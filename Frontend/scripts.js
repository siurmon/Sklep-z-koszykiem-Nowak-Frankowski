fetch("/api/status")
  .then(odpowiedz => odpowiedz.json())
  .then(dane => {
    document.getElementById("wynik").textContent = dane.message;})

  .catch(() => {
    document.getElementById("wynik").textContent = "Błąd połączenia z backendem";});
