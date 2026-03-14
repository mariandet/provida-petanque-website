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
            excerpt,
            content,
            featured_image,
            body_image,
            external_video_url,
            is_published,
            view_count,
            created_at,
            updated_at
        FROM news_posts
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$news) {
        json_response("ERROR", "News not found");
    }

    $galleryStmt = $pdo->prepare("
        SELECT id, news_id, image_path, sort_order, created_at
        FROM news_gallery
        WHERE news_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $galleryStmt->execute([$id]);
    $gallery = $galleryStmt->fetchAll(PDO::FETCH_ASSOC);

    $news["status"] = "SUCCESS";
    $news["gallery"] = $gallery;

    echo json_encode($news);
    exit;

} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}