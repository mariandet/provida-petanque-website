<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

$d = json_decode(file_get_contents("php://input"), true);

$id    = (int)($d["id"] ?? 0);
$title = trim($d["title"] ?? "");
$desc  = trim($d["description"] ?? "");
$date  = $d["event_date"] ?? null;

$price = trim($d["price"] ?? "");
$ccy   = trim($d["currency"] ?? "");
$open  = (int)($d["is_open"] ?? 0);

$priceVal = ($price === "") ? null : (float)$price;
$ccyVal   = ($priceVal === null) ? null : ($ccy === "" ? "USD" : $ccy);

if($id <= 0 || $title === ""){
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"Invalid data"]);
  exit;
}

$stmt = $pdo->prepare("UPDATE competitions
                       SET title=?, description=?, event_date=?, price=?, currency=?, is_open=?
                       WHERE id=?");
$stmt->execute([$title, $desc, $date ?: null, $priceVal, $ccyVal, $open, $id]);

echo json_encode(["status"=>"SUCCESS","message"=>"Updated"]);