import psycopg2, os
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv

load_dotenv("/opt/kobets/.env")
app = FastAPI(title="Kobets.com API")
app.add_middleware(CORSMiddleware, allow_origins=["*"], allow_methods=["*"], allow_headers=["*"])

def get_db():
    return psycopg2.connect(dbname=os.getenv("POSTGRES_DB"), user=os.getenv("POSTGRES_USER"),
        password=os.getenv("POSTGRES_PASSWORD"), host="localhost", port=5432)

@app.get("/")
def root():
    return {"status": "Kobets.com API running"}

@app.get("/fights")
def get_fights():
    db = get_db()
    cur = db.cursor()
    cur.execute("SELECT id, event_name, fight_date, broadcast, promotion FROM fights ORDER BY id DESC LIMIT 50")
    rows = cur.fetchall()
    return [{"id":r[0],"event":r[1],"date":r[2],"broadcast":r[3],"sport":r[4]} for r in rows]

@app.get("/predictions")
def get_predictions():
    db = get_db()
    cur = db.cursor()
    cur.execute("SELECT id, predicted_winner, confidence, reasoning, created_at FROM predictions ORDER BY id DESC LIMIT 20")
    rows = cur.fetchall()
    return [{"id":r[0],"pick":r[1],"confidence":r[2],"analysis":r[3],"created":str(r[4])} for r in rows]

@app.get("/odds")
def get_odds():
    db = get_db()
    cur = db.cursor()
    cur.execute("SELECT sportsbook, fighter_a_odds, fighter_b_odds, scraped_at FROM odds ORDER BY id DESC LIMIT 50")
    rows = cur.fetchall()
    return [{"book":r[0],"fighter_a":r[1],"fighter_b":r[2],"updated":str(r[3])} for r in rows]
