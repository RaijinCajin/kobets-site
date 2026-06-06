import requests, psycopg2, os
from datetime import datetime
from dotenv import load_dotenv

load_dotenv("/opt/kobets/.env")
DB = psycopg2.connect(dbname=os.getenv("POSTGRES_DB"), user=os.getenv("POSTGRES_USER"),
    password=os.getenv("POSTGRES_PASSWORD"), host="localhost", port=5432)

API_KEY = os.getenv("ODDS_API_KEY")
SPORTS = ["mma_mixed_martial_arts", "boxing_boxing"]
BOOKS = ["draftkings", "fanduel", "betmgm"]

def fetch_odds(sport):
    r = requests.get(f"https://api.the-odds-api.com/v4/sports/{sport}/odds/",
        params={"apiKey": API_KEY, "regions": "us", "markets": "h2h",
                "bookmakers": ",".join(BOOKS)}, timeout=15)
    print(f"  Requests remaining: {r.headers.get('x-requests-remaining', 'N/A')}")
    if r.status_code != 200:
        print(f"  Error {r.status_code}: {r.text[:200]}")
        return []
    return r.json()

if __name__ == "__main__":
    cur = DB.cursor()
    total = 0
    for sport in SPORTS:
        print(f"\nFetching {sport}...")
        games = fetch_odds(sport)
        print(f"  Found {len(games)} events with odds")
        for game in games:
            home = game.get("home_team","")
            away = game.get("away_team","")
            print(f"  {home} vs {away}")
            for bm in game.get("bookmakers",[]):
                for market in bm.get("markets",[]):
                    if market["key"] != "h2h": continue
                    outcomes = {o["name"]: o["price"] for o in market["outcomes"]}
                    cur.execute(
                        "INSERT INTO odds (sportsbook, fighter_a_odds, fighter_b_odds, scraped_at) VALUES (%s,%s,%s,%s)",
                        (bm["key"], str(outcomes.get(home,"N/A")), str(outcomes.get(away,"N/A")), datetime.now()))
                    total += 1
    DB.commit()
    print(f"\nTotal odds rows saved: {total}")
