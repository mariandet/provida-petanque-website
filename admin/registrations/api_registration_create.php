<?php
// api_registration_create.php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

$competition_id = (int)($_POST["competition_id"] ?? 0);
$full_name = trim($_POST["full_name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$note = trim($_POST["note"] ?? "");

if($competition_id<=0 || $full_name==="" || $phone===""){
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"Invalid data"]);
  exit;
}

if(empty($_FILES["proof_image"]) || $_FILES["proof_image"]["error"] !== 0){
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"Image required"]);
  exit;
}

$ext = strtolower(pathinfo($_FILES["proof_image"]["name"], PATHINFO_EXTENSION));
if(!in_array($ext,["jpg","jpeg","png","webp","gif"])){
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"Invalid image type"]);
  exit;
}

$uploadDir = __DIR__."/uploads/";
if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);

$filename = "reg_".time()."_".mt_rand(1000,9999).".".$ext;
move_uploaded_file($_FILES["proof_image"]["tmp_name"], $uploadDir.$filename);

$url = "uploads/".$filename;

$stmt = $pdo->prepare("
  INSERT INTO registrations
  (competition_id, full_name, phone, email, note, proof_image_url)
  VALUES(?,?,?,?,?,?)
");
$stmt->execute([$competition_id,$full_name,$phone,$email,$note,$url]);

echo json_encode(["status"=>"SUCCESS","id"=>$pdo->lastInsertId()]);