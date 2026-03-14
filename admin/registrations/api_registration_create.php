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
    $competition_id = (int)($_POST["competition_id"] ?? 0);
    $full_name = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $note = trim($_POST["note"] ?? "");
    $is_agree_terms = (int)($_POST["is_agree_terms"] ?? 0);

    if ($competition_id <= 0 || $full_name === "" || $phone === "") {
        json_response("ERROR", "Invalid data");
    }

    $checkComp = $pdo->prepare("SELECT id FROM competitions WHERE id = ? LIMIT 1");
    $checkComp->execute([$competition_id]);
    if (!$checkComp->fetchColumn()) {
        json_response("ERROR", "Competition not found");
    }

    $proofImageUrl = null;

    if (isset($_FILES["proof_image"]) && $_FILES["proof_image"]["error"] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES["proof_image"]["error"] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES["proof_image"]["name"], PATHINFO_EXTENSION));
        $allowedExt = ["jpg", "jpeg", "png", "webp", "gif"];

        if (in_array($ext, $allowedExt, true)) {
            $uploadDir = __DIR__ . "/uploads/";

            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            if (is_writable($uploadDir)) {
                $fileName = "reg_" . time() . "_" . mt_rand(1000, 9999) . "." . $ext;
                $dest = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES["proof_image"]["tmp_name"], $dest)) {
                    $proofImageUrl = "uploads/" . $fileName;
                }
            }
        }
    }
}
    $stmt = $pdo->prepare("
        INSERT INTO registrations
        (
            competition_id,
            full_name,
            phone,
            email,
            note,
            is_agree_terms,
            proof_image_url,
            created_at
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, NOW()
        )
    ");

    $stmt->execute([
        $competition_id,
        $full_name,
        $phone,
        $email,
        $note,
        $is_agree_terms === 1 ? 1 : 0,
        $proofImageUrl
    ]);

    json_response("SUCCESS", "Registration created successfully", [
        "id" => (int)$pdo->lastInsertId(),
        "proof_image_url" => $proofImageUrl,
        "payment_status" => $proofImageUrl ? "PAID" : "NOT YET PAID"
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    json_response("ERROR", $e->getMessage());
}