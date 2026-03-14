<?php
require_once __DIR__ . "/../../auth.php";

header("Content-Type: application/json; charset=utf-8");

function json_response($status, $message, $extra = []) {
    echo json_encode(
        array_merge([
            "status" => $status,
            "message" => $message
        ], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );
    exit;
}

function normalize_youtube_url($url) {
    $url = trim((string)$url);
    if ($url === "") {
        return "";
    }

    if (preg_match('~youtube\.com/watch\?v=([^&]+)~i', $url, $m)) {
        return "https://www.youtube.com/watch?v=" . $m[1];
    }

    if (preg_match('~youtu\.be/([^?&]+)~i', $url, $m)) {
        return "https://www.youtube.com/watch?v=" . $m[1];
    }

    if (preg_match('~youtube\.com/embed/([^?&]+)~i', $url, $m)) {
        return "https://www.youtube.com/watch?v=" . $m[1];
    }

    return $url;
}

function is_valid_youtube_url($url) {
    $url = trim((string)$url);
    if ($url === "") {
        return true;
    }

    return (bool)preg_match('~^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)/~i', $url);
}

$video1 = normalize_youtube_url($_POST["video_1"] ?? "");
$video2 = normalize_youtube_url($_POST["video_2"] ?? "");
$video3 = normalize_youtube_url($_POST["video_3"] ?? "");

$videos = [$video1, $video2, $video3];

foreach ($videos as $i => $v) {
    if (!is_valid_youtube_url($v)) {
        json_response("ERROR", "Video " . ($i + 1) . " must be a YouTube link", [
            "received" => $v
        ]);
    }
}

/* SAVE TO user/images/gallery_videos.json */
$saveFile = __DIR__ . "/../../user/images/gallery_videos.json";
$saveDir  = dirname($saveFile);

if (!is_dir($saveDir)) {
    json_response("ERROR", "Directory not found", [
        "dir" => $saveDir
    ]);
}

if (!is_writable($saveDir)) {
    json_response("ERROR", "Directory not writable", [
        "dir" => $saveDir,
        "save_file" => $saveFile
    ]);
}

$json = json_encode(
    $videos,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

if ($json === false) {
    json_response("ERROR", "JSON encode failed");
}

if (file_put_contents($saveFile, $json) === false) {
    json_response("ERROR", "Failed to save file", [
        "path" => $saveFile
    ]);
}

json_response("SUCCESS", "Videos saved successfully", [
    "path" => $saveFile,
    "videos" => $videos
]);