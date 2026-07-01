#!/usr/bin/env python3
"""
agent_grade_picks.py — Grades pending kb_pick records on ko-bets.com.

Matches each pending pick against kb_result records (by matchup/selection text).
When a confident match is found, sets kb_status to win/loss/push and stamps
kb_result_note + kb_date_graded. Only registered meta fields are written.

  --dry-run   Report intended grades without writing.

Requires KOBETS_WP_* env vars.
"""
import os
import sys
import re
import datetime as _dt

from lib_wp import WP, CPT_RESULT


def _norm(s):
    return re.sub(r"[^a-z0-9]+", " ", (s or "").lower()).strip()


def grade(dry=False):
    wp = WP()
    pending = wp.picks(status="pending")
    if not pending:
        print("[grade] no pending picks")
        return 0
    results = wp.list_items(CPT_RESULT)
    if not results:
        print(f"[grade] {len(pending)} pending picks, but no kb_result records to grade against")
        return 0

    # Build a lookup of result text -> outcome
    res_index = []
    for r in results:
        meta = r.get("meta") or {}
        title = re.sub(r"<[^>]+>", "", r.get("title", {}).get("rendered", ""))
        res_index.append((_norm(title + " " + " ".join(str(v) for v in meta.values())), meta, title))

    graded = 0
    now = _dt.datetime.now(_dt.timezone.utc).strftime("%Y-%m-%d")
    for p in pending:
        meta = p.get("meta") or {}
        key = _norm((meta.get("kb_matchup") or "") + " " + (meta.get("kb_selection") or ""))
        sel = _norm(meta.get("kb_selection") or "")
        if not sel:
            continue
        match = None
        for text, rmeta, rtitle in res_index:
            if sel and sel in text:
                match = (rmeta, rtitle)
                break
        if not match:
            continue
        rmeta, rtitle = match
        outcome = (rmeta.get("kb_status") or rmeta.get("kb_result") or "").lower()
        if outcome not in ("win", "loss", "push"):
            # cannot determine confidently; skip
            continue
        note = f"Graded from result: {rtitle}"
        if dry:
            print(f"[grade][dry] pick {p['id']} '{sel}' -> {outcome}")
        else:
            wp.update_meta("kb_pick", p["id"], {
                "kb_status": outcome,
                "kb_result_note": note,
                "kb_date_graded": now,
            })
            print(f"[grade] pick {p['id']} '{sel}' -> {outcome}")
        graded += 1
    print(f"[grade] done: {graded} graded, {len(pending) - graded} still pending")
    return 0


if __name__ == "__main__":
    sys.exit(grade(dry="--dry-run" in sys.argv))
