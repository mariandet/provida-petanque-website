<!-- <?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";
$perPage = 10;
$page = max(1, (int)($_GET["page"] ?? 1));
$search = trim($_GET["search"] ?? "");

$where = "";
$params = [];

if($search !== ""){
  $where = "WHERE title LIKE :search OR description LIKE :search";
  $params[":search"] = "%".$search."%";
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM competitions $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "
  SELECT id,title,description,event_date,price,currency,is_open
  FROM competitions
  $where
  ORDER BY id DESC
  LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach($params as $k=>$v){
  $stmt->bindValue($k, $v, PDO::PARAM_STR);
}

$stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Competitions</title>

<!-- Use absolute path (recommended) -->
<link rel="stylesheet" href="../assets/style.css">
<style>
  /*****8////////////////////////
/* compact media blocks */
.media-section{margin-top:12px;}
.media-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;}
.media-title{font-weight:700;color:var(--color-navy);font-size:.95rem;}
.btn-mini{
  display:inline-flex;align-items:center;justify-content:center;
  padding:6px 10px;border-radius:8px;border:1px solid var(--color-border);
  background:#fff;color:var(--color-navy);font-weight:600;font-size:.85rem;
  cursor:pointer;transition:.2s;
}
.btn-mini:hover{border-color:var(--color-gold);color:var(--color-gold);}
.media-scroll{
  display:flex;gap:10px;flex-wrap:nowrap;overflow-x:auto;overflow-y:hidden;
  padding-bottom:6px;
}
.media-scroll::-webkit-scrollbar{height:6px;}
.media-scroll::-webkit-scrollbar-thumb{background:rgba(0,0,0,.2);border-radius:999px;}
.media-item{width:140px;flex:0 0 auto;position:relative;}
.media-thumb{
  width:140px;height:95px;object-fit:cover;border-radius:10px;display:block;
  cursor:pointer;background:#000;
}
.media-x{
  position:absolute;top:6px;right:6px;width:26px;height:26px;border:none;border-radius:999px;
  background:rgba(0,0,0,.65);color:#fff;cursor:pointer;font-size:14px;line-height:1;
}
.media-play{
  position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);
  width:36px;height:36px;border-radius:999px;background:rgba(0,0,0,.55);
  color:#fff;display:flex;align-items:center;justify-content:center;
  font-size:16px;pointer-events:none;
}

/* make modal content scroll when long */
#editModal .modal__content{max-height:85vh;overflow:auto;}
</style>

</head>

<body class="admin-body">

<nav class="admin-navbar">
  <div class="admin-navbar__container">
    <h1 class="admin-navbar__title">Competition Management</h1>
    <div style="display:flex;gap:10px;">
      <a class="btn btn--outline" href="index.php">Dashboard</a>
      <a class="btn btn--gold" href="logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="admin-dashboard">
  <div style="margin-top:14px;">
    <a class="btn btn--outline" href="../admin.php">← Back</a>
  </div>
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h2 style="color:var(--color-navy);margin:0;">Competitions</h2>
    <!-- <a class="btn btn--primary" href="create.php">+ Create</a> -->
     <a href="javascript:void(0);" class="btn btn--primary" onclick="openCreate()">+ Create</a>
  </div>
  <!-- SUCCESS MESSAGE DIV -->
  <div id="successMessage" style="
    display: none;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: #ffffff;
    padding: 12px 18px;
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    font-weight: 600;
      z-index: 9999;
  ">
    ✅ Success
  </div>
  <form method="get" style="margin-bottom:15px;display:flex;gap:8px;">
  <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
         placeholder="Search title or description..."
         style="padding:6px 10px;border-radius:6px;border:1px solid #ccc;width:250px;">
  <button type="submit" class="btn-sm btn-view">Search</button>
  <a href="?" class="btn-sm btn-delete">Reset</a>
</form>
  <div class="registrations-table">
    <table>
      <thead>
        <tr>
          <th style="width:60px;">ID</th>
          <th>Title</th>
          <th style="width:140px;">Date</th>
          <th style="width:140px;">Fee</th>
          <th style="width:90px;">Status</th>
          <th style="width:200px;">Actions</th>
        </tr>
      </thead>

      <tbody>
      <?php if(empty($rows)): ?>
        <tr><td colspan="6" style="text-align:center;">No competitions found.</td></tr>
      <?php endif; ?>

      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= (int)$r["id"] ?></td>
          <td><?= htmlspecialchars($r["title"]) ?></td>
          <td><?= htmlspecialchars($r["event_date"] ?? "-") ?></td>
          <td>
            <?php
              if ($r["price"] === null) echo "FREE";
              else echo htmlspecialchars($r["price"]." ".$r["currency"]);
            ?>
          </td>
          <td>
            <?php if((int)$r["is_open"]===1): ?>
              <span style="color:green;font-weight:600;">OPEN</span>
            <?php else: ?>
              <span style="color:red;font-weight:600;">CLOSED</span>
            <?php endif; ?>
          </td>

          <td class="actions">
            <!-- VIEW (popup) -->
            <a href="javascript:void(0);"
            class="btn-sm btn-view"
            onclick="openView(this)"
            data-id="<?= (int)$r["id"] ?>"
            data-title="<?= htmlspecialchars($r["title"]) ?>"
            data-date="<?= htmlspecialchars($r["event_date"] ?? '-') ?>"
            data-price="<?= htmlspecialchars($r["price"] === null ? 'FREE' : ($r["price"].' '.$r["currency"])) ?>"
            data-status="<?= ((int)$r["is_open"]===1) ? 'OPEN' : 'CLOSED' ?>"
            data-description="<?= htmlspecialchars($r["description"] ?? '') ?>">
            View
            </a>

            <!-- EDIT (popup) -->
            <a href="javascript:void(0);"
               class="btn-sm btn-edit editBtn"
               data-id="<?= (int)$r["id"] ?>"
               data-title="<?= htmlspecialchars($r["title"]) ?>"
               data-date="<?= htmlspecialchars($r["event_date"] ?? '') ?>"
               data-price="<?= htmlspecialchars($r["price"] ?? '') ?>"
               data-currency="<?= htmlspecialchars($r["currency"] ?? '') ?>"
               data-isopen="<?= (int)$r["is_open"] ?>"
               data-description="<?= htmlspecialchars($r["description"] ?? '') ?>">
               Edit
            </a>

            <!-- DELETE (simple confirm) -->
            <a class="btn-sm btn-delete"
               href="delete.php?id=<?= (int)$r["id"] ?>"
               onclick="return confirm('Delete this competition?')">
               Delete
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
        
      </tbody>
    </table>
  </div>

  <?php if($totalPages > 1): ?>
        <div style="display:flex;gap:6px;justify-content:flex-end;margin-top:12px;flex-wrap:wrap;">
          <?php if($page > 1): ?>
            <a class="btn-sm btn-edit"
              href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">Prev</a>
          <?php endif; ?>

          <?php for($p=1;$p<=$totalPages;$p++): ?>
            <a class="btn-sm <?= $p===$page ? 'btn-view' : 'btn-edit' ?>"
              href="?page=<?= $p ?>&search=<?= urlencode($search) ?>">
              <?= $p ?>
            </a>
          <?php endfor; ?>

          <?php if($page < $totalPages): ?>
            <a class="btn-sm btn-edit"
              href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
</div>

<!-- ✅ MODALS MUST BE OUTSIDE THE TABLE (ONLY ONCE) -->

<!-- VIEW MODAL -->
<div id="viewModal" class="modal">
  <div class="modal__overlay" id="viewOverlay"></div>
  <div class="modal__content" style="max-width:650px;">
    <button class="modal__close" type="button" onclick="closeView()">×</button>

    <h3 style="margin-bottom:10px;" id="mTitle">-</h3>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
      <div>
        <div style="font-weight:700;color:var(--color-navy);">Date</div>
        <div id="mDate" style="color:var(--color-text-light);">-</div>
      </div>
      <div>
        <div style="font-weight:700;color:var(--color-navy);">Fee</div>
        <div id="mPrice" style="color:var(--color-text-light);">-</div>
      </div>
      <div>
        <div style="font-weight:700;color:var(--color-navy);">Status</div>
        <div id="mStatus" style="color:var(--color-text-light);">-</div>
      </div>
      <div>
        <div style="font-weight:700;color:var(--color-navy);">ID</div>
        <div id="mId" style="color:var(--color-text-light);">-</div>
      </div>
    </div>

    <div style="margin-top:10px;">
      <div style="font-weight:700;color:var(--color-navy);margin-bottom:6px;">Description</div>
      <div id="mDescription" style="color:var(--color-text-light);line-height:1.7;white-space:pre-wrap;"></div>
    </div>

    <div style="margin-top:15px;">
        <div style="font-weight:700;color:var(--color-navy);margin-bottom:8px;">Images</div>
        <div id="mImages" style="display:flex;flex-wrap:wrap;gap:10px;"></div>
    </div>

    <div style="margin-top:18px;display:flex;gap:10px;justify-content:flex-end;">
      <button class="btn btn--outline" type="button" onclick="closeView()">Close</button>
    </div>
  </div>
</div>

<!-- EDIT MODAL -->

<!-- EDIT MODAL -->
<div id="editModal" class="modal">
  <div class="modal__overlay" id="editOverlay"></div>

  <div class="modal__content" style="max-width:700px;">
    <button class="modal__close" type="button" onclick="closeEdit()">×</button>
    <h3 style="margin-bottom:12px;">Edit Competition</h3>

    <form id="editForm" class="admin-form" style="padding:0;box-shadow:none;">
      <input type="hidden" id="eId">

      <div class="form-group">
        <label>Title</label>
        <input type="text" id="eTitle" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Date</label>
          <input type="date" id="eDate">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select id="eOpen">
            <option value="1">OPEN</option>
            <option value="0">CLOSED</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Price (optional)</label>
          <input type="number" step="0.01" id="ePrice" placeholder="10.00">
        </div>
        <div class="form-group">
          <label>Currency</label>
          <input type="text" id="eCurrency" placeholder="USD">
        </div>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea id="eDescription" rows="4"></textarea>
      </div>

      <!-- IMAGES -->
      <div class="media-section">
        <div class="media-head">
          <div class="media-title">Images</div>
          <label class="btn-mini">
            + Add Images
            <input type="file" id="eImages" multiple accept="image/*" style="display:none;">
          </label>
        </div>
        <div id="eMediaBox" class="media-scroll"></div>
      </div>

      <!-- VIDEOS -->
      <div class="media-section">
        <div class="media-head">
          <div class="media-title">Videos</div>
          <label class="btn-mini">
            + Add Videos
            <input type="file" id="eVideos" multiple accept="video/mp4,video/webm,video/quicktime" style="display:none;">
          </label>
        </div>
        <div id="eVideoBox" class="media-scroll"></div>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end;align-items:center;margin-top:12px;">
        <span id="editMsg" style="color:var(--color-text-light);font-size:0.9rem;"></span>
        <button class="btn btn--outline" type="button" onclick="closeEdit()">Cancel</button>
        <button class="btn btn--primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- FULL MEDIA MODAL (put once before </body>) -->
<div id="mediaModal" class="modal">
  <div class="modal__overlay" onclick="closeMedia()"></div>
  <div class="modal__content" style="max-width:90%;background:#000;">
    <button class="modal__close" onclick="closeMedia()" style="color:#fff;">×</button>
    <div id="mediaContent" style="display:flex;justify-content:center;align-items:center;"></div>
  </div>
</div>
     
    </form>
  </div>
</div>
<!-- CREATE MODAL -->
<div id="createModal" class="modal">
  <div class="modal__overlay" onclick="closeCreate()"></div>

  <div class="modal__content" style="max-width:700px;">
    <button class="modal__close" type="button" onclick="closeCreate()">×</button>

    <h3>Create Competition</h3>

    <form id="createForm" class="admin-form" style="padding:0;box-shadow:none;">
      
      <div class="form-group">
        <label>Title</label>
        <input type="text" id="cTitle" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Date</label>
          <input type="date" id="cDate">
        </div>

        <div class="form-group">
          <label>Status</label>
          <select id="cOpen">
            <option value="1">OPEN</option>
            <option value="0">CLOSED</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Price</label>
          <input type="number" step="0.01" id="cPrice">
        </div>

        <div class="form-group">
          <label>Currency</label>
          <input type="text" id="cCurrency" value="USD">
        </div>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea id="cDescription" rows="5"></textarea>
      </div>

      <div class="form-group">
        <label>Upload Images</label>
        <input type="file" id="cImages" multiple accept="image/*">
      </div>
                
    <div class="form-group">
        <label>Upload Videos (MP4)</label>

    <!-- MUST BE: name="videos[]" OR id matches -->
    <input type="file" id="cVideos" name="videos[]" multiple accept="video/mp4,video/webm,video/quicktime">

    </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <span id="createMsg"></span>
        <button type="button" class="btn btn--outline" onclick="closeCreate()">Cancel</button>
        <button type="submit" class="btn btn--primary">Create</button>
      </div>

    </form>
  </div>
</div>

<!-- Image MODEL -->
<!-- <div id="mediaModal" class="modal">
  <div class="modal__overlay" onclick="closeMedia()"></div>
  <div class="modal__content" style="max-width:90%;background:black;">
    <button class="modal__close" onclick="closeMedia()" style="color:white;">×</button>
    <div id="mediaContent" style="text-align:center;"></div>
  </div>
</div> -->
<!-- add this once (before </body>) -->
<div id="mediaModal" class="modal">
  <div class="modal__overlay" onclick="closeMedia()"></div>
  <div class="modal__content" style="max-width:90%;background:#000;">
    <button class="modal__close" onclick="closeMedia()" style="color:#fff;">×</button>
    <div id="mediaContent" style="display:flex;justify-content:center;align-items:center;"></div>
  </div>
</div>
<script>


/* EDIT */
const editModal = document.getElementById("editModal");
const editOverlay = document.getElementById("editOverlay");

// function openEdit(btn){
//   document.getElementById("eId").value = btn.dataset.id;
//   document.getElementById("eTitle").value = btn.dataset.title || "";
//   document.getElementById("eDate").value = btn.dataset.date || "";
//   document.getElementById("ePrice").value = btn.dataset.price || "";
//   document.getElementById("eCurrency").value = btn.dataset.currency || "";
//   document.getElementById("eOpen").value = btn.dataset.isopen || "0";
//   document.getElementById("eDescription").value = btn.dataset.description || "";
//   document.getElementById("editMsg").textContent = "";
//   editModal.classList.add("active");
// }
/* OPEN EDIT FROM ROW BUTTON */
async function openEdit(btn){

  document.getElementById("eId").value = btn.dataset.id;
  document.getElementById("eTitle").value = btn.dataset.title || "";
  document.getElementById("eDate").value = btn.dataset.date || "";
  document.getElementById("ePrice").value = btn.dataset.price || "";
  document.getElementById("eCurrency").value = btn.dataset.currency || "";
  document.getElementById("eOpen").value = btn.dataset.isopen || "0";
  document.getElementById("eDescription").value = btn.dataset.description || "";

  editModal.classList.add("active");

  // 👇 LOAD MEDIA
  await loadEditImages(btn.dataset.id);
  await loadEditVideos(btn.dataset.id);
}
/* LOAD CURRENT IMAGES INTO EDIT MODAL */
/* LOAD CURRENT IMAGES INTO EDIT MODAL (same behavior as video) */
async function loadEditImages(competitionId){
  const res = await fetch("api_competition_get.php?id=" + competitionId);
  const j = await res.json().catch(()=>({}));

  const box = document.getElementById("eMediaBox");
  box.innerHTML = "";

  const imgs = (j.media || []).filter(m => m.media_type === "IMAGE");
  if(!imgs.length){
    box.innerHTML = "<span style='color:var(--color-text-light);'>No images</span>";
    return;
  }

  imgs.forEach(m=>{
    const item = document.createElement("div");
    item.className = "media-item";
    item.style.cursor = "pointer";

    const img = document.createElement("img");
    // IMPORTANT: match video behavior (prefix + cache bust)
    img.src = m.media_url + "?t=" + Date.now();
    img.className = "media-thumb";
    img.style.background = "#000";

    // click full view
    item.addEventListener("click", (e)=>{
      e.preventDefault();
      e.stopPropagation();
      openImg(m.media_url);
    });

    // delete button
    const del = document.createElement("button");
    del.type = "button";
    del.className = "media-x";
    del.textContent = "✕";

    del.addEventListener("click", async (e)=>{
      e.preventDefault();
      e.stopPropagation();
      if(!confirm("Remove this image?")) return;

      const r = await fetch("api_media_delete.php",{
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({ id: m.id })
      });

      const jj = await r.json().catch(()=>({}));

      if(r.ok && jj.status === "SUCCESS"){
        showSuccess("Image removed");
        loadEditImages(competitionId);
      }else{
        alert(jj.message || ("Error " + r.status));
      }
    });

    item.appendChild(img);
    item.appendChild(del);
    box.appendChild(item);
  });
}

function closeEdit(){ editModal.classList.remove("active"); }
document.querySelectorAll(".editBtn").forEach(btn => btn.addEventListener("click", () => openEdit(btn)));
editOverlay.addEventListener("click", closeEdit);

document.getElementById("editForm").addEventListener("submit", async function(e){
  e.preventDefault();

  const payload = {
    id: document.getElementById("eId").value,
    title: document.getElementById("eTitle").value,
    event_date: document.getElementById("eDate").value,
    price: document.getElementById("ePrice").value,
    currency: document.getElementById("eCurrency").value,
    is_open: document.getElementById("eOpen").value,
    description: document.getElementById("eDescription").value
  };

  document.getElementById("editMsg").textContent = "Saving...";

  const res = await fetch("api_competition_update.php", {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify(payload)
  });

  const j = await res.json().catch(()=>({}));
  if(res.ok && j.status === "SUCCESS"){

   const files = document.getElementById("eImages").files;

    // IMAGE upload
    if(files.length > 0){
      const fd = new FormData();
      fd.append("competition_id", payload.id);

      for(let i=0;i<files.length;i++){
          fd.append("images[]", files[i]);
      }

      await fetch("api_media_upload_image.php", {
          method: "POST",
          body: fd
      });
    }

    // VIDEO upload
    const videoFiles = document.getElementById("cVideos").files;

    if(videoFiles.length > 0){
    const fdVideo = new FormData();
    fdVideo.append("competition_id", payload.id);

    for(let i=0;i<videoFiles.length;i++){
        fdVideo.append("videos[]", videoFiles[i]);
    }

    await fetch("api_media_upload_video.php", {
        method: "POST",
        body: fdVideo
    });
    }

  

    document.getElementById("editMsg").textContent = "✅ Updated";
    setTimeout(()=> location.reload(), 300);
  } else {
    document.getElementById("editMsg").textContent = j.message || ("Error " + res.status);
  }
});

/* LOAD VIDEOS */

/* AUTO UPLOAD */
document.getElementById("eVideos").addEventListener("change", ()=>{
  const id = document.getElementById("eId").value;
  if(id) uploadMoreVideos(id);
});


async function loadEditVideos(competitionId){
  const res = await fetch("api_competition_get.php?id=" + competitionId);
  const j = await res.json().catch(()=>({}));

  const box = document.getElementById("eVideoBox");
  box.innerHTML = "";

  const vids = (j.media || []).filter(m => m.media_type === "VIDEO");
  if(!vids.length){
    box.innerHTML = "<span style='color:var(--color-text-light);'>No videos</span>";
    return;
  }

  vids.forEach(m=>{
    const wrap = document.createElement("div");
    wrap.style.width = "160px";
    wrap.style.position = "relative";
    wrap.style.cursor = "pointer";

    const video = document.createElement("video");
    video.src = m.media_url;
    video.muted = true;
    video.preload = "metadata";
    video.style.width = "160px";
    video.style.height = "110px";
    video.style.objectFit = "cover";
    video.style.borderRadius = "10px";

    video.addEventListener("loadedmetadata", ()=>{
      try{ video.currentTime = Math.min(1, video.duration || 1); }catch(e){}
    });

    video.onclick = ()=> openFullVideo( m.media_url);

    const del = document.createElement("button");
    del.textContent = "✕";
    del.style.position = "absolute";
    del.style.top = "6px";
    del.style.right = "6px";
    del.style.width = "28px";
    del.style.height = "28px";
    del.style.border = "none";
    del.style.borderRadius = "999px";
    del.style.background = "rgba(0,0,0,.65)";
    del.style.color = "#fff";
    del.style.cursor = "pointer";

    del.onclick = async (e)=>{
      e.preventDefault(); e.stopPropagation();
      if(!confirm("Remove this video?")) return;

      const r = await fetch("api_media_delete.php",{
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({ id: m.id })
      });
      const jj = await r.json().catch(()=>({}));

      if(r.ok && jj.status==="SUCCESS"){
        showSuccess("Video removed");
        loadEditVideos(competitionId);
      }else{
        alert(jj.message || ("Error " + r.status));
      }
    };

    wrap.appendChild(video);
    wrap.appendChild(del);
    box.appendChild(wrap);
  });
}

/* UPLOAD MORE VIDEOS */
async function uploadMoreVideos(competitionId){
  const input = document.getElementById("eVideos");
  const files = input.files;
  if(!files || !files.length) return;

  const fd = new FormData();
  fd.append("competition_id", competitionId);
  for(let i=0;i<files.length;i++){
    fd.append("videos[]", files[i]);
  }

  const res = await fetch("api_media_upload_video.php", {
    method:"POST",
    body: fd
  });

  if(!res.ok){
    const t = await res.text();
    alert("Upload failed: " + t);
    return;
  }

  input.value = "";
  showSuccess("Videos added");
  loadEditVideos(competitionId);
}

/* FULL VIDEO VIEW */
function openFullVideo(url){
  const c = document.getElementById("mediaContent");
  c.innerHTML = `
    <video controls autoplay style="max-width:90vw;max-height:80vh;border-radius:10px;">
      <source src="${url}">
    </video>
  `;
  document.getElementById("mediaModal").classList.add("active");
}


/* ADD this inside openEdit() AFTER loadEditImages */
// await loadEditVideos(btn.dataset.id);
function openImg(url){
  const c = document.getElementById("mediaContent");
  c.innerHTML = `<img src="${url}" style="max-width:90vw;max-height:80vh;border-radius:10px;">`;
  document.getElementById("mediaModal").classList.add("active");
}

// PREVIEW newly selected images BEFORE upload (EDIT)
document.getElementById("eImages").addEventListener("change", () => {
  const input = document.getElementById("eImages");
  const files = input.files;
  const box = document.getElementById("eMediaBox");
  if(!files || !files.length) return;

  // remove old temp previews
  box.querySelectorAll(".temp-preview").forEach(x => x.remove());

  Array.from(files).forEach((file, index) => {

    const item = document.createElement("div");
    item.className = "media-item temp-preview";
    item.style.position = "relative";

    const img = document.createElement("img");
    img.className = "media-thumb";
    img.src = URL.createObjectURL(file);
    img.onload = () => URL.revokeObjectURL(img.src);

    // VIEW (click full screen)
    img.onclick = () => {
      openImg(img.src);
    };

    // REMOVE button
    const remove = document.createElement("button");
    remove.type = "button";
    remove.className = "media-x";
    remove.textContent = "✕";

    remove.onclick = (e) => {
      e.preventDefault();
      e.stopPropagation();

      // remove from preview UI
      item.remove();

      // remove file from input (rebuild FileList)
      const dt = new DataTransfer();
      Array.from(input.files)
        .filter((_, i) => i !== index)
        .forEach(f => dt.items.add(f));

      input.files = dt.files;
    };

    item.appendChild(img);
    item.appendChild(remove);
    box.prepend(item);
  });
});
/**************CREATE*****************/
const createModal = document.getElementById("createModal");

function openCreate(){ createModal.classList.add("active"); }
function closeCreate(){ createModal.classList.remove("active"); }

document.getElementById("createForm").addEventListener("submit", async function(e){
  e.preventDefault();

  const payload = {
    title: cTitle.value,
    event_date: cDate.value,
    price: cPrice.value,
    currency: cCurrency.value,
    is_open: cOpen.value,
    description: cDescription.value
  };

  const res = await fetch("api_competition_create.php",{
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body:JSON.stringify(payload)
  });

  const j = await res.json();

  if(j.status === "SUCCESS"){

    const id = j.id;
    const files = cImages.files;

    if(files.length){
      const fd = new FormData();
      fd.append("competition_id", id);
      for(let i=0;i<files.length;i++){
        fd.append("images[]", files[i]);
      }

      await fetch("api_media_upload_image.php",{
        method:"POST",
        body:fd
      });
    }

   
    const fdVideo = new FormData();
    // EDIT case (payload.id already exists)
    fdVideo.append("competition_id", id);
    const vInput = document.getElementById("cVideos") || document.getElementById("eVideos");
    const videoFiles = vInput ? vInput.files : null;

    for(let i=0;i<videoFiles.length;i++){
    fdVideo.append("videos[]", videoFiles[i]);
    }

    const resV = await fetch("api_media_upload_video.php", {
    method: "POST",
    body: fdVideo
    });

    // document.getElementById("createMsg").textContent = "✅ SUCCESS";
    // setTimeout(()=> location.reload(), 300);
    closeCreate()
    showSuccess("Competition updated successfully");
   
    setTimeout(() => location.reload(), 1500);
  } else {
    createMsg.textContent = j.message || "Error";
  }
});

/****************VIEW*****************/
async function openView(btn){

  document.getElementById("mId").textContent = btn.dataset.id || "-";
  document.getElementById("mTitle").textContent = btn.dataset.title || "-";
  document.getElementById("mDate").textContent = btn.dataset.date || "-";
  document.getElementById("mPrice").textContent = btn.dataset.price || "-";
  document.getElementById("mDescription").textContent = btn.dataset.description || "-";

  const s = btn.dataset.status || "-";
  const mStatus = document.getElementById("mStatus");
  mStatus.textContent = s;
  mStatus.style.fontWeight = "700";
  mStatus.style.color = (s === "OPEN") ? "green" : "red";

  // LOAD IMAGES
  const res = await fetch("api_competition_get.php?id="+btn.dataset.id);
  const j = await res.json();

  const box = document.getElementById("mImages");
  box.innerHTML = "";


        if(j.media && j.media.length){

        box.innerHTML = "";

        j.media.forEach(m=>{
          // REPLACE your VIDEO block with this (correct position + click works)

          // REPLACE your VIDEO block with this (correct position + click works)
        if(m.media_type === "VIDEO"){

            const item = document.createElement("div");
            item.style.width = "140px";
            item.style.height = "100px";
            item.style.borderRadius = "10px";
            item.style.overflow = "hidden";
            item.style.position = "relative";
            item.style.cursor = "pointer";
            item.style.background = "#000";

            const vid = document.createElement("video");
            vid.src =  m.media_url;
            vid.muted = true;
            vid.playsInline = true;
            vid.preload = "metadata";
            vid.style.width = "100%";
            vid.style.height = "100%";
            vid.style.objectFit = "cover";
            vid.style.display = "block";

            // seek to get a frame thumbnail
            vid.addEventListener("loadedmetadata", () => {
                try { vid.currentTime = Math.min(1, vid.duration || 1); } catch(e){}
            });

            const overlay = document.createElement("div");
            overlay.textContent = "▶";
            overlay.style.position = "absolute";
            overlay.style.left = "50%";
            overlay.style.top = "50%";
            overlay.style.transform = "translate(-50%,-50%)";
            overlay.style.width = "40px";
            overlay.style.height = "40px";
            overlay.style.borderRadius = "50%";
            overlay.style.background = "rgba(0,0,0,0.55)";
            overlay.style.color = "#fff";
            overlay.style.display = "flex";
            overlay.style.alignItems = "center";
            overlay.style.justifyContent = "center";
            overlay.style.fontSize = "18px";
            overlay.style.pointerEvents = "none";

            item.appendChild(vid);
            item.appendChild(overlay);

            item.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                openFullVideo(m.media_url, "video");
            });

            box.appendChild(item);
        }

        if(m.media_type === "IMAGE"){

            const wrapper = document.createElement("div");
            wrapper.style.position = "relative";
            wrapper.style.cursor = "pointer";

            const img = document.createElement("img");
            img.src =  m.media_url;
            img.style.width = "120px";
            img.style.height = "120px";
            img.style.objectFit = "cover";
            img.style.borderRadius = "8px";
            img.style.transition = "0.2s";
            img.onmouseenter = () => img.style.transform = "scale(1.05)";
            img.onmouseleave = () => img.style.transform = "scale(1)";

            img.onclick = () => openFullMedia( m.media_url, "IMAGE");

            wrapper.appendChild(img);
            box.appendChild(wrapper);
        }

           
        });

        } else {
        box.innerHTML = "<span style='color:var(--color-text-light);'>No media</span>";
        }
        document.getElementById("viewModal").classList.add("active");
}


function closeView(){ viewModal.classList.remove("active"); }
document.querySelectorAll(".viewBtn").forEach(btn => btn.addEventListener("click", () => openView(btn)));
viewOverlay.addEventListener("click", closeView);

function openFullMedia(url, type){

  const container = document.getElementById("mediaContent");
  container.innerHTML = "";

  if(type === "IMAGE"){
    container.innerHTML = `<img src="${url}" style="max-width:100%;max-height:80vh;border-radius:8px;">`;
  }

  if(type === "VIDEO"){
    container.innerHTML = `
      <iframe src="${url}" 
              style="width:90vw;height:70vh;border:none;border-radius:8px;"
              allowfullscreen>
      </iframe>`;
  }

  document.getElementById("mediaModal").classList.add("active");
}

function closeMedia(){
  document.getElementById("mediaModal").classList.remove("active");
  document.getElementById("mediaContent").innerHTML = "";
}

function openFullVideo(url){

  const container = document.getElementById("mediaContent");
  container.innerHTML = "";

  // YouTube link
  if(url.includes("youtube.com") || url.includes("youtu.be")){

    let videoId = "";

    if(url.includes("youtu.be/")){
      videoId = url.split("youtu.be/")[1];
    } else {
      const params = new URLSearchParams(url.split("?")[1]);
      videoId = params.get("v");
    }

    container.innerHTML = `
      <iframe 
        src="https://www.youtube.com/embed/${videoId}" 
        style="width:90vw;height:70vh;border:none;border-radius:8px;"
        allowfullscreen>
      </iframe>`;
  }

  // Direct MP4 file
  else if(url.endsWith(".mp4") || url.endsWith(".webm")){
    container.innerHTML = `
      <video controls autoplay style="max-width:90vw;max-height:80vh;border-radius:8px;">
        <source src="${url}">
      </video>`;
  }

  document.getElementById("mediaModal").classList.add("active");
}
/******SUCCESS SCREEN******/
function showSuccess(message){
  const box = document.getElementById("successMessage");
  box.textContent = "✅ " + message;
  box.style.display = "block";

  setTimeout(() => {
    box.style.display = "none";
  }, 2000);
}

/************EDIT**************/

</script>

</body>
</html>