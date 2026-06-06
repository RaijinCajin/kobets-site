import psycopg2, os, anthropic
from datetime import datetime
from dotenv import load_dotenv
load_dotenv("/opt/kobets/.env")
DB = psycopg2.connect(dbname=os.getenv("POSTGRES_DB"), user=os.getenv("POSTGRES_USER"), password=os.getenv("POSTGRES_PASSWORD"), host="localhost", port=5432)
client = anthropic.Anthropic(api_key=os.getenv("ANTHROPIC_API_KEY"))
fights = [("Belal Muhammad","Gabriel Bonfim"),("Alex Pereira","Ciryl Gane"),("Conor McGregor","Max Holloway"),("Islam Makhachev","Michael Morales"),("Ilia Topuria","Justin Gaethje")]
cur = DB.cursor()
cur.execute("SELECT fighter_a_odds, fighter_b_odds FROM odds LIMIT 5")
odds = cur.fetchall()
print("Kobets.com Fight Predictor - Powered by Claude\n")
for i,(fa,fb) in enumerate(fights):
    oa,ob = odds[i] if i < len(odds) else ("N/A","N/A")
    print(f"\n{'='*50}\n{fa} vs {fb}\nOdds: {oa} / {ob}\n{'='*50}")
    msg = client.messages.create(model="claude-sonnet-4-6", max_tokens=400, messages=[{"role":"user","content":f"MMA analyst for Kobets.com. Fight: {fa} vs {fb}. Odds: {fa}={oa}, {fb}={ob}. Give: 1)PICK+reason 2)CONFIDENCE 3)METHOD 4)BET? Be brief."}])
    result = msg.content[0].text
    print(result)
    cur.execute("INSERT INTO predictions (predicted_winner, confidence, reasoning, created_at) VALUES (%s,%s,%s,%s)", (fa, "Medium", result, datetime.now()))
DB.commit()
print("\nAll predictions saved!")
