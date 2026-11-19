const pole = id => document.getElementById(id);

// Wszystkie zapytania do PHP. Gdy Nginx zwroci HTML zamiast JSON (np. blad 502),
// pokazujemy normalny komunikat, a nie blad "Unexpected token <".
async function api(adres, opcje = {}) {
    let odpowiedz;
    try {
        odpowiedz = await fetch(adres, {cache: 'no-store', ...opcje});
    } catch {
        throw new Error('Nie można połączyć się z backendem.');
    }

    if (!(odpowiedz.headers.get('content-type') || '').includes('application/json')) {
        throw new Error('Backend nie działa lub nie odpowiada poprawnie.');
    }

    let dane;
    try {
        dane = await odpowiedz.json();
    } catch {
        throw new Error('Backend zwrócił nieprawidłowe dane.');
    }

    if (!odpowiedz.ok) throw new Error(dane.blad || 'Błąd serwera.');
    return dane;
}

function ustawStatus(id, tekst, stan) {
    pole(id).textContent = tekst;
    pole(id).className = 'status ' + stan;
}

async function sprawdzStatus() {
    try {
        const dane = await api('/api/status');
        ustawStatus('wynik_backend', 'Backend działa', 'ok');
        ustawStatus('wynik_db', dane.database ? 'Baza danych działa' : 'Baza danych nie działa',
                    dane.database ? 'ok' : 'blad');
    } catch {
        ustawStatus('wynik_backend', 'Backend nie działa', 'blad');
        ustawStatus('wynik_db', 'Nie można odczytać stanu bazy danych', 'nieznany');
    }
}

function pokazBlad(id, komunikat) {
    pole(id).textContent = komunikat;
    pole(id).className = 'komunikat_bledu';
}

function pokazListe(id, elementy, opis, podpis, akcja) {
    const miejsce = pole(id);
    miejsce.className = '';
    miejsce.replaceChildren();
    if (!elementy.length) miejsce.textContent = 'Brak produktów.';

    for (const produkt of elementy) {
        const wiersz = document.createElement('div');
        wiersz.className = 'wiersz';
        const tekst = document.createElement('span');
        tekst.textContent = opis(produkt);
        const przycisk = document.createElement('button');
        przycisk.textContent = podpis;
        przycisk.onclick = () => akcja(produkt.id);
        wiersz.append(tekst, przycisk);
        miejsce.append(wiersz);
    }
}

async function pokazProdukty() {
    try {
        const produkty = await api('/api/produkty');
        pokazListe('produkty', produkty, p => `${p.nazwa} — ${Number(p.cena).toFixed(2)} zł`,
                   'Do koszyka', id => zmienKoszyk('/api/koszyk', 'POST', {id_produktu: id}));
    } catch (blad) {
        pokazBlad('produkty', 'Nie można wczytać produktów. ' + blad.message);
    }
}

async function pokazKoszyk() {
    try {
        const dane = await api('/api/koszyk');
        pokazListe('koszyk', dane.produkty, p => `${p.nazwa} × ${p.ilosc} — ${Number(p.suma).toFixed(2)} zł`,
                   'Usuń', id => zmienKoszyk('/api/koszyk/' + id, 'DELETE'));
        pole('razem').textContent = Number(dane.razem).toFixed(2);
    } catch (blad) {
        pokazBlad('koszyk', 'Nie można wczytać koszyka. ' + blad.message);
        pole('razem').textContent = '--';
    }
}

async function zmienKoszyk(adres, metoda, dane) {
    try {
        await api(adres, {
            method: metoda,
            headers: {'Content-Type': 'application/json'},
            body: dane ? JSON.stringify(dane) : undefined
        });
        pole('komunikat').textContent = '';
        await pokazKoszyk();
    } catch (blad) {
        pole('komunikat').textContent = blad.message;
    }
}

pole('formularz').onsubmit = async function (zdarzenie) {
    zdarzenie.preventDefault();
    try {
        await api('/api/produkty', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({nazwa: pole('nazwa').value, cena: Number(pole('cena').value)})
        });
        this.reset();
        pole('komunikat').textContent = 'Produkt dodany.';
        await pokazProdukty();
    } catch (blad) {
        pole('komunikat').textContent = blad.message;
    }
};

// Przycisk odswieza status, produkty oraz koszyk i pokazuje godzine sprawdzenia.
async function odswiezWszystko() {
    const przycisk = pole('odswiez');
    przycisk.disabled = true;
    przycisk.textContent = 'Sprawdzanie...';
    pole('czas_statusu').textContent = 'Sprawdzanie połączenia...';

    await Promise.all([sprawdzStatus(), pokazProdukty(), pokazKoszyk()]);

    pole('czas_statusu').textContent = 'Ostatnie sprawdzenie: ' + new Date().toLocaleTimeString('pl-PL');
    przycisk.textContent = 'Odśwież status';
    przycisk.disabled = false;
}

pole('odswiez').onclick = odswiezWszystko;
odswiezWszystko();
