<?php

// Polaczenie z PostgreSQL - dane logowania pobieramy ze zmiennych srodowiskowych
function polaczZBaza() {
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '5432';
    $nazwa = getenv('DB_NAME') ?: 'sklep';
    $user = getenv('DB_USER') ?: 'sklep';
    $haslo = getenv('DB_PASSWORD') ?: '';

    return new PDO(
        "pgsql:host=$host;port=$port;dbname=$nazwa",
        $user,
        $haslo,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}
