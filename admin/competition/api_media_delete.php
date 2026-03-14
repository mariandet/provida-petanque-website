<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=utf-8");

function json_response($status,$message,$extra=[]){
    echo json_encode(array_merge([
        "status"=>$status,
        "message"=>$message
    ],$extra),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = (int)($data["id"] ?? 0);

if($id <= 0){
    json_response("ERROR","Invalid id");
}

$stmt = $pdo->prepare("SELECT media_url FROM competition_media WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row){
    json_response("ERROR","Media not found");
}

/* build path */
$filePath = __DIR__ . "/" . $row["media_url"];

/* DEBUG INFO */
if(!file_exists($filePath)){
    json_response("ERROR","File not found",[
        "file_from_db"=>$row["media_url"],
        "full_path"=>$filePath,
        "dir"=>__DIR__
    ]);
}

/* try delete */
$result = unlink($filePath);

if(!$result){
    json_response("ERROR","unlink failed",[
        "path"=>$filePath,
        "permission"=>is_writable(dirname($filePath))
    ]);
}

/* delete db */
$pdo->prepare("DELETE FROM competition_media WHERE id=?")->execute([$id]);

json_response("SUCCESS","Deleted",[
    "deleted"=>$filePath
]);