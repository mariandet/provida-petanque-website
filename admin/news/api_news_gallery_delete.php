<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json");

function json_response($status, $message = "", $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));
    exit;
}

try {
    $data = json_decode(file_get_contents("php://input"), true);

    $id = (int)($data["id"] ?? 0);
    if ($id <= 0) {
        json_response("ERROR", "Invalid gallery ID");
    }

    $stmt = $pdo->prepare("DELETE FROM news_gallery WHERE id = ?");
    $stmt->execute([$id]);

    json_response("SUCCESS", "Gallery image deleted successfully");
} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}