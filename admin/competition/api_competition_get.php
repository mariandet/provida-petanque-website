<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

$id = (int)($_GET["id"] ?? 0);

if($id <= 0){
    http_response_code(400);
    echo json_encode(["status"=>"ERROR","message"=>"Invalid ID"]);
    exit;
}

/* Get competition */
$stmt = $pdo->prepare("
    SELECT id,title,description,event_date,price,currency,is_open
    FROM competitions
    WHERE id=?
");
$stmt->execute([$id]);
$competition = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$competition){
    http_response_code(404);
    echo json_encode(["status"=>"ERROR","message"=>"Not found"]);
    exit;
}

/* Get media */
$m = $pdo->prepare("
    SELECT id,media_type,media_url
    FROM competition_media
    WHERE competition_id=?
    ORDER BY id DESC
");
$m->execute([$id]);
$media = $m->fetchAll(PDO::FETCH_ASSOC);

/* Return JSON */
echo json_encode([
    "status" => "SUCCESS",
    "data"   => $competition,
    "media"  => $media
]);