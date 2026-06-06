import psycopg2, os, requests, json
from bs4 import BeautifulSoup
from datetime import datetime
from dotenv import load_dotenv

load_dotenv("/opt/kobets/.env")
DB = psycopg2.connect(dbname=os.getenv("POSTGRES_DB"), user=os.getenv("POSTGRES_USER"),
    password=os.getenv("POSTGRES_PASSWORD"), host="localhost", port=5432)

NETWORKS = ["ESPN","UFC Fight Pass","PPV","DAZN","Showtime","Fox","CBS","TNT","Max","Prime","Internet","Peacock","Paramount"]
MONTHS = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"]

def fetch_via_crawl4ai(url):
    resp = requests.post("http://localhost:11235/crawl", json={
        "urls": [url],
        "browser_config": {"headless": True},
        "crawler_config": {"wait_for": "div[id^=preview]", "page_timeout": 20000}
    }, timeout=60)
    results = resp.json().get("results", [{}])
    return results[0].get("html", "") if results else ""

def scrape_tapology():
    html = fetch_via_crawl4ai("https://www.tapology.com/fightcenter")
    if not html:
        print("ERROR: No HTML from Crawl4AI")
        return []
    soup = BeautifulSoup(html, "html.parser")
    events = []
    for preview in soup.select("div[id^='preview']"):
        promo = preview.select_one("div.promotion")
        if not promo:
            continue
        name_el = promo.select_one("a")
        event_name = name_el.text.strip() if name_el else "Unknown"
        fight_date, broadcast = None, "TBD"
        for span in promo.select("span"):
            txt = span.text.strip()
            if not fight_date and any(m in txt for m in MONTHS):
                fight_date = txt
            if broadcast == "TBD" and any(n in txt for n in NETWORKS):
                broadcast = txt
        sport = "MMA"
        geo = preview.select_one("div.geography")
        if geo:
            sp = geo.select_one("span.sport")
            if sp:
                sport = sp.text.strip()
        events.append({"event_name": event_name, "fight_date": fight_date,
                       "broadcast": broadcast, "promotion": sport, "scraped_at": datetime.now()})
    return events

if __name__ == "__main__":
    print("Scraping via Crawl4AI...")
    events = scrape_tapology()
    for e in events:
        print(f"  [{e['promotion']}] {e['event_name']} | {e['fight_date']} | {e['broadcast']}")
    cur = DB.cursor()
    for e in events:
        cur.execute("INSERT INTO fights (event_name, fight_date, broadcast, promotion, scraped_at) VALUES (%s,%s,%s,%s,%s)",
            (e["event_name"], e["fight_date"], e["broadcast"], e["promotion"], e["scraped_at"]))
    DB.commit()
    print(f"Saved {len(events)} events.")
