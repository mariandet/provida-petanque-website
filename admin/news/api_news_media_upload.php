<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

function json_response($status, $message = "", $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ensure_dir(string $path): void {
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            throw new Exception("Cannot create upload directory: " . $path);
        }
    }

    if (!is_dir($path)) {
        throw new Exception("Upload path is not a directory: " . $path);
    }

    if (!is_writable($path)) {
        @chmod($path, 0755);
    }

    if (!is_writable($path)) {
        throw new Exception("Upload directory is not writable: " . $path);
    }
}

function save_file(string $tmpName, string $originalName, string $targetDir, string $prefix = ""): string {
    if ($tmpName === "" || !file_exists($tmpName)) {
        throw new Exception("Uploaded temp file not found");
    }

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png", "webp", "gif"];

    if (!in_array($ext, $allowed, true)) {
        throw new Exception("Only image files are allowed");
    }

    $safeName = $prefix . uniqid("", true) . "." . $ext;
    $fullPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($tmpName, $fullPath)) {
        throw new Exception("Failed to move uploaded file");
    }

    @chmod($fullPath, 0644);

    return $safeName;
}

try {
    $rawNewsId = $_POST["news_id"] ?? "";

    if ($rawNewsId === "" || !is_numeric($rawNewsId)) {
        json_response("ERROR", "Invalid news_id");
    }

    $newsId = (int)$rawNewsId;
    if ($newsId <= 0) {
        json_response("ERROR", "Invalid news_id");
    }

    $check = $pdo->prepare("SELECT id FROM news_posts WHERE id = ?");
    $check->execute([$newsId]);
    if (!$check->fetchColumn()) {
        json_response("ERROR", "News post not found");
    }

    // keep old directory
    $imageDir = __DIR__ . "/uploads";
    ensure_dir($imageDir);

    $featuredPath = null;
    $bodyPath = null;
    $galleryInserted = 0;

    $pdo->beginTransaction();

    if (isset($_FILES["featured_image"]) && $_FILES["featured_image"]["error"] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES["featured_image"]["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("Featured image upload failed");
        }

        $savedName = save_file(
            $_FILES["featured_image"]["tmp_name"],
            $_FILES["featured_image"]["name"],
            $imageDir,
            "featured_"
        );

        $featuredPath = "uploads/" . $savedName;
    }

    if (isset($_FILES["body_image"]) && $_FILES["body_image"]["error"] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES["body_image"]["error"] !== UPLOAD_ERR_OK) {
            throw new Exception("Body image upload failed");
        }

        $savedName = save_file(
            $_FILES["body_image"]["tmp_name"],
            $_FILES["body_image"]["name"],
            $imageDir,
            "body_"
        );

        $bodyPath = "uploads/" . $savedName;
    }

    if ($featuredPath !== null || $bodyPath !== null) {
        $fields = [];
        $params = [];

        if ($featuredPath !== null) {
            $fields[] = "featured_image = ?";
            $params[] = $featuredPath;
        }

        if ($bodyPath !== null) {
            $fields[] = "body_image = ?";
            $params[] = $bodyPath;
        }

        $params[] = $newsId;

        $sql = "UPDATE news_posts SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    if (isset($_FILES["gallery_images"]) && is_array($_FILES["gallery_images"]["name"])) {
        $count = count($_FILES["gallery_images"]["name"]);

        $realUploadCount = 0;
        for ($i = 0; $i < $count; $i++) {
            if (($_FILES["gallery_images"]["error"][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $realUploadCount++;
            }
        }

        if ($realUploadCount > 4) {
            throw new Exception("Gallery allows maximum 4 images only");
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM news_gallery WHERE news_id = ?");
        $countStmt->execute([$newsId]);
        $existingCount = (int)$countStmt->fetchColumn();

        if (($existingCount + $realUploadCount) > 4) {
            throw new Exception("Total gallery images cannot exceed 4");
        }

        for ($i = 0; $i < $count; $i++) {
            if ($_FILES["gallery_images"]["error"][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($_FILES["gallery_images"]["error"][$i] !== UPLOAD_ERR_OK) {
                throw new Exception("One gallery image upload failed");
            }

            $savedName = save_file(
                $_FILES["gallery_images"]["tmp_name"][$i],
                $_FILES["gallery_images"]["name"][$i],
                $imageDir,
                "gallery_"
            );

            $imagePath = "uploads/" . $savedName;
            $sortOrder = $existingCount + $galleryInserted + 1;

            $stmt = $pdo->prepare("
                INSERT INTO news_gallery (news_id, image_path, sort_order, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$newsId, $imagePath, $sortOrder]);

            $galleryInserted++;
        }
    }

    $pdo->commit();

    json_response("SUCCESS", "Images uploaded successfully", [
        "news_id" => $newsId,
        "featured_image" => $featuredPath,
        "body_image" => $bodyPath,
        "gallery_inserted" => $galleryInserted
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response("ERROR", $e->getMessage(), [
        "post" => $_POST,
        "files" => array_keys($_FILES),
        "upload_dir" => __DIR__ . "/uploads"
    ]);
}
?>