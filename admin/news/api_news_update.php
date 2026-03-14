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

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {
        json_response("ERROR", "Invalid JSON input");
    }

    $id = (int)($data["id"] ?? 0);
    if ($id <= 0) {
        json_response("ERROR", "Invalid ID");
    }

    $title = trim((string)($data["title"] ?? ""));
    if ($title === "") {
        json_response("ERROR", "Title is required");
    }

    $removeFeatured = (int)($data["remove_featured_image"] ?? 0);
    $removeBody = (int)($data["remove_body_image"] ?? 0);
    $removeGalleryIds = $data["remove_gallery_ids"] ?? [];

    $oldStmt = $pdo->prepare("
        SELECT id, featured_image, body_image
        FROM news_posts
        WHERE id = ?
        LIMIT 1
    ");
    $oldStmt->execute([$id]);
    $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
        json_response("ERROR", "News not found");
    }

    if ($removeFeatured === 1 && !empty($old["featured_image"])) {
        $file = realpath(__DIR__ . "/../../") . "/" . ltrim($old["featured_image"], "/");
        if (is_file($file) && !@unlink($file)) {
            json_response("ERROR", "Failed to delete featured image");
        }
    }

    if ($removeBody === 1 && !empty($old["body_image"])) {
        $file = realpath(__DIR__ . "/../../") . "/" . ltrim($old["body_image"], "/");
        if (is_file($file) && !@unlink($file)) {
            json_response("ERROR", "Failed to delete body image");
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
                FROM news_gallery
                WHERE news_id = ? AND id IN ($placeholders)
            ");
            $gStmt->execute(array_merge([$id], $removeGalleryIds));
            $galleryRows = $gStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($galleryRows as $g) {
                if (!empty($g["image_path"])) {
                    $file = realpath(__DIR__ . "/../../") . "/" . ltrim($g["image_path"], "/");
                    if (is_file($file) && !@unlink($file)) {
                        json_response("ERROR", "Failed to delete gallery image");
                    }
                }
            }

            $dStmt = $pdo->prepare("
                DELETE FROM news_gallery
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
            content_1 = :content_1,
            content_2 = :content_2,
            external_video_url = :external_video_url,
            is_published = :is_published,
            featured_image = CASE WHEN :remove_featured_image = 1 THEN NULL ELSE featured_image END,
            body_image = CASE WHEN :remove_body_image = 1 THEN NULL ELSE body_image END
        WHERE id = :id
    ");

    $stmt->execute([
        ":id" => $id,
        ":title" => $title,
        ":subtitle" => trim((string)($data["subtitle"] ?? "")),
        ":author_name" => trim((string)($data["author_name"] ?? "Admin")),
        ":news_date" => (($data["news_date"] ?? "") !== "") ? $data["news_date"] : null,
        ":content_1" => trim((string)($data["content_1"] ?? "")),
        ":content_2" => trim((string)($data["content_2"] ?? "")),
        ":external_video_url" => trim((string)($data["external_video_url"] ?? "")),
        ":is_published" => (int)($data["is_published"] ?? 0),
        ":remove_featured_image" => $removeFeatured,
        ":remove_body_image" => $removeBody
    ]);

    json_response("SUCCESS", "News updated successfully", [
        "id" => $id
    ]);

} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}