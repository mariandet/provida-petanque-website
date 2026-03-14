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

function make_slug($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        json_response("ERROR", "Invalid JSON input");
    }

    $title = trim($data["title"] ?? "");
    if ($title === "") {
        json_response("ERROR", "Title is required");
    }

    $slug = trim($data["slug"] ?? "");
    if ($slug === "") {
        $slug = make_slug($title);
    }

    $check = $pdo->prepare("SELECT COUNT(*) FROM news_posts WHERE slug = ?");
    $check->execute([$slug]);
    if ((int)$check->fetchColumn() > 0) {
        $slug .= "-" . time();
    }

    $stmt = $pdo->prepare("
        INSERT INTO news_posts (
            title, subtitle, slug, category, tags, author_name,
            news_date, excerpt, content, featured_image,
            external_video_url, meta_title, meta_description,
            allow_comments, is_published, view_count, created_at, updated_at
        ) VALUES (
            :title, :subtitle, :slug, :category, :tags, :author_name,
            :news_date, :excerpt, :content, NULL,
            :external_video_url, :meta_title, :meta_description,
            :allow_comments, :is_published, 0, NOW(), NOW()
        )
    ");

    $stmt->execute([
        ":title" => $title,
        ":subtitle" => trim($data["subtitle"] ?? ""),
        ":slug" => $slug,
        ":category" => trim($data["category"] ?? ""),
        ":tags" => trim($data["tags"] ?? ""),
        ":author_name" => trim($data["author_name"] ?? "Admin"),
        ":news_date" => ($data["news_date"] ?? "") !== "" ? $data["news_date"] : null,
        ":excerpt" => trim($data["excerpt"] ?? ""),
        ":content" => trim($data["content"] ?? ""),
        ":external_video_url" => trim($data["external_video_url"] ?? ""),
        ":meta_title" => trim($data["meta_title"] ?? ""),
        ":meta_description" => trim($data["meta_description"] ?? ""),
        ":allow_comments" => (int)($data["allow_comments"] ?? 0),
        ":is_published" => (int)($data["is_published"] ?? 0)
    ]);

    $id = (int)$pdo->lastInsertId();

    json_response("SUCCESS", "News created successfully", [
        "id" => $id,
        "slug" => $slug,
        "frontend_url" => "/news-detail.php?slug=" . urlencode($slug)
    ]);
} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}