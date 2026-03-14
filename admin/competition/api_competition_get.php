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
    $id = (int)($_GET["id"] ?? 0);

    if ($id <= 0) {
        json_response("ERROR", "Invalid ID");
    }

    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            description,
            term_condition,
            event_date,
            price,
            currency,
            is_open
        FROM competitions
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $competition = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$competition) {
        json_response("ERROR", "Not found");
    }

    $m = $pdo->prepare("
        SELECT
            id,
            media_type,
            media_url
        FROM competition_media
        WHERE competition_id = ?
        ORDER BY id DESC
    ");
    $m->execute([$id]);
    $media = $m->fetchAll(PDO::FETCH_ASSOC);

    json_response("SUCCESS", "Competition loaded", [
        "data" => $competition,
        "media" => $media
    ]);

} catch (Throwable $e) {
    json_response("ERROR", $e->getMessage());
}
?>