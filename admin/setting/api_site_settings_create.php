<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

$hero_title_en = trim($_POST["hero_title_en"] ?? "");
$hero_title_kh = trim($_POST["hero_title_kh"] ?? "");
$hero_subtitle_en = trim($_POST["hero_subtitle_en"] ?? "");
$hero_subtitle_kh = trim($_POST["hero_subtitle_kh"] ?? "");
$hero_description_en = trim($_POST["hero_description_en"] ?? "");
$hero_description_kh = trim($_POST["hero_description_kh"] ?? "");
$about_text_en = trim($_POST["about_text_en"] ?? "");
$about_text_kh = trim($_POST["about_text_kh"] ?? "");

$stmt = $pdo->prepare("
    INSERT INTO site_settings (
        about_text_en, about_text_kh,
        hero_title_en, hero_title_kh,
        hero_subtitle_en, hero_subtitle_kh,
        hero_description_en, hero_description_kh
    ) VALUES (
        :about_text_en, :about_text_kh,
        :hero_title_en, :hero_title_kh,
        :hero_subtitle_en, :hero_subtitle_kh,
        :hero_description_en, :hero_description_kh
    )
");

$stmt->execute([
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
    "id" => (int)$pdo->lastInsertId()
], JSON_UNESCAPED_UNICODE);