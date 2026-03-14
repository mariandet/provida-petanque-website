<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

function json_response($status,$message,$extra=[]){
    echo json_encode(array_merge([
        "status"=>$status,
        "message"=>$message
    ],$extra),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    exit;
}

$d = json_decode(file_get_contents("php://input"), true);
$id = (int)($d["id"] ?? 0);

if ($id <= 0) {
    json_response("ERROR","Invalid ID");
}

/* get image path from DB */
$stmt = $pdo->prepare("SELECT proof_image_url FROM registrations WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    json_response("ERROR","Registration not found");
}

/* delete image file */
if (!empty($row["proof_image_url"])) {

    $uploadDir = __DIR__ . "/uploads/";
    $filePath = $uploadDir . basename($row["proof_image_url"]);

    if (file_exists($filePath)) {

        if (!unlink($filePath)) {
            json_response("ERROR","Failed to delete image",[
                "path"=>$filePath
            ]);
        }

    }
}

/* delete DB record */
$pdo->prepare("DELETE FROM registrations WHERE id=?")->execute([$id]);

json_response("SUCCESS","Registration deleted");