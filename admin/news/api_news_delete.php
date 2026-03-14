<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

function json_response(string $status, string $message, array $extra = []): void {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        json_response("ERROR", "Invalid JSON input");
    }

    $id = (int)($data["id"] ?? 0);

    if ($id <= 0) {
        json_response("ERROR", "Invalid news ID");
    }

    $pdo->beginTransaction();

    $postStmt = $pdo->prepare("
        SELECT id, featured_image, body_image
        FROM news_posts
        WHERE id = ?
        LIMIT 1
    ");
    $postStmt->execute([$id]);
    $post = $postStmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        $pdo->rollBack();
        json_response("ERROR", "News post not found");
    }

    $galleryStmt = $pdo->prepare("
        SELECT id, image_path
        FROM news_gallery
        WHERE news_id = ?
    ");
    $galleryStmt->execute([$id]);
    $galleryRows = $galleryStmt->fetchAll(PDO::FETCH_ASSOC);

    $deleteGalleryStmt = $pdo->prepare("DELETE FROM news_gallery WHERE news_id = ?");
    $deleteGalleryStmt->execute([$id]);

    $deletePostStmt = $pdo->prepare("DELETE FROM news_posts WHERE id = ?");
    $deletePostStmt->execute([$id]);

    if ($deletePostStmt->rowCount() < 1) {
        throw new Exception("Failed to delete news post");
    }

    $pdo->commit();

    $filesToDelete = [];

    $featured = trim((string)($post["featured_image"] ?? ""));
    if ($featured !== "") {
        $filesToDelete[] = $featured;
    }

    $body = trim((string)($post["body_image"] ?? ""));
    if ($body !== "") {
        $filesToDelete[] = $body;
    }

    foreach ($galleryRows as $row) {
        $img = trim((string)($row["image_path"] ?? ""));
        if ($img !== "") {
            $filesToDelete[] = $img;
        }
    }

    foreach ($filesToDelete as $relativePath) {
        $relativePath = ltrim($relativePath, "/");
        $fullPath = __DIR__ . "/" . $relativePath;

        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    json_response("SUCCESS", "News deleted successfully", [
        "id" => $id
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response("ERROR", $e->getMessage());
}
?>