<?php
// /provida-club/admin/api_media_upload_video.php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

$competition_id = (int)($_POST["competition_id"] ?? 0);
if($competition_id <= 0){
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"competition_id required"]);
  exit;
}

if(empty($_FILES["videos"])){
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"videos[] required"]);
  exit;
}

$uploadDir = __DIR__ . "/uploads/";
if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$names = $_FILES["videos"]["name"];
$tmp   = $_FILES["videos"]["tmp_name"];
$err   = $_FILES["videos"]["error"];

for($i=0; $i<count($names); $i++){
  if($err[$i] !== UPLOAD_ERR_OK) continue;

  $ext = strtolower(pathinfo($names[$i], PATHINFO_EXTENSION));
  if(!in_array($ext, ["mp4","webm","mov"])) continue;

  $new = "comp_{$competition_id}_video_" . time() . "_{$i}." . $ext;
  if(!move_uploaded_file($tmp[$i], $uploadDir.$new)) continue;

  $url = "uploads/".$new;

  $stmt = $pdo->prepare("
    INSERT INTO competition_media(competition_id, media_type, media_url)
    VALUES(?, 'VIDEO', ?)
  ");
  $stmt->execute([$competition_id, $url]);
}

echo json_encode(["status"=>"SUCCESS","message"=>"Video uploaded"]);