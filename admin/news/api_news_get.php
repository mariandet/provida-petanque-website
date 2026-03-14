<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

function json_response($status, $message = "", $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    json_response("ERROR", "Invalid ID");
}

$stmt = $pdo->prepare("
    SELECT
        id,
        title,
        subtitle,
        author_name,
        news_date,
        content_1,
        content_2,
        featured_image,
        body_image,
        external_video_url,
        is_published,
        view_count,
        created_at
    FROM news_posts
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    json_response("ERROR", "News not found");
}

$galleryStmt = $pdo->prepare("
    SELECT id, image_path, sort_order
    FROM news_gallery
    WHERE news_id = ?
    ORDER BY sort_order ASC, id ASC
");
$galleryStmt->execute([$id]);
$row["gallery"] = $galleryStmt->fetchAll(PDO::FETCH_ASSOC);

json_response("SUCCESS", "Loaded", $row);