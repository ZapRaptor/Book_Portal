<?php

$pdo = new PDO(
    "pgsql:host=localhost;port=5432;dbname=book_portal_db",
    "YOUR_USERNAME",
    "YOUR_PASSWORD",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);