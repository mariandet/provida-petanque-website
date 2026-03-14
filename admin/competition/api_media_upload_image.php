<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

$competition_id = (int)($_POST["competition_id"] ?? 0);
if($competition_id <= 0){
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"Invalid competition_id"]);
  exit;
}

if(!isset($_FILES["images"])){
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"No files"]);
  exit;
}

$uploadDir = __DIR__ . "/uploads/";
if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$files = $_FILES["images"];
$count = is_array($files["name"]) ? count($files["name"]) : 0;

for($i=0; $i<$count; $i++){
  if($files["error"][$i] !== UPLOAD_ERR_OK) continue;

  $tmp = $files["tmp_name"][$i];
  $name = basename($files["name"][$i]);

  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if(!in_array($ext, ["jpg","jpeg","png","webp","gif"])) continue;

  $newName = "comp_" . $competition_id . "_" . time() . "_" . $i . "." . $ext;
  $dest = $uploadDir . $newName;

  if(move_uploaded_file($tmp, $dest)){
    $url = "uploads/" . $newName;
    $stmt = $pdo->prepare("INSERT INTO competition_media(competition_id, media_type, media_url) VALUES(?, 'IMAGE', ?)");
    $stmt->execute([$competition_id, $url]);
  }
}

echo json_encode(["status"=>"SUCCESS","message"=>"Images uploaded"]);