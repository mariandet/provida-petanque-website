<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

$id = (int)($_POST["id"] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid ID"]);
    exit;
}

$hero_title_en = trim($_POST["hero_title_en"] ?? "");
$hero_title_kh = trim($_POST["hero_title_kh"] ?? "");
$hero_subtitle_en = trim($_POST["hero_subtitle_en"] ?? "");
$hero_subtitle_kh = trim($_POST["hero_subtitle_kh"] ?? "");
$hero_description_en = trim($_POST["hero_description_en"] ?? "");
$hero_description_kh = trim($_POST["hero_description_kh"] ?? "");
$about_text_en = trim($_POST["about_text_en"] ?? "");
$about_text_kh = trim($_POST["about_text_kh"] ?? "");

$stmt = $pdo->prepare("
    UPDATE site_settings
    SET
        about_text_en = :about_text_en,
        about_text_kh = :about_text_kh,
        hero_title_en = :hero_title_en,
        hero_title_kh = :hero_title_kh,
        hero_subtitle_en = :hero_subtitle_en,
        hero_subtitle_kh = :hero_subtitle_kh,
        hero_description_en = :hero_description_en,
        hero_description_kh = :hero_description_kh
    WHERE id = :id
");

$stmt->execute([
    ":id" => $id,
    ":about_text_en" => $about_text_en,
    ":about_text_kh" => $about_text_kh,
    ":hero_title_en" => $hero_title_en,
    ":hero_title_kh" => $hero_title_kh,
    ":hero_subtitle_en" => $hero_subtitle_en,
    ":hero_subtitle_kh" => $hero_subtitle_kh,
    ":hero_description_en" => $hero_description_en,
    ":hero_description_kh" => $hero_description_kh
]);

echo json_encode([
    "status" => "SUCCESS",
    "message" => "Updated successfully"
], JSON_UNESCAPED_UNICODE);