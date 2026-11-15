CREATE TABLE produkty (
    id SERIAL PRIMARY KEY,
    nazwa VARCHAR(100) NOT NULL,
    cena NUMERIC(10, 2) NOT NULL CHECK (cena >= 0)
);

CREATE TABLE koszyk (
    id_produktu INTEGER PRIMARY KEY REFERENCES produkty(id) ON DELETE CASCADE,
    ilosc INTEGER NOT NULL CHECK (ilosc > 0)
);

INSERT INTO produkty (nazwa, cena) VALUES
    ('Koszulka', 39.99),
    ('Kubek', 19.99),
    ('Dlugopis', 4.50);
