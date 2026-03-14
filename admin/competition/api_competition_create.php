<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json");

$d = json_decode(file_get_contents("php://input"), true);

$title = trim($d["title"] ?? "");
$date  = $d["event_date"] ?? null;
$price = trim($d["price"] ?? "");
$ccy   = trim($d["currency"] ?? "USD");
$open  = (int)($d["is_open"] ?? 0);
$desc  = trim($d["description"] ?? "");

if($title === ""){
  echo json_encode(["status"=>"ERROR","message"=>"Title required"]);
  exit;
}

$priceVal = ($price === "") ? null : (float)$price;
$ccyVal   = ($priceVal === null) ? null : $ccy;

$stmt = $pdo->prepare("
  INSERT INTO competitions(title,description,event_date,price,currency,is_open)
  VALUES(?,?,?,?,?,?)
");

$stmt->execute([$title,$desc,$date ?: null,$priceVal,$ccyVal,$open]);

echo json_encode([
  "status"=>"SUCCESS",
  "id"=>$pdo->lastInsertId()
]);