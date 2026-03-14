<?php
header("Content-Type: application/json; charset=utf-8");

function json_response($status,$message,$extra=[]){
echo json_encode(array_merge([
"status"=>$status,
"message"=>$message
],$extra),JSON_PRETTY_PRINT);
exit;
}

/* images folder */
$baseDir  = "../../user/images/";

/* map */
$map = [

"hero_1"       => $baseDir."DSC00429.jpg",
"hero_2"       => $baseDir."DSC09721.jpg",
"hero_3"       => $baseDir."DSC09961.jpg",

"next_comp_bg" => $baseDir."DSC08700.JPG",

"news_1"       => $baseDir."DSC08751.JPG",
"news_2"       => $baseDir."DSC08773.JPG",
"news_3"       => $baseDir."DSC02662.jpg",

"gallery_1"    => $baseDir."gallery-1.jpg",
"gallery_2"    => $baseDir."gallery-2.JPG",
"gallery_3"    => $baseDir."gallery-3.jpg",
"gallery_4"    => $baseDir."gallery-4.JPG",
"gallery_5"    => $baseDir."gallery-5.jpg",
"gallery_6"    => $baseDir."gallery-6.jpg",

"about_founder"=> $baseDir."ABOUT.jpg"
];

/* request debug info */
$key = $_POST["target"] ?? "";
$fileInfo = $_FILES["image"] ?? null;

/* folder debug */
$folderDebug = [
"path"=>$baseDir,
"exists"=>is_dir($baseDir),
"writable"=>is_writable($baseDir),
"permissions"=>is_dir($baseDir) ? substr(sprintf('%o', fileperms($baseDir)),-4) : "N/A",
"realpath"=>realpath($baseDir),
"user"=>get_current_user()
];

if($key===""){
json_response("ERROR","Missing target key",[
"received_target"=>$key,
"available_targets"=>array_keys($map),
"folder"=>$folderDebug
]);
}

if(!$fileInfo){
json_response("ERROR","Image not received",[
"post"=>$_POST,
"files"=>$_FILES,
"folder"=>$folderDebug
]);
}

if(!isset($map[$key])){
json_response("ERROR","Unknown image target",[
"received"=>$key,
"available"=>array_keys($map),
"folder"=>$folderDebug
]);
}

if(!is_dir($baseDir)){
json_response("ERROR","Images folder not found",[
"folder"=>$folderDebug
]);
}

if(!is_writable($baseDir)){
json_response("ERROR","Images folder not writable",[
"folder"=>$folderDebug
]);
}

/* upload error */
$err = $fileInfo["error"];

if($err !== UPLOAD_ERR_OK){
json_response("ERROR","Upload error",[
"upload_error"=>$err,
"file"=>$fileInfo,
"folder"=>$folderDebug
]);
}

$tmp = $fileInfo["tmp_name"];
$name = $fileInfo["name"];

if(!is_uploaded_file($tmp)){
json_response("ERROR","Temp upload file invalid",[
"tmp"=>$tmp,
"file"=>$fileInfo
]);
}

$ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));
$allowed = ["jpg","jpeg","png","webp","gif"];

if(!in_array($ext,$allowed,true)){
json_response("ERROR","Invalid file type",[
"extension"=>$ext,
"allowed"=>$allowed
]);
}

$target = $map[$key];
$targetDir = dirname($target);

if(!is_dir($targetDir)){
json_response("ERROR","Target directory missing",[
"target_dir"=>$targetDir,
"folder"=>$folderDebug
]);
}

if(!is_writable($targetDir)){
json_response("ERROR","Target directory not writable",[
"target_dir"=>$targetDir,
"permissions"=>substr(sprintf('%o', fileperms($targetDir)),-4)
]);
}

/* move */
if(!move_uploaded_file($tmp,$target)){
json_response("ERROR","move_uploaded_file failed",[
"tmp"=>$tmp,
"target"=>$target,
"target_exists"=>file_exists($target),
"target_dir"=>$targetDir,
"folder"=>$folderDebug
]);
}

json_response("SUCCESS","Image updated",[
"target_key"=>$key,
"saved_to"=>$target,
"filename"=>basename($target)
]);