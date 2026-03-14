<?php
require_once __DIR__ . "/../../auth.php";

// $ROOT = __DIR__ . "/../../..images";

function item($label,$web,$file){
  return ["label"=>$label,"web"=>$web,"file"=>$file];
}

$groups = [

"Hero Slideshow" => [
"hero_1" => item("Hero Slide 1","images/DSC00429.jpg", "images/DSC00429.jpg"),
"hero_2" => item("Hero Slide 2","images/DSC09721.jpg", "images/DSC09721.jpg"),
"hero_3" => item("Hero Slide 3","images/DSC09961.jpg", "images/DSC09961.jpg"),
],

"Next Competition Section" => [
"next_comp_bg" => item("Background","images/DSC08700.JPG", "images/DSC08700.JPG"),
],

"News Preview" => [
"news_1" => item("News Image 1","images/DSC08751.JPG", "images/DSC08751.JPG"),
"news_2" => item("News Image 2","images/DSC08773.JPG", "images/DSC08773.JPG"),
"news_3" => item("News Image 3","images/DSC02662.jpg", "images/DSC02662.jpg"),
],

"Gallery Preview" => [
"gallery_1" => item("Gallery 1","images/DSC01027.jpg", "images/DSC01027.jpg"),
"gallery_2" => item("Gallery 2","images/DSC08154.JPG", "images/DSC08154.JPG"),
"gallery_3" => item("Gallery 3","images/DSC09751.jpg", "images/DSC09751.jpg"),
"gallery_4" => item("Gallery 4","images/DSC08721.JPG", "images/DSC08721.JPG"),
]

];

function imgv($web,$file){
$v = file_exists($file) ? filemtime($file) : time();
return $web."?v=".$v;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Image Management</title>
<link rel="stylesheet" href="../assets/style.css">
</head>

<body class="admin-body">

<nav class="admin-navbar">
<div class="admin-navbar__container">
<h1 class="admin-navbar__title">Image Management</h1>

<div style="display:flex;gap:10px;">
<a class="btn btn--outline" href="../admin.php">Dashboard</a>
<a class="btn btn--gold" href="../logout.php">Logout</a>
</div>
</div>
</nav>

<div class="admin-dashboard">

<div style="margin-top:14px;">
<a class="btn btn--outline" href="../admin.php">← Back</a>
</div>

<h2 style="margin:15px 0;color:var(--color-navy);">Website Images</h2>

<div class="img-grid">

<?php foreach($groups as $title=>$items): ?>

<h3 style="grid-column:1/-1"><?= $title ?></h3>

<?php foreach($items as $key=>$it): ?>

<?php $src = imgv($it["web"],$it["file"]); ?>

<div class="img-card">

<img class="img-thumb"
src="<?= $src ?>"
onclick="openImg('<?= $src ?>')">

<div class="img-meta">

<span><?= $it["label"] ?></span>

<input type="file"
style="display:none"
id="file_<?= $key ?>"
accept="image/*"
onchange="uploadImg('<?= $key ?>',this)">

<button class="btn-sm btn-edit"
onclick="document.getElementById('file_<?= $key ?>').click()">
Replace
</button>

</div>

</div>

<?php endforeach; ?>

<?php endforeach; ?>

</div>

</div>


<div id="mediaModal" class="modal">
<div class="modal__overlay" onclick="closeMedia()"></div>
<div class="modal__content" style="max-width:90%;background:#000;">
<button class="modal__close" onclick="closeMedia()" style="color:#fff;">×</button>
<div id="mediaContent"></div>
</div>
</div>


<script>

function openImg(url){
document.getElementById("mediaContent").innerHTML =
`<img src="${url}" style="max-width:90vw;max-height:80vh;">`

document.getElementById("mediaModal").classList.add("active")
}

function closeMedia(){
document.getElementById("mediaModal").classList.remove("active")
}

async function uploadImg(key,input){

const f=input.files[0]
if(!f) return

const fd=new FormData()
fd.append("target",key)
fd.append("image",f)

const r=await fetch("api_site_image_replace.php",{method:"POST",body:fd})
const j=await r.json()

if(j.status==="SUCCESS"){
location.reload()
}else{
alert(j.message)
}

}

</script>

<style>

.img-grid{
display:grid;
grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
gap:14px;
}

.img-card{
background:#fff;
border-radius:12px;
overflow:hidden;
box-shadow:0 6px 14px rgba(0,0,0,0.1);
}

.img-thumb{
width:100%;
height:150px;
object-fit:cover;
cursor:pointer;
}

.img-meta{
padding:10px;
display:flex;
justify-content:space-between;
align-items:center;
}

</style>

</body>
</html>