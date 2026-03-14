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
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        json_response("ERROR", "Invalid JSON input");
    }

    $title = trim((string)($data["title"] ?? ""));
    $subtitle = trim((string)($data["subtitle"] ?? ""));
    $authorName = trim((string)($data["author_name"] ?? ""));
    $newsDate = trim((string)($data["news_date"] ?? ""));
    $isPublished = (int)($data["is_published"] ?? 0);
    $excerpt = trim((string)($data["excerpt"] ?? ""));
    $content = trim((string)($data["content"] ?? ""));
    $externalVideoUrl = trim((string)($data["external_video_url"] ?? ""));

    if ($title === "") {
        json_response("ERROR", "Title is required");
    }

    if ($authorName === "") {
        $authorName = "Admin";
    }

    if ($newsDate === "") {
        $newsDate = date("Y-m-d");
    }

    $isPublished = $isPublished === 1 ? 1 : 0;

    $sql = "
        INSERT INTO news_posts (
            title,
            subtitle,
            author_name,
            news_date,
            excerpt,
            content,
            external_video_url,
            is_published,
            view_count,
            created_at
        ) VALUES (
            :title,
            :subtitle,
            :author_name,
            :news_date,
            :excerpt,
            :content,
            :external_video_url,
            :is_published,
            0,
            NOW()
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":title" => $title,
        ":subtitle" => $subtitle,
        ":author_name" => $authorName,
        ":news_date" => $newsDate,
        ":excerpt" => $excerpt,
        ":content" => $content,
        ":external_video_url" => $externalVideoUrl,
        ":is_published" => $isPublished
    ]);

    $id = (int)$pdo->lastInsertId();

    json_response("SUCCESS", "News created successfully", [
        "id" => $id
    ]);

} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}