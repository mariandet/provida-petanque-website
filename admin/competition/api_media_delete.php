<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

$d = json_decode(file_get_contents("php://input"), true);
$id = (int)($d["id"] ?? 0);

if($id <= 0){
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"Invalid id"]);
  exit;
}

// Get file info
$stmt = $pdo->prepare("SELECT media_type, media_url FROM competition_media WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row){
  http_response_code(404);
  echo json_encode(["status"=>"ERROR","message"=>"Media not found"]);
  exit;
}

// DELETE physical file (IMAGE + VIDEO)
if(!empty($row["media_url"])){

  // remove domain prefix if exists
  $relativePath = str_replace("/provida-club", "", $row["media_url"]);

  // go to project root safely
  $fullPath = realpath(__DIR__ . "/../../..") . $relativePath;

  if(is_file($fullPath)){
      @unlink($fullPath);
  }
}

// Delete database record
$pdo->prepare("DELETE FROM competition_media WHERE id=?")->execute([$id]);

echo json_encode([
  "status"=>"SUCCESS",
  "message"=>"Deleted"
]);