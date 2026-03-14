<?php
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=provida_club;charset=utf8mb4",
        "root",
        "12345678",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}