<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

function json_response($data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    json_response([
        "status" => "ERROR",
        "message" => "id required"
    ], 400);
}

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.competition_id,
        c.title AS comp_title,
        r.full_name,
        r.phone,
        r.email,
        r.note,
        r.is_agree_terms,
        r.proof_image_url,
        r.created_at
    FROM registrations r
    JOIN competitions c ON c.id = r.competition_id
    WHERE r.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    json_response([
        "status" => "ERROR",
        "message" => "not found"
    ], 404);
}

$row["is_agree_terms"] = (int)($row["is_agree_terms"] ?? 0);
$row["payment_status"] = !empty($row["proof_image_url"]) ? "PAID" : "NOT YET PAID";

json_response(array_merge([
    "status" => "SUCCESS",
    "message" => "OK"
], $row));