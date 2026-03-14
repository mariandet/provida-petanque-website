<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

function json_response($status, $message, $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));
    exit;
}

try {

    $id = (int)($_POST["id"] ?? 0);
    $full_name = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $note = trim($_POST["note"] ?? "");
    $is_agree_terms = (int)($_POST["is_agree_terms"] ?? 0);

    if ($id <= 0) {
        json_response("ERROR", "Invalid ID");
    }

    if ($full_name === "" || $phone === "") {
        json_response("ERROR", "full_name and phone required");
    }

    /* get existing record */
    $stmt = $pdo->prepare("
        SELECT proof_image_url
        FROM registrations
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        json_response("ERROR", "Registration not found");
    }

    $proofImageUrl = $row["proof_image_url"] ?? null;

    /* check if new image uploaded */
    if (isset($_FILES["proof_image"]) && $_FILES["proof_image"]["error"] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES["proof_image"]["error"] !== UPLOAD_ERR_OK) {
            json_response("ERROR", "Image upload failed");
        }

        $ext = strtolower(pathinfo($_FILES["proof_image"]["name"], PATHINFO_EXTENSION));

        if (!in_array($ext, ["jpg","jpeg","png","webp","gif"], true)) {
            json_response("ERROR", "Invalid image type");
        }

        $uploadDir = __DIR__ . "/uploads/";

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                json_response("ERROR", "Cannot create upload folder");
            }
        }

        if (!is_writable($uploadDir)) {
            json_response("ERROR", "Upload folder is not writable");
        }

        /* delete old image */
        if (!empty($proofImageUrl)) {

            $oldFile = $uploadDir . basename($proofImageUrl);

            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        /* save new image */
        $filename = "reg_" . time() . "_" . mt_rand(1000,9999) . "." . $ext;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES["proof_image"]["tmp_name"], $destination)) {
            json_response("ERROR", "Failed to save uploaded image");
        }

        $proofImageUrl = "uploads/" . $filename;
    }

    /* update database */
    $update = $pdo->prepare("
        UPDATE registrations
        SET
            full_name = ?,
            phone = ?,
            email = ?,
            note = ?,
            is_agree_terms = ?,
            proof_image_url = ?
        WHERE id = ?
    ");

    $update->execute([
        $full_name,
        $phone,
        $email,
        $note,
        $is_agree_terms === 1 ? 1 : 0,
        $proofImageUrl,
        $id
    ]);

    json_response("SUCCESS", "Registration updated successfully", [
        "proof_image_url" => $proofImageUrl,
        "payment_status" => $proofImageUrl ? "PAID" : "NOT YET PAID"
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    json_response("ERROR", $e->getMessage());
}