#!/usr/bin/env python3
"""
agent_content.py — Generates Fight Analysis articles for ko-bets.com.

  --preview   Generate ONE fight-preview post for the next upcoming event.
  --profile   Generate ONE fighter-profile post for the next fighter without one.
  --weekly    Do both (default for cron).
  --dry-run   Print what would be published; do not POST to WordPress.

Requires env:
  ANTHROPIC_API_KEY        (already present in the Hermes container)
  KOBETS_WP_URL / KOBETS_WP_USER / KOBETS_WP_APP_PASSWORD
Optional:
  KOBETS_MODEL             default claude-haiku-4-5-20251001
"""
import os
import sys
import re
import json
import datetime as _dt

from lib_wp import WP, CPT_EVENT, CPT_FIGHTER

CATEGORY = "Fight Analysis"
DEFAULT_MODEL = os.environ.get("KOBETS_MODEL", "claude-haiku-4-5-20251001")

DISCLAIMER = (
    "<hr>\n<p><em>KO-Bets is an independent, educational resource. This article is for "
    "informational purposes only and is not betting advice. Past performance does not "
    "guarantee future results. Must be 18+ (or the legal age in your area). If gambling "
    "stops being fun, call 1-800-GAMBLER.</em></p>"
)


def _client():
    try:
        from anthropic import Anthropic
    except ImportError:
        sys.stderr.write("anthropic not installed. Run: pip install anthropic\n")
        raise
    return Anthropic()  # reads ANTHROPIC_API_KEY


def _slugify(s):
    s = re.sub(r"[^a-z0-9]+", "-", (s or "").lower()).strip("-")
    return s[:80] or "item"


def _generate(prompt, system, max_tokens=1400):
    client = _client()
    msg = client.messages.create(
        model=DEFAULT_MODEL,
        max_tokens=max_tokens,
        system=system,
        messages=[{"role": "user", "content": prompt}],
    )
    parts = [b.text for b in msg.content if getattr(b, "type", "") == "text"]
    return "\n".join(parts).strip()


def _clean_html(text):
    """Strip code fences and any model-added disclaimer so we control the footer."""
    text = re.sub(r"^```[a-zA-Z]*\n?", "", text.strip())
    text = re.sub(r"\n?```$", "", text.strip())
    # drop a trailing horizontal rule + everything after if model added its own disclaimer
    return text.strip()


SYSTEM = (
    "You are a combat-sports writer for KO-Bets, an independent educational MMA/boxing "
    "analysis site. Write in clear, confident, neutral prose. Use semantic WordPress HTML: "
    "<p> paragraphs and <h2> subheadings only (no <h1>, no markdown, no images, no links). "
    "Be factual and measured; never guarantee outcomes or give direct betting advice. "
    "Do not invent specific statistics, records, or quotes you were not given. "
    "Do NOT include a disclaimer — it is appended automatically."
)


def pick_next_event(wp):
    events = wp.events()
    now = _dt.datetime.now(_dt.timezone.utc)
    upcoming = []
    for e in events:
        meta = e.get("meta") or {}
        if meta.get("kb_status") not in (None, "", "upcoming"):
            continue
        start = meta.get("kb_start_utc") or ""
        try:
            dt = _dt.datetime.fromisoformat(start.replace("Z", "+00:00"))
        except ValueError:
            dt = None
        if dt and dt < now:
            continue
        upcoming.append((dt or now, e))
    upcoming.sort(key=lambda t: t[0])
    return upcoming[0][1] if upcoming else (events[0] if events else None)


def make_preview(wp, dry=False):
    ev = pick_next_event(wp)
    if not ev:
        print("[content] no events found; skipping preview")
        return None
    meta = ev.get("meta") or {}
    title_txt = re.sub(r"<[^>]+>", "", ev.get("title", {}).get("rendered", "")).strip()
    slug = "preview-" + _slugify(ev.get("slug") or title_txt)
    existing = wp.find_post_by_slug(slug)
    if existing:
        print(f"[content] preview already exists: {existing.get('link')}")
        return existing

    facts = {
        "event": title_txt,
        "promotion": meta.get("kb_promotion"),
        "headline": meta.get("kb_headline"),
        "sport": meta.get("kb_sport"),
        "venue": meta.get("kb_venue"),
        "start_utc": meta.get("kb_start_utc"),
        "broadcaster": meta.get("kb_broadcaster"),
    }
    prompt = (
        "Write a 400-550 word preview article for this upcoming combat-sports event. "
        "Open with a short hook paragraph, then 2-3 <h2> sections covering what to watch, "
        "the stylistic matchup themes in general terms, and how to follow the card. "
        "Only use the facts provided; if a field is empty, omit it gracefully.\n\n"
        f"FACTS (JSON):\n{json.dumps(facts, indent=2)}"
    )
    body = _clean_html(_generate(prompt, SYSTEM))
    post_title = f"Preview: {title_txt}"
    content = body + "\n" + DISCLAIMER
    excerpt = f"What to watch at {title_txt} — an independent KO-Bets preview."
    if dry:
        print(f"[content][dry] PREVIEW '{post_title}' ({len(body)} chars) slug={slug}")
        return None
    cat = wp.ensure_category(CATEGORY)
    post, created = wp.create_post_if_absent(post_title, content, slug, [cat], excerpt)
    print(f"[content] preview {'published' if created else 'exists'}: {post.get('link')}")
    return post


def pick_next_fighter(wp):
    """Prefer fighters referenced in an upcoming (pending) pick's matchup; fall back to any unprofiled fighter."""
    priority_names = []
    try:
        for p in wp.picks() or []:
            meta = p.get("meta") or {}
            if (meta.get("kb_status") or "pending").lower() != "pending":
                continue
            matchup = meta.get("kb_matchup") or ""
            for part in re.split(r"\s+vs\.?\s+", matchup, flags=re.I):
                part = part.strip()
                if part:
                    priority_names.append(part.lower())
    except Exception:
        priority_names = []

    fighters = list(wp.fighters())

    def _candidate(f):
        name = re.sub(r"<[^>]+>", "", f.get("title", {}).get("rendered", "")).strip()
        if not name:
            return None
        slug = "profile-" + _slugify(f.get("slug") or name)
        if wp.find_post_by_slug(slug):
            return None
        return f, slug, name

    if priority_names:
        for f in fighters:
            cand = _candidate(f)
            if not cand:
                continue
            _, _, name = cand
            if any(name.lower() in pn or pn in name.lower() for pn in priority_names):
                return cand

    for f in fighters:
        cand = _candidate(f)
        if cand:
            return cand
    return None, None, None


def make_profile(wp, dry=False):
    f, slug, name = pick_next_fighter(wp)
    if not f:
        print("[content] all fighters already profiled; skipping")
        return None
    meta = f.get("meta") or {}
    facts = {
        "name": name,
        "nickname": meta.get("kb_nickname"),
        "record": meta.get("kb_record"),
        "stance": meta.get("kb_stance"),
        "weight_class": meta.get("kb_weight_class"),
        "discipline": meta.get("kb_discipline"),
    }
    prompt = (
        "Write a 350-500 word fighter profile. Open with a one-paragraph intro, then 2 "
        "<h2> sections (e.g. 'Style and background', 'What to know'). Use only the facts "
        "provided; if a field is empty, write generally without inventing specifics.\n\n"
        f"FACTS (JSON):\n{json.dumps(facts, indent=2)}"
    )
    body = _clean_html(_generate(prompt, SYSTEM, max_tokens=1100))
    post_title = f"Fighter Profile: {name}"
    content = body + "\n" + DISCLAIMER
    excerpt = f"An independent KO-Bets profile of {name}."
    if dry:
        print(f"[content][dry] PROFILE '{post_title}' ({len(body)} chars) slug={slug}")
        return None
    cat = wp.ensure_category(CATEGORY)
    post, created = wp.create_post_if_absent(post_title, content, slug, [cat], excerpt)
    print(f"[content] profile {'published' if created else 'exists'}: {post.get('link')}")
    return post


def main(argv):
    dry = "--dry-run" in argv
    wp = WP()
    did = False
    if "--preview" in argv or "--weekly" in argv or not any(
        a in argv for a in ("--preview", "--profile")
    ):
        make_preview(wp, dry=dry)
        did = True
    if "--profile" in argv or "--weekly" in argv:
        make_profile(wp, dry=dry)
        did = True
    if not did:
        print(__doc__)
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
