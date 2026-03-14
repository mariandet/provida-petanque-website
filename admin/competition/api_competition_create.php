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

try {
    $d = json_decode(file_get_contents("php://input"), true);

    if (!is_array($d)) {
        json_response("ERROR", "Invalid JSON input");
    }

    $title = trim((string)($d["title"] ?? ""));
    $date  = $d["event_date"] ?? null;
    $price = trim((string)($d["price"] ?? ""));
    $ccy   = trim((string)($d["currency"] ?? "USD"));
    $open  = (int)($d["is_open"] ?? 0);
    $desc  = trim((string)($d["description"] ?? ""));
    $termCondition = trim((string)($d["term_condition"] ?? ""));

    if ($title === "") {
        json_response("ERROR", "Title required");
    }

    $priceVal = ($price === "") ? null : (float)$price;
    $ccyVal   = ($priceVal === null) ? null : $ccy;
    $dateVal  = (!empty($date)) ? $date : null;
    $openVal  = ($open === 1) ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO competitions (
            title,
            description,
            term_condition,
            event_date,
            price,
            currency,
            is_open
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $title,
        $desc,
        $termCondition,
        $dateVal,
        $priceVal,
        $ccyVal,
        $openVal
    ]);

    json_response("SUCCESS", "Competition created successfully", [
        "id" => (int)$pdo->lastInsertId()
    ]);

} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}
?>