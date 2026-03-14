<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json");

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = (int)($data["id"] ?? 0);

    if ($id <= 0) {
        echo json_encode(["status" => "ERROR", "message" => "Invalid media ID"]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM news_media WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(["status" => "SUCCESS", "message" => "Media deleted successfully"]);
} catch (Throwable $e) {
    echo json_encode(["status" => "ERROR", "message" => $e->getMessage()]);
}