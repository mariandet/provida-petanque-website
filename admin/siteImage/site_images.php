<?php
require_once __DIR__ . "/../../auth.php";

/**
 * Adjust these paths to your project structure:
 * - $web: path used in <img src="">
 * - $file: real filesystem path used for filemtime + replacing
 */
function makeItem($label, $web, $file){
  return ["label"=>$label, "web"=>$web, "file"=>$file];
}

$ROOT_IMAGES = __DIR__ . "/../../../images";  // <-- adjust if needed

$groups = [
  "Hero Slideshow" => [
    "hero_1" => makeItem("Hero Slide 1", "/images/hero-1.jpg", $ROOT_IMAGES."/hero-1.jpg"),
    "hero_2" => makeItem("Hero Slide 2", "/images/hero-2.jpg", $ROOT_IMAGES."/hero-2.jpg"),
    "hero_3" => makeItem("Hero Slide 3", "/images/hero-3.jpg", $ROOT_IMAGES."/hero-3.jpg"),
  ],

  "Next Competition" => [
    "next_comp_bg" => makeItem("Section Background", "/images/next-competition-bg.jpg", $ROOT_IMAGES."/next-competition-bg.jpg"),
  ],

  "News Preview" => [
    "news_1" => makeItem("News Image 1", "/images/news-1.jpg", $ROOT_IMAGES."/news-1.jpg"),
    "news_2" => makeItem("News Image 2", "/images/news-2.jpg", $ROOT_IMAGES."/news-2.jpg"),
    "news_3" => makeItem("News Image 3", "/images/news-3.jpg", $ROOT_IMAGES."/news-3.jpg"),
  ],

  "Gallery Preview" => [
    "gallery_1" => makeItem("Gallery Image 1", "/images/gallery-1.jpg", $ROOT_IMAGES."/gallery-1.jpg"),
    "gallery_2" => makeItem("Gallery Image 2", "/images/gallery-2.jpg", $ROOT_IMAGES."/gallery-2.jpg"),
    "gallery_3" => makeItem("Gallery Image 3", "/images/gallery-3.jpg", $ROOT_IMAGES."/gallery-3.jpg"),
    "gallery_4" => makeItem("Gallery Image 4", "/images/gallery-4.jpg", $ROOT_IMAGES."/gallery-4.jpg"),
  ],
];

function imgv($webPath, $filePath){
  $v = file_exists($filePath) ? filemtime($filePath) : time();
  return $webPath . "?v=" . $v;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Site Images</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    .toast{
      display:none;
      margin:12px 0;
      background:#28a745;color:#fff;
      padding:12px 18px;border-radius:10px;
      box-shadow:0 8px 20px rgba(0,0,0,.15);
      font-weight:600;
    }
    .section-title{
      margin:18px 0 8px;
      color:var(--color-navy);
      font-size:1.1rem;
      font-weight:800;
    }
    .section-desc{
      margin:-2px 0 12px;
      color:var(--color-text-light);
      font-size:.9rem;
    }
    .img-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));
      gap:14px;
    }
    .img-card{
      background:#fff;
      border-radius:14px;
      box-shadow:0 10px 24px rgba(0,0,0,.08);
      overflow:hidden;
      position:relative;
    }
    .img-thumb{
      width:100%;
      height:150px;
      background:#111;
      display:block;
      object-fit:cover;
      cursor:zoom-in;
    }
    .img-meta{
      padding:10px 12px 12px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
    }
    .img-label{
      font-weight:700;
      color:var(--color-navy);
      font-size:.95rem;
      line-height:1.2;
    }
    .img-path{
      font-size:.78rem;
      color:var(--color-text-light);
      margin-top:3px;
    }
    .replace-btn{
      width:38px;height:38px;
      border-radius:10px;
      border:1px solid rgba(0,0,0,.12);
      background:#fff;
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      transition:.15s transform, .15s box-shadow;
    }
    .replace-btn:hover{
      transform:translateY(-1px);
      box-shadow:0 10px 18px rgba(0,0,0,.10);
    }
    .replace-btn svg{ width:18px; height:18px; }

    /* preview modal */
    .modal{ display:none; position:fixed; inset:0; z-index:9999; }
    .modal.active{ display:block; }
    .modal__overlay{ position:absolute; inset:0; background:rgba(0,0,0,.55); }
    .modal__content{
      position:relative;
      margin:4vh auto;
      background:#000;
      max-width:90%;
      border-radius:14px;
      padding:14px;
      box-shadow:0 20px 50px rgba(0,0,0,.35);
    }
    .modal__close{
      position:absolute;
      top:10px; right:12px;
      font-size:28px;
      border:0; background:transparent;
      cursor:pointer;
      color:#fff;
    }
  </style>
</head>

<body class="admin-body">

<nav class="admin-navbar">
  <div class="admin-navbar__container">
    <h1 class="admin-navbar__title">Site Images</h1>
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

  <div id="toast" class="toast">✅ Updated</div>

  <?php foreach($groups as $groupName => $items): ?>
    <div class="section-title"><?= htmlspecialchars($groupName) ?></div>
    <div class="section-desc">
      Click ✏️ to replace. Click image to preview.
    </div>

    <div class="img-grid">
      <?php foreach($items as $key => $item): ?>
        <?php $src = imgv($item["web"], $item["file"]); ?>
        <div class="img-card">
          <img class="img-thumb"
               src="<?= htmlspecialchars($src) ?>"
               alt="<?= htmlspecialchars($item["label"]) ?>"
               onclick="openPreview('<?= htmlspecialchars($src) ?>')">

          <div class="img-meta">
            <div>
              <div class="img-label"><?= htmlspecialchars($item["label"]) ?></div>
              <div class="img-path"><?= htmlspecialchars($item["web"]) ?></div>
            </div>

            <input type="file" accept="image/*" style="display:none"
                   id="file_<?= htmlspecialchars($key) ?>"
                   onchange="uploadReplace('<?= htmlspecialchars($key) ?>', this)">

            <button class="replace-btn" type="button"
                    title="Replace image"
                    onclick="document.getElementById('file_<?= htmlspecialchars($key) ?>').click()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 20h9"/>
                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
              </svg>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>

<!-- Preview modal -->
<div id="previewModal" class="modal">
  <div class="modal__overlay" onclick="closePreview()"></div>
  <div class="modal__content">
    <button class="modal__close" onclick="closePreview()">×</button>
    <div id="previewBox" style="display:flex;justify-content:center;align-items:center;">
    </div>
  </div>
</div>

<script>
function toast(msg){
  const t = document.getElementById("toast");
  t.textContent = "✅ " + msg;
  t.style.display = "block";
  setTimeout(()=> t.style.display = "none", 2200);
}

function openPreview(url){
  document.getElementById("previewBox").innerHTML =
    `<img src="${url}" style="max-width:90vw;max-height:80vh;border-radius:10px;">`;
  document.getElementById("previewModal").classList.add("active");
}
function closePreview(){
  document.getElementById("previewModal").classList.remove("active");
  document.getElementById("previewBox").innerHTML = "";
}

async function uploadReplace(targetKey, input){
  const file = input.files && input.files[0];
  if(!file) return;

  if(file.size > 6 * 1024 * 1024){
    alert("Max file size is 6MB");
    input.value = "";
    return;
  }

  const fd = new FormData();
  fd.append("target", targetKey);
  fd.append("image", file);

  toast("Uploading...");

  const res = await fetch("api_site_image_replace.php", { method:"POST", body: fd });
  const j = await res.json().catch(()=>({}));

  input.value = "";

  if(res.ok && j.status === "SUCCESS"){
    toast("Image replaced");
    setTimeout(()=> location.reload(), 500);
  }else{
    alert(j.message || ("Error " + res.status));
  }
}
</script>

</body>
</html>