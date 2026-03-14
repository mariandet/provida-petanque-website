<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

function json_response($status, $message = "") {
    echo json_encode([
        "status" => $status,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {

    $d = json_decode(file_get_contents("php://input"), true);

    if (!is_array($d)) {
        json_response("ERROR", "Invalid JSON");
    }

    $id    = (int)($d["id"] ?? 0);
    $title = trim((string)($d["title"] ?? ""));
    $desc  = trim((string)($d["description"] ?? ""));
    $term  = trim((string)($d["term_condition"] ?? ""));
    $date  = $d["event_date"] ?? null;

    $price = trim((string)($d["price"] ?? ""));
    $ccy   = trim((string)($d["currency"] ?? ""));
    $open  = (int)($d["is_open"] ?? 0);

    if ($id <= 0 || $title === "") {
        json_response("ERROR", "Invalid data");
    }

    $priceVal = ($price === "") ? null : (float)$price;
    $ccyVal   = ($priceVal === null) ? null : ($ccy === "" ? "USD" : $ccy);
    $dateVal  = (!empty($date)) ? $date : null;
    $openVal  = ($open === 1) ? 1 : 0;

    $stmt = $pdo->prepare("
        UPDATE competitions
        SET
            title = ?,
            description = ?,
            term_condition = ?,
            event_date = ?,
            price = ?,
            currency = ?,
            is_open = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $title,
        $desc,
        $term,
        $dateVal,
        $priceVal,
        $ccyVal,
        $openVal,
        $id
    ]);

    json_response("SUCCESS", "Competition updated");

} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}
?>