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

    $newsId = (int)($data["news_id"] ?? 0);
    $type   = trim((string)($data["type"] ?? ""));

    if ($newsId <= 0) {
        json_response("ERROR", "Invalid news id");
    }

    if (!in_array($type, ["featured", "body"], true)) {
        json_response("ERROR", "Invalid image type");
    }

    $field = ($type === "featured") ? "featured_image" : "body_image";

    $stmt = $pdo->prepare("SELECT {$field} AS image_path FROM news_posts WHERE id = ? LIMIT 1");
    $stmt->execute([$newsId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json_response("ERROR", "News not found");
    }

    $imagePath = trim((string)($row["image_path"] ?? ""));

    if ($imagePath === "") {
        json_response("ERROR", "No image to delete");
    }

    $basePath = realpath(__DIR__ . "/../../");
    if ($basePath === false) {
        json_response("ERROR", "Base path not found");
    }

    $file = ltrim($imagePath, "/");

    if (!file_exists($file)) {
        json_response("ERROR", "Image file not found", [
            "path" => $file
        ]);
    }

    if (!is_file($file)) {
        json_response("ERROR", "Target is not a file", [
            "path" => $file
        ]);
    }

    if (!unlink($file)) {
        json_response("ERROR", "Failed to delete image file", [
            "path" => $file
        ]);
    }

    $update = $pdo->prepare("UPDATE news_posts SET {$field} = NULL WHERE id = ?");
    if (!$update->execute([$newsId])) {
        json_response("ERROR", "Image file deleted but failed to update database");
    }

    json_response("SUCCESS", "Image deleted successfully");

} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}