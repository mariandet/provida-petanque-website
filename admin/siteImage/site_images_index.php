<?php
require_once __DIR__ . "/../../auth.php";

function item($label,$web,$file){
  return ["label"=>$label,"web"=>$web,"file"=>$file];
}

$ROOT_IMAGES = "../../user/images/";
$VIDEO_FILE = "../../user/images/gallery_videos.json";

/* load videos */
$videos = ["","",""];
if(file_exists($VIDEO_FILE)){
    $tmp = json_decode(file_get_contents($VIDEO_FILE), true);
    if(is_array($tmp)){
        $videos = array_pad($tmp,3,"");
    }
}

$groups = [

"Hero Slideshow" => [
"hero_1" => item("Hero Slide 1",$ROOT_IMAGES."DSC00429.jpg",$ROOT_IMAGES."DSC00429.jpg"),
"hero_2" => item("Hero Slide 2",$ROOT_IMAGES."DSC09721.jpg",$ROOT_IMAGES."DSC09721.jpg"),
"hero_3" => item("Hero Slide 3",$ROOT_IMAGES."DSC09961.jpg",$ROOT_IMAGES."DSC09961.jpg"),
],

"Next Competition Section" => [
"next_comp_bg" => item("Background",$ROOT_IMAGES."DSC08700.JPG",$ROOT_IMAGES."DSC08700.JPG"),
],

"Gallery Images" => [
"gallery_1" => item("Gallery 1",$ROOT_IMAGES."gallery-1.jpg",$ROOT_IMAGES."gallery-1.jpg"),
"gallery_2" => item("Gallery 2",$ROOT_IMAGES."gallery-2.JPG",$ROOT_IMAGES."gallery-2.JPG"),
"gallery_3" => item("Gallery 3",$ROOT_IMAGES."gallery-3.jpg",$ROOT_IMAGES."gallery-3.jpg"),
"gallery_4" => item("Gallery 4",$ROOT_IMAGES."gallery-4.JPG",$ROOT_IMAGES."gallery-4.JPG"),
"gallery_5" => item("Gallery 5",$ROOT_IMAGES."gallery-5.jpg",$ROOT_IMAGES."gallery-5.jpg"),
"gallery_6" => item("Gallery 6",$ROOT_IMAGES."gallery-6.jpg",$ROOT_IMAGES."gallery-6.jpg"),
],

"About Page" => [
"about_founder" => item("Founder Image",$ROOT_IMAGES."ABOUT.jpg",$ROOT_IMAGES."ABOUT.jpg"),
],
// "QR_CODE"    => $baseDir."qr-payment.png",
"QR_CODE" => [
"QR_CODE" => item("QR_CODE",$ROOT_IMAGES."qr-payment.png",$ROOT_IMAGES."qr-payment.png")
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

<h3 style="grid-column:1/-1">Gallery Videos (YouTube)</h3>

<div class="img-card" style="padding:15px;grid-column:1/-1;">

<label>Video 1</label>
<input type="text" id="video1"
value="<?= htmlspecialchars($videos[0]) ?>"
placeholder="YouTube link"
style="width:100%;padding:8px;margin-bottom:10px;">

<label>Video 2</label>
<input type="text" id="video2"
value="<?= htmlspecialchars($videos[1]) ?>"
placeholder="YouTube link"
style="width:100%;padding:8px;margin-bottom:10px;">

<label>Video 3</label>
<input type="text" id="video3"
value="<?= htmlspecialchars($videos[2]) ?>"
placeholder="YouTube link"
style="width:100%;padding:8px;margin-bottom:10px;">

<button class="btn-sm btn-edit" onclick="saveVideos()">Save Videos</button>

</div>

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

async function saveVideos(){

const v1 = document.getElementById("video1").value.trim()
const v2 = document.getElementById("video2").value.trim()
const v3 = document.getElementById("video3").value.trim()

const fd = new FormData()
fd.append("video_1", v1)
fd.append("video_2", v2)
fd.append("video_3", v3)

const r = await fetch("api_gallery_video_save.php", {
  method: "POST",
  body: fd
})

const j = await r.json().catch(() => ({}))

if(j.status === "SUCCESS"){
  alert("Videos saved")
  location.reload()
}else{
  alert(j.message || "Save failed")
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