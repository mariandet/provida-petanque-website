<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid ID"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM site_settings WHERE id = :id LIMIT 1");
$stmt->execute([":id" => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(["message" => "Setting not found"]);
    exit;
}

echo json_encode($row, JSON_UNESCAPED_UNICODE);