<?php
// /provida-club/admin/registrations/api_registration_get.php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

$id = (int)($_GET["id"] ?? 0);
if($id <= 0){ http_response_code(400); echo json_encode(["message"=>"id required"]); exit; }

$stmt = $pdo->prepare("
  SELECT r.id, c.title AS comp_title, r.full_name, r.phone, r.email, r.note, r.proof_image_url, r.created_at
  FROM registrations r
  JOIN competitions c ON c.id = r.competition_id
  WHERE r.id=?
  LIMIT 1
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row){ http_response_code(404); echo json_encode(["message"=>"not found"]); exit; }

echo json_encode($row);