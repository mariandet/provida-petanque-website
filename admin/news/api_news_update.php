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

    if (!$data) {
        json_response("ERROR", "Invalid JSON input");
    }

    $id = (int)($data["id"] ?? 0);
    if ($id <= 0) {
        json_response("ERROR", "Invalid ID");
    }

    $title = trim($data["title"] ?? "");
    if ($title === "") {
        json_response("ERROR", "Title is required");
    }

    $removeFeatured = (int)($data["remove_featured_image"] ?? 0);
    $removeBody = (int)($data["remove_body_image"] ?? 0);
    $removeGalleryIds = $data["remove_gallery_ids"] ?? [];

    $oldStmt = $pdo->prepare("
        SELECT id, featured_image, body_image
        FROM news_posts
        WHERE id = :id
        LIMIT 1
    ");
    $oldStmt->execute([":id" => $id]);
    $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
        json_response("ERROR", "News not found");
    }

    if ($removeFeatured === 1 && !empty($old["featured_image"])) {
        $file = realpath(__DIR__ . "/../../") . "/" . ltrim($old["featured_image"], "/");
        if (is_file($file)) {
            @unlink($file);
        }
    }

    if ($removeBody === 1 && !empty($old["body_image"])) {
        $file = realpath(__DIR__ . "/../../") . "/" . ltrim($old["body_image"], "/");
        if (is_file($file)) {
            @unlink($file);
        }
    }

    if (is_array($removeGalleryIds) && count($removeGalleryIds) > 0) {
        $removeGalleryIds = array_values(array_filter(array_map("intval", $removeGalleryIds), function ($v) {
            return $v > 0;
        }));

        if (!empty($removeGalleryIds)) {
            $placeholders = implode(",", array_fill(0, count($removeGalleryIds), "?"));

            $gStmt = $pdo->prepare("
                SELECT id, image_path
                FROM news_gallery_images
                WHERE news_id = ? AND id IN ($placeholders)
            ");
            $gStmt->execute(array_merge([$id], $removeGalleryIds));
            $galleryRows = $gStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($galleryRows as $g) {
                if (!empty($g["image_path"])) {
                    $file = realpath(__DIR__ . "/../../") . "/" . ltrim($g["image_path"], "/");
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }

            $dStmt = $pdo->prepare("
                DELETE FROM news_gallery_images
                WHERE news_id = ? AND id IN ($placeholders)
            ");
            $dStmt->execute(array_merge([$id], $removeGalleryIds));
        }
    }

    $stmt = $pdo->prepare("
        UPDATE news_posts SET
            title = :title,
            subtitle = :subtitle,
            author_name = :author_name,
            news_date = :news_date,
            excerpt = :excerpt,
            content = :content,
            external_video_url = :external_video_url,
            is_published = :is_published,
            featured_image = CASE WHEN :remove_featured_image = 1 THEN NULL ELSE featured_image END,
            body_image = CASE WHEN :remove_body_image = 1 THEN NULL ELSE body_image END,
            updated_at = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        ":id" => $id,
        ":title" => $title,
        ":subtitle" => trim($data["subtitle"] ?? ""),
        ":author_name" => trim($data["author_name"] ?? "Admin"),
        ":news_date" => ($data["news_date"] ?? "") !== "" ? $data["news_date"] : null,
        ":excerpt" => trim($data["excerpt"] ?? ""),
        ":content" => trim($data["content"] ?? ""),
        ":external_video_url" => trim($data["external_video_url"] ?? ""),
        ":is_published" => (int)($data["is_published"] ?? 0),
        ":remove_featured_image" => $removeFeatured,
        ":remove_body_image" => $removeBody
    ]);

    json_response("SUCCESS", "News updated successfully", [
        "id" => $id,
        "frontend_url" => "/news-detail.php?id=" . $id
    ]);
} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}