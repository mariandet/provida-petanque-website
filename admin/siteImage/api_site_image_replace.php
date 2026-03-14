<?php

header("Content-Type: application/json");

$map = [

"hero_1"=>"images/DSC00429.jpg",
"hero_2"=>"images/DSC09721.jpg",
"hero_3"=>"images/DSC09961.jpg",

"next_comp_bg"=>"images/DSC08700.JPG",

"news_1"=>"images/DSC08751.JPG",
"news_2"=>"images/DSC08773.JPG",
"news_3"=>"images/DSC02662.jpg",

"gallery_1"=>"images/DSC01027.jpg",
"gallery_2"=>"images/DSC08154.JPG",
"gallery_3"=>"images/DSC09751.jpg",
"gallery_4"=>"images/DSC08721.JPG"

];

$key = $_POST["target"] ?? null;

if(!$key || !isset($_FILES["image"])){
echo json_encode(["status"=>"ERROR","message"=>"Invalid request"]);
exit;
}

if(!isset($map[$key])){
echo json_encode(["status"=>"ERROR","message"=>"Unknown image"]);
exit;
}

$tmp = $_FILES["image"]["tmp_name"];

if(!move_uploaded_file($tmp,$map[$key])){
echo json_encode(["status"=>"ERROR","message"=>"Upload failed"]);
exit;
}

echo json_encode(["status"=>"SUCCESS"]);