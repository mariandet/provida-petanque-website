<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

$input = json_decode(file_get_contents("php://input"), true);
$id = (int)($input["id"] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid ID"]);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM site_settings WHERE id = :id");
$stmt->execute([":id" => $id]);

echo json_encode([
    "status" => "SUCCESS",
    "message" => "Deleted successfully"
], JSON_UNESCAPED_UNICODE);