<?php
// api_registration_delete.php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

$d = json_decode(file_get_contents("php://input"), true);
$id = (int)($d["id"] ?? 0);

if($id<=0){
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"Invalid ID"]);
  exit;
}

$stmt = $pdo->prepare("SELECT proof_image_url FROM registrations WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row){
  http_response_code(404);
  echo json_encode(["status"=>"ERROR","message"=>"Not found"]);
  exit;
}

$url = $row["proof_image_url"];

if($url && str_starts_with($url,"/provida-club/uploads/")){
  $rel = str_replace("/provida-club","",$url);
  $path = __DIR__."/../..".$rel;
  if(is_file($path)) @unlink($path);
}

$pdo->prepare("DELETE FROM registrations WHERE id=?")->execute([$id]);

echo json_encode(["status"=>"SUCCESS"]);