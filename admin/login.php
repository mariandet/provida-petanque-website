<?php
session_start();
require_once "../config/db.php";

header("Content-Type: application/json");

$response = [
    "method" => $_SERVER["REQUEST_METHOD"],
    "status" => "UNKNOWN",
    "message" => ""
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    $response["status"] = "ERROR";
    $response["message"] = "Method not allowed";
    echo json_encode($response);
    exit;
}

$d = json_decode(file_get_contents("php://input"), true);

$u = trim($d["username"] ?? "");
$p = $d["password"] ?? "";

$stmt = $pdo->prepare("SELECT id FROM users WHERE username=? AND password=? LIMIT 1");
$stmt->execute([$u, $p]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$row){
    http_response_code(401);
    $response["status"] = "ERROR";
    $response["message"] = "Invalid username or password";
    echo json_encode($response);
    exit;
}

session_regenerate_id(true);
$_SESSION["uid"] = (int)$row["id"];

$response["status"] = "SUCCESS";
$response["message"] = "Login OK";
echo json_encode($response);