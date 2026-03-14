<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

function json_response($status, $message, $extra = []){
    echo json_encode(
        array_merge([
            "status" => $status,
            "message" => $message
        ], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );
    exit;
}

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {
        json_response("ERROR", "Invalid JSON");
    }

    $id = (int)($data["id"] ?? 0);

    if ($id <= 0) {
        json_response("ERROR", "Invalid gallery image id");
    }

    $stmt = $pdo->prepare("
        SELECT id, image_path
        FROM news_gallery
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);



    $imagePath = trim((string)($row["image_path"] ?? ""));

    if ($imagePath === "") {
        json_response("ERROR", "No gallery image to delete");
    }

  

$file = trim($imagePath, "/");

if (!file_exists($file)) {
    json_response("ERROR", $file . " Gallery image file not found", [
        "path" => $file
    ]);
}
    if (!is_file($file)) {
        json_response("ERROR", "Target is not a file", [
            "path" => $file
        ]);
    }

    if (!unlink($file)) {
        json_response("ERROR", "Failed to delete gallery image file", [
            "path" => $file
        ]);
    }

    $delete = $pdo->prepare("DELETE FROM news_gallery WHERE id = ?");
    if (!$delete->execute([$id])) {
        json_response("ERROR", "Gallery image file deleted but failed to update database");
    }

    json_response("SUCCESS", "Gallery image deleted successfully");

} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}