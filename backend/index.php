<?php

require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Wysyla odpowiedz w formacie JSON z odpowiednim kodem HTTP.
function odpowiedz($dane, $kod = 200) {
    http_response_code($kod);
    echo json_encode($dane, JSON_UNESCAPED_UNICODE);
    exit;
}

// Odczytuje JSON wyslany przez frontend.
function pobierzDane() {
    $dane = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($dane)) {
        odpowiedz(['blad' => 'Wyślij poprawny JSON.'], 400);
    }
    return $dane;
}

$adres = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$metoda = $_SERVER['REQUEST_METHOD'];

// /health sprawdza tylko PHP, nawet gdy baza danych jest wylaczona.
if ($adres === '/health' && $metoda === 'GET') {
    odpowiedz(['status' => 'ok']);
}

// Status dwoch osobnych uslug, zawsze zwracany, jesli dziala PHP.
if ($adres === '/api/status' && $metoda === 'GET') {
    try {
        $baza = polaczZBaza();
        $baza->query('SELECT 1');
        odpowiedz(['backend' => true, 'database' => true]);
    } catch (PDOException $blad) {
        error_log($blad->getMessage());
        odpowiedz(['backend' => true, 'database' => false]);
    }
}

try {
    // GET /api/produkty - lista produktow.
    if ($adres === '/api/produkty' && $metoda === 'GET') {
        $baza = polaczZBaza();
        $produkty = $baza->query('SELECT id, nazwa, cena FROM produkty ORDER BY id')
                        ->fetchAll(PDO::FETCH_ASSOC);
        foreach ($produkty as &$produkt) {
            $produkt['id'] = (int) $produkt['id'];
            $produkt['cena'] = (float) $produkt['cena'];
        }
        odpowiedz($produkty);
    }

    // GET /api/produkty/1 - jeden produkt; brak produktu = 404.
    if ($metoda === 'GET' && preg_match('#^/api/produkty/([0-9]+)$#', $adres, $pasuje)) {
        $baza = polaczZBaza();
        $zapytanie = $baza->prepare('SELECT id, nazwa, cena FROM produkty WHERE id = ?');
        $zapytanie->execute([(int) $pasuje[1]]);
        $produkt = $zapytanie->fetch(PDO::FETCH_ASSOC);
        if (!$produkt) {
            odpowiedz(['blad' => 'Produkt nie istnieje.'], 404);
        }
        $produkt['id'] = (int) $produkt['id'];
        $produkt['cena'] = (float) $produkt['cena'];
        odpowiedz($produkt);
    }

    // POST /api/produkty - dodanie nowego produktu.
    if ($adres === '/api/produkty' && $metoda === 'POST') {
        $dane = pobierzDane();
        $nazwa = is_string($dane['nazwa'] ?? null) ? trim($dane['nazwa']) : '';
        $cena = $dane['cena'] ?? null;

        if ($nazwa === '' || strlen($nazwa) > 100 ||
            (!is_int($cena) && !is_float($cena)) || $cena < 0 || $cena > 99999999.99) {
            odpowiedz(['blad' => 'Podaj nazwę (maks. 100 znaków) i poprawną cenę (0-99999999.99).'], 400);
        }

        $baza = polaczZBaza();
        $zapytanie = $baza->prepare('INSERT INTO produkty (nazwa, cena) VALUES (?, ?) RETURNING id');
        $zapytanie->execute([$nazwa, round($cena, 2)]);
        odpowiedz(['id' => (int) $zapytanie->fetchColumn(), 'wiadomosc' => 'Produkt dodany.'], 201);
    }

    // GET /api/koszyk - wszystkie pozycje koszyka.
    if ($adres === '/api/koszyk' && $metoda === 'GET') {
        $baza = polaczZBaza();
        $produkty = $baza->query('SELECT p.id, p.nazwa, p.cena, k.ilosc, p.cena * k.ilosc AS suma
                                 FROM koszyk k JOIN produkty p ON p.id = k.id_produktu ORDER BY p.id')
                        ->fetchAll(PDO::FETCH_ASSOC);
        $razem = 0;
        foreach ($produkty as &$produkt) {
            $produkt['id'] = (int) $produkt['id'];
            $produkt['cena'] = (float) $produkt['cena'];
            $produkt['ilosc'] = (int) $produkt['ilosc'];
            $produkt['suma'] = (float) $produkt['suma'];
            $razem += $produkt['suma'];
        }
        odpowiedz(['produkty' => $produkty, 'razem' => round($razem, 2)]);
    }

    // POST /api/koszyk - dodanie do koszyka (kolejny raz zwieksza ilosc).
    if ($adres === '/api/koszyk' && $metoda === 'POST') {
        $dane = pobierzDane();
        $id = filter_var($dane['id_produktu'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            odpowiedz(['blad' => 'Niepoprawny numer produktu.'], 400);
        }

        $baza = polaczZBaza();
        $zapytanie = $baza->prepare('SELECT id FROM produkty WHERE id = ?');
        $zapytanie->execute([$id]);
        if (!$zapytanie->fetch()) {
            odpowiedz(['blad' => 'Produkt nie istnieje.'], 404);
        }

        $zapytanie = $baza->prepare('INSERT INTO koszyk (id_produktu, ilosc) VALUES (?, 1)
                                     ON CONFLICT (id_produktu) DO UPDATE SET ilosc = koszyk.ilosc + 1');
        $zapytanie->execute([$id]);
        odpowiedz(['wiadomosc' => 'Dodano do koszyka.']);
    }

    // DELETE /api/koszyk/1 - usuniecie z koszyka.
    if ($metoda === 'DELETE' && preg_match('#^/api/koszyk/([0-9]+)$#', $adres, $pasuje)) {
        $baza = polaczZBaza();
        $zapytanie = $baza->prepare('DELETE FROM koszyk WHERE id_produktu = ?');
        $zapytanie->execute([(int) $pasuje[1]]);
        if ($zapytanie->rowCount() === 0) {
            odpowiedz(['blad' => 'Nie ma takiego produktu w koszyku.'], 404);
        }
        odpowiedz(['wiadomosc' => 'Usunięto z koszyka.']);
    }

    odpowiedz(['blad' => 'Nie ma takiego endpointu.'], 404);
} catch (PDOException $blad) {
    error_log($blad->getMessage());
    odpowiedz(['blad' => 'Baza danych jest niedostępna.'], 503);
}
