#!/usr/bin/env python3
"""
api_bridge.py — Syncs api.ko-bets.com -> WordPress custom post types.

Reads route paths from site-config.json -> api_bridge.routes. If a route is
blank, that entity is skipped (no-op). This is intentional: WordPress is already
populated, and api.ko-bets.com's route names must be supplied before the bridge
can fetch from it. Fill in the routes in site-config.json, then:

  python3 api_bridge.py --once

Maps source records into WP CPT meta using the field names registered live.
Upserts by slug so re-running is idempotent.

Requires KOBETS_WP_* env vars.
"""
import os
import sys
import json
import re

import requests
from lib_wp import WP, CPT_FIGHTER, CPT_EVENT, CPT_PICK

HERE = os.path.dirname(os.path.abspath(__file__))


def load_config():
    with open(os.path.join(HERE, "site-config.json")) as f:
        return json.load(f)


def _slug(s):
    return re.sub(r"[^a-z0-9]+", "-", (s or "").lower()).strip("-")[:80]


def fetch(base, route):
    if not route:
        return None
    url = base.rstrip("/") + "/" + route.lstrip("/")
    r = requests.get(url, timeout=30, headers={"User-Agent": "kobets-bridge/1.0"})
    r.raise_for_status()
    data = r.json()
    if isinstance(data, dict):
        for k in ("data", "results", "items"):
            if isinstance(data.get(k), list):
                return data[k]
        return [data]
    return data


# Map source fields -> WP meta. Adjust keys if the API uses different names.
MAPPERS = {
    "fighters": (CPT_FIGHTER, {
        "name": "title", "nickname": "kb_nickname", "record": "kb_record",
        "stance": "kb_stance", "weight_class": "kb_weight_class", "discipline": "kb_discipline",
    }),
    "events": (CPT_EVENT, {
        "name": "title", "title": "title", "sport": "kb_sport", "promotion": "kb_promotion",
        "headline": "kb_headline", "venue": "kb_venue", "start_utc": "kb_start_utc",
        "broadcaster": "kb_broadcaster", "stream_service": "kb_stream_service", "status": "kb_status",
    }),
    "picks": (CPT_PICK, {
        "matchup": "kb_matchup", "selection": "kb_selection", "sport": "kb_sport",
        "league": "kb_league", "market": "kb_market", "odds": "kb_odds", "status": "kb_status",
        "confidence": "kb_confidence", "units": "kb_units",
    }),
}


def upsert(wp, rest_base, records, field_map):
    n = 0
    for rec in records:
        title = rec.get("name") or rec.get("title") or rec.get("matchup") or rec.get("selection")
        if not title:
            continue
        slug = _slug(title)
        meta = {}
        for src, dest in field_map.items():
            if dest == "title":
                continue
            if src in rec and rec[src] not in (None, ""):
                meta[dest] = rec[src]
        existing = wp.get(rest_base, params={"slug": slug})
        payload = {"title": title, "status": "publish", "slug": slug, "meta": meta}
        clean_meta = {k: v for k, v in meta.items() if k in __import__("lib_wp").META_FIELDS.get(rest_base, [])}
        payload["meta"] = clean_meta
        if existing:
            wp.post(f"{rest_base}/{existing[0]['id']}", {"meta": clean_meta})
        else:
            wp.post(rest_base, payload)
        n += 1
    return n


def main(argv):
    cfg = load_config()
    bridge = cfg.get("api_bridge", {})
    base = bridge.get("base", "")
    routes = bridge.get("routes", {})
    wp = WP()
    totals = {}
    for key, (rest_base, field_map) in MAPPERS.items():
        route = routes.get(key, "")
        if not route:
            print(f"[bridge] {key}: no route configured, skipping")
            continue
        try:
            records = fetch(base, route) or []
        except Exception as e:
            print(f"[bridge] {key}: fetch failed: {e}")
            continue
        totals[key] = upsert(wp, rest_base, records, field_map)
    if totals:
        print("[bridge] synced: " + ", ".join(f"{v} {k}" for k, v in totals.items()))
    else:
        print("[bridge] nothing synced (no routes configured). WordPress data left intact.")
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
