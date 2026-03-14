<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=provida_club;charset=utf8mb4",
        "root",
        ""
    );

    echo "✅ Database connected successfully!";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}