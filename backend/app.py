#pomoc AI przy używaniu frameworka Flask i łączeniu z bazą danych PostgreSQL

import os
import psycopg
from flask import Flask, jsonify

app = Flask(__name__)

@app.get("/api/status")
def status():
    try:
        with psycopg.connect(
            host=os.environ["DB_HOST"],
            dbname=os.environ["DB_NAME"],
            user=os.environ["DB_USER"],
            password=os.environ["DB_PASSWORD"]
        ) as conn:
            conn.execute("SELECT 1")
        return jsonify({"message": "Backend i baza danych działają!"})
    except psycopg.Error:
        return jsonify({"message": "Brak połączenia z bazą danych"}), 503

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)