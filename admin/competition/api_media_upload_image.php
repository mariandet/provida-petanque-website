<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

function json_response($status, $message = "", $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

try {
    $competitionId = (int)($_POST["competition_id"] ?? 0);

    if ($competitionId <= 0) {
        json_response("ERROR", "Invalid competition_id", [
            "post" => $_POST,
            "files" => array_keys($_FILES)
        ]);
    }

    $checkStmt = $pdo->prepare("SELECT id FROM competitions WHERE id = ? LIMIT 1");
    $checkStmt->execute([$competitionId]);
    if (!$checkStmt->fetchColumn()) {
        json_response("ERROR", "Competition not found", [
            "competition_id" => $competitionId
        ]);
    }

    if (!isset($_FILES["images"])) {
        json_response("ERROR", "No images uploaded", [
            "files" => $_FILES
        ]);
    }

    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            json_response("ERROR", "Cannot create upload directory", [
                "upload_dir" => $uploadDir
            ]);
        }
    }

    if (!is_writable($uploadDir)) {
        json_response("ERROR", "Upload directory is not writable", [
            "upload_dir" => $uploadDir
        ]);
    }

    $files = $_FILES["images"];
    $allowedExt = ["jpg", "jpeg", "png", "webp", "gif"];
    $uploaded = [];
    $errors = [];

    if (!is_array($files["name"])) {
        json_response("ERROR", "Invalid images[] structure", [
            "images" => $files
        ]);
    }

    $pdo->beginTransaction();

    for ($i = 0; $i < count($files["name"]); $i++) {
        $name = $files["name"][$i] ?? "";
        $tmpName = $files["tmp_name"][$i] ?? "";
        $error = $files["error"][$i] ?? UPLOAD_ERR_NO_FILE;
        $size = $files["size"][$i] ?? 0;

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = [
                "file" => $name,
                "error" => "Upload error code: " . $error
            ];
            continue;
        }

        if ($tmpName === "" || !file_exists($tmpName)) {
            $errors[] = [
                "file" => $name,
                "error" => "Temporary uploaded file not found"
            ];
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            $errors[] = [
                "file" => $name,
                "error" => "Invalid file type"
            ];
            continue;
        }

        $newName = "comp_" . $competitionId . "_" . date("YmdHis") . "_" . $i . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $destination = $uploadDir . $newName;
        $mediaUrl = "uploads/" . $newName;

        if (!move_uploaded_file($tmpName, $destination)) {
            $errors[] = [
                "file" => $name,
                "error" => "Failed to move uploaded file",
                "destination" => $destination
            ];
            continue;
        }

        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO competition_media (competition_id, media_type, media_url)
                VALUES (?, 'IMAGE', ?)
            ");
            $insertStmt->execute([$competitionId, $mediaUrl]);

            $uploaded[] = [
                "original_name" => $name,
                "saved_name" => $newName,
                "media_url" => $mediaUrl,
                "size" => $size
            ];
        } catch (Throwable $dbError) {
            @unlink($destination);
            $errors[] = [
                "file" => $name,
                "error" => "DB insert failed: " . $dbError->getMessage()
            ];
        }
    }

    if (count($uploaded) === 0) {
        $pdo->rollBack();
        json_response("ERROR", "No image was saved", [
            "uploaded" => $uploaded,
            "errors" => $errors,
            "competition_id" => $competitionId,
            "upload_dir" => $uploadDir
        ]);
    }

    $pdo->commit();

    json_response("SUCCESS", "Images uploaded successfully", [
        "competition_id" => $competitionId,
        "uploaded_count" => count($uploaded),
        "uploaded" => $uploaded,
        "errors" => $errors
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response("ERROR", $e->getMessage(), [
        "post" => $_POST,
        "files_keys" => array_keys($_FILES),
        "upload_dir" => __DIR__ . "/uploads/"
    ]);
}
?>