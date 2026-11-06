fetch("/api/status")
  .then(odpowiedz => odpowiedz.json())
  .then(dane => {
    document.getElementById("wynik_backend").textContent = dane.message_backend;
    document.getElementById("wynik_db").textContent = dane.message_db;
  })

  .catch(() => {
    document.getElementById("wynik_backend").textContent = "Błąd połączenia z backendem";
    document.getElementById("wynik_db").textContent = "Status Bazy nieznany";
  });
