<?php
// /provida-club/admin/registrations/api_registration_update.php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

$id        = (int)($_POST["id"] ?? 0);
$full_name = trim($_POST["full_name"] ?? "");
$phone     = trim($_POST["phone"] ?? "");
$email     = trim($_POST["email"] ?? "");
$note      = trim($_POST["note"] ?? "");

if ($id <= 0) {
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"Invalid ID"]);
  exit;
}
if ($full_name === "" || $phone === "") {
  http_response_code(400);
  echo json_encode(["status"=>"ERROR","message"=>"full_name and phone required"]);
  exit;
}

$stmt = $pdo->prepare("SELECT proof_image_url FROM registrations WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  http_response_code(404);
  echo json_encode(["status"=>"ERROR","message"=>"Not found"]);
  exit;
}

$url = $row["proof_image_url"] ?? null;

/* ✅ only replace image if user uploads new file */
if (!empty($_FILES["proof_image"]) && is_uploaded_file($_FILES["proof_image"]["tmp_name"])) {

 // delete old file (if inside uploads/registrations)
if (!empty($url)) {

  $projectRoot = realpath(__DIR__ . "/../.."); // /provida-club
  $uploadsBase = realpath($projectRoot . "/uploads/");

  // support BOTH formats:
  // 1) "/provida-club/uploads/registrations/xxx.jpg"
  // 2) "/uploads/registrations/xxx.jpg"
  // 3) "uploads/registrations/xxx.jpg"
  $u = $url;

  if (str_starts_with($u, "/uploads/")) $u = substr($u, strlen("/uploads/")); // => "/uploads/registrations/..."
  if (!str_starts_with($u, "/")) $u = "/" . $u; // ensure leading "/"

  // only delete if under /uploads/registrations/
  if (str_starts_with($u, "/uploads/")) {

    $oldPath = $projectRoot . $u; // filesystem path
    $oldDir  = realpath(dirname($oldPath));

    if ($uploadsBase && $oldDir && str_starts_with($oldDir, $uploadsBase) && is_file($oldPath)) {
      @unlink($oldPath);
    }
  }
}

  // upload new
  $uploadDir = __DIR__ . "/uploads/";
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

  $ext = strtolower(pathinfo($_FILES["proof_image"]["name"], PATHINFO_EXTENSION));
  if (!in_array($ext, ["jpg","jpeg","png","webp","gif"], true)) {
    http_response_code(400);
    echo json_encode(["status"=>"ERROR","message"=>"Invalid image type"]);
    exit;
  }

  $filename = "reg_" . time() . "_" . mt_rand(1000,9999) . "." . $ext;

  if (!move_uploaded_file($_FILES["proof_image"]["tmp_name"], $uploadDir . $filename)) {
    http_response_code(500);
    echo json_encode(["status"=>"ERROR","message"=>"Upload failed"]);
    exit;
  }

  $url = "uploads/" . $filename;
}

$pdo->prepare("
  UPDATE registrations
  SET full_name=?, phone=?, email=?, note=?, proof_image_url=?
  WHERE id=?
")->execute([$full_name, $phone, $email, $note, $url, $id]);

echo json_encode(["status"=>"SUCCESS"]);