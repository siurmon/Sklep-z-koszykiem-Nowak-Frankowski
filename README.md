# Sklep-z-koszykiem-Nowak-Frankowski

## 1. Uruchomienie

W folderze z `compose.yaml`:

```powershell
docker compose up -d --build
```

Strona: http://localhost:8080

Test backendu: http://localhost:8080/health

Status backendu i bazy: http://localhost:8080/api/status

Port strony, nazwa bazy, użytkownik i hasło są w pliku `.env` (przykład w `.env.example`).

## 2. Endpointy — wszystkie odpowiedzi są w formacie JSON

| Metoda  | Adres               | Znaczenie                         | kod |
|---------|---------------------|-----------------------------------|-----|
| GET     | `/health`           | Czy backend PHP działa (bez bazy) | 200 |
| GET     | `/api/status`       | Czy działa backend i baza         | 200 |
| GET     | `/api/produkty`     | Lista produktów                   | 200 |
| GET     | `/api/produkty/1`   | Jeden produkt po ID               | 200 |
| POST    | `/api/produkty`     | Nowy produkt                      | 201 |
| GET     | `/api/koszyk`       | Pozycje i suma koszyka            | 200 |
| POST    | `/api/koszyk`       | Dodanie produktu do koszyka       | 200 |
| DELETE  | `/api/koszyk/1`     | Usunięcie produktu 1 z koszyka    | 200 |

### 3. Błędy API

| HTTP| Kiedy                                                     | Przykład JSON                               |
|-----|-----------------------------------------------------------|---------------------------------------------|
| 400 | Zły JSON, pusta nazwa, niepoprawna cena lub ID            | `{"blad":"Wyślij poprawny JSON."}`          |
| 404 | Produkt nie istnieje, brak wpisu w koszyku lub zły adres  | `{"blad":"Produkt nie istnieje."}`          |
| 503 | Nie udało się połączyć z bazą albo wykonać SQL            | `{"blad":"Baza danych jest niedostępna."}`  |

Walidacja produktu: nazwa musi być tekstem, nie może być pusta; 
Cena musi być liczbą JSON od `0` do `99999999.99`. Cena jest zaokrąglana do dwóch miejsc po przecinku. 
`id_produktu` ma być dodatnią liczbą całkowitą.

## 4. Zmienne środowiskowe

Przykład pliku `.env`:

```.env
DB_NAME=sklep
DB_USER=sklep
DB_PASSWORD=sklep123
DB_PORT=5432
FRONTEND_PORT=8080
```

## 5. Struktura plików

```text
Sklep-z-koszykiem-PHP-wymagania/
├── compose.yaml
├── .env.example
├── .env
├── backend/
│   ├── Dockerfile
│   ├── db.php
│   └── index.php
├── db/
│   └── init.sql
└── frontend/
    ├── Dockerfile
    ├── nginx.conf
    ├── site.html
    ├── style.css
    └── scripts.js
```

## 6. Ręczne testowanie API w PowerShell

```powershell
# Pojedynczy produkt
Invoke-RestMethod http://localhost:8080/api/produkty/1

# Lista produktów
Invoke-RestMethod http://localhost:8080/api/produkty

# Dodanie produktu (POST + JSON)
Invoke-RestMethod http://localhost:8080/api/produkty -Method POST -ContentType 'application/json' -Body '{"nazwa":"Myszka","cena":49.99}'

# Zły produkt — oczekiwany HTTP 400
Invoke-WebRequest http://localhost:8080/api/produkty -Method POST -ContentType 'application/json' -Body '{"nazwa":"","cena":-1}' -SkipHttpErrorCheck

# Nieistniejący produkt — oczekiwany HTTP 404
Invoke-WebRequest http://localhost:8080/api/produkty/999999 -SkipHttpErrorCheck

# Dodanie do koszyka
Invoke-RestMethod http://localhost:8080/api/koszyk -Method POST -ContentType 'application/json' -Body '{"id_produktu":1}'

# Usunięcie z koszyka
Invoke-RestMethod http://localhost:8080/api/koszyk/1 -Method DELETE
```
