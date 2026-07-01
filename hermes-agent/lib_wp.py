#!/usr/bin/env python3
"""
lib_wp.py — Shared WordPress REST client for ko-bets.com agents.

Reads credentials from the environment:
  KOBETS_WP_URL           e.g. https://ko-bets.com
  KOBETS_WP_USER          WP username / email
  KOBETS_WP_APP_PASSWORD  WP application password (spaces OK)

Usage:
  from lib_wp import WP
  wp = WP()
  wp.selftest()

CLI:
  python3 lib_wp.py --selftest
"""
import os
import sys
import json
import base64
import datetime as _dt

try:
    import requests
except ImportError:
    sys.stderr.write("requests not installed. Run: pip install requests\n")
    raise

# Custom post type REST bases confirmed live on ko-bets.com
CPT_PICK = "kb_pick"
CPT_EVENT = "kb_event"
CPT_RESULT = "kb_result"
CPT_FIGHTER = "kb_fighter"

# Registered meta fields per post type (confirmed via live wp-json inspection)
META_FIELDS = {
    CPT_FIGHTER: ["kb_record", "kb_stance", "kb_weight_class", "kb_discipline", "kb_nickname"],
    CPT_EVENT: ["kb_sport", "kb_promotion", "kb_headline", "kb_venue", "kb_start_utc",
                "kb_broadcaster", "kb_stream_service", "kb_affiliate_url", "kb_status"],
    CPT_PICK: ["kb_sport", "kb_league", "kb_matchup", "kb_selection", "kb_market", "kb_odds",
               "kb_status", "kb_result_note", "kb_date_graded", "kb_article_url",
               "kb_confidence", "kb_units"],
}


class WPError(Exception):
    pass


class WP:
    def __init__(self, url=None, user=None, app_password=None, timeout=30):
        self.url = (url or os.environ.get("KOBETS_WP_URL", "https://ko-bets.com")).rstrip("/")
        self.user = user or os.environ.get("KOBETS_WP_USER", "")
        pw = app_password or os.environ.get("KOBETS_WP_APP_PASSWORD", "")
        # WP application passwords are shown with spaces; the API accepts them with spaces removed
        self.app_password = pw.replace(" ", "")
        self.timeout = timeout
        if not self.user or not self.app_password:
            raise WPError("Missing KOBETS_WP_USER or KOBETS_WP_APP_PASSWORD in environment")
        token = base64.b64encode(f"{self.user}:{self.app_password}".encode()).decode()
        self.session = requests.Session()
        self.session.headers.update({
            "Authorization": f"Basic {token}",
            "Content-Type": "application/json",
            "User-Agent": "kobets-agent/1.0",
        })

    # ---- low level ----
    def _api(self, path):
        return f"{self.url}/wp-json/wp/v2/{path.lstrip('/')}"

    def _request(self, method, path, **kw):
        kw.setdefault("timeout", self.timeout)
        r = self.session.request(method, self._api(path), **kw)
        if r.status_code >= 400:
            raise WPError(f"{method} {path} -> {r.status_code}: {r.text[:500]}")
        if r.text.strip() == "":
            return None
        return r.json()

    def get(self, path, params=None):
        return self._request("GET", path, params=params)

    def post(self, path, payload):
        return self._request("POST", path, data=json.dumps(payload))

    # ---- collections ----
    def list_items(self, rest_base, per_page=100, params=None):
        """Fetch all items of a post type, following pagination."""
        out = []
        page = 1
        while True:
            p = {"per_page": per_page, "page": page}
            if params:
                p.update(params)
            try:
                batch = self.get(rest_base, params=p)
            except WPError as e:
                # WP returns 400 once you page past the end
                if "rest_post_invalid_page_number" in str(e) or "-> 400" in str(e):
                    break
                raise
            if not batch:
                break
            out.extend(batch)
            if len(batch) < per_page:
                break
            page += 1
        return out

    def fighters(self):
        return self.list_items(CPT_FIGHTER)

    def events(self, status=None):
        items = self.list_items(CPT_EVENT)
        if status:
            items = [e for e in items if (e.get("meta") or {}).get("kb_status") == status]
        return items

    def picks(self, status=None):
        items = self.list_items(CPT_PICK)
        if status:
            items = [p for p in items if (p.get("meta") or {}).get("kb_status") == status]
        return items

    # ---- categories ----
    def ensure_category(self, name):
        """Return category id, creating it if needed."""
        existing = self.get("categories", params={"search": name, "per_page": 100}) or []
        for c in existing:
            if c.get("name", "").lower() == name.lower():
                return c["id"]
        created = self.post("categories", {"name": name})
        return created["id"]

    # ---- posts ----
    def create_post(self, title, content, category_ids=None, excerpt=None, status="publish"):
        payload = {"title": title, "content": content, "status": status}
        if excerpt:
            payload["excerpt"] = excerpt
        if category_ids:
            payload["categories"] = category_ids
        return self.post("posts", payload)

    def find_post_by_slug(self, slug):
        res = self.get("posts", params={"slug": slug, "status": "publish,draft,future,pending"})
        return res[0] if res else None

    def create_post_if_absent(self, title, content, slug, category_ids=None, excerpt=None):
        existing = self.find_post_by_slug(slug)
        if existing:
            return existing, False
        payload = {"title": title, "content": content, "status": "publish", "slug": slug}
        if excerpt:
            payload["excerpt"] = excerpt
        if category_ids:
            payload["categories"] = category_ids
        return self.post("posts", payload), True

    # ---- CPT update ----
    def update_meta(self, rest_base, item_id, meta):
        clean = {k: v for k, v in meta.items() if k in META_FIELDS.get(rest_base, [])}
        return self.post(f"{rest_base}/{item_id}", {"meta": clean})

    # ---- selftest ----
    def selftest(self):
        me = self.get("users/me", params={"context": "edit"})
        print(f"WP auth OK as: {me.get('name')} (id {me.get('id')})")
        counts = {}
        for base in (CPT_PICK, CPT_EVENT, CPT_FIGHTER, CPT_RESULT):
            try:
                items = self.list_items(base, per_page=100)
                counts[base] = len(items)
            except WPError as e:
                counts[base] = f"ERR {e}"
        print("  " + ", ".join(f"{k}: {v}" for k, v in counts.items()))
        return counts


def main(argv):
    if "--selftest" in argv:
        WP().selftest()
        return 0
    print(__doc__)
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
