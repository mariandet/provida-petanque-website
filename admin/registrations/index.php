<?php
// search + pagination (your block is already correct)
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

$perPage = 10;
$page = max(1, (int)($_GET["page"] ?? 1));
$search = trim($_GET["search"] ?? "");

$where = "";
$params = [];

if($search !== ""){
  $where = "WHERE c.title LIKE :s OR r.full_name LIKE :s OR r.phone LIKE :s OR r.email LIKE :s";
  $params[":s"] = "%".$search."%";
}

$totalStmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM registrations r
  JOIN competitions c ON c.id = r.competition_id
  $where
");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "
  SELECT r.id, c.title AS comp_title, r.full_name, r.phone, r.email, r.created_at
  FROM registrations r
  JOIN competitions c ON c.id = r.competition_id
  $where
  ORDER BY r.id DESC
  LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach($params as $k=>$v){ $stmt->bindValue($k, $v, PDO::PARAM_STR); }
$stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$compList = $pdo->query("SELECT id, title FROM competitions  WHERE is_open='1'  ORDER BY id DESC")
                ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Registrations</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body class="admin-body">

<nav class="admin-navbar">
  <div class="admin-navbar__container">
    <h1 class="admin-navbar__title">Registration Management</h1>
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

  <div style="display:flex;justify-content:space-between;align-items:center;margin:12px 0;">
    <h2 style="color:var(--color-navy);margin:0;">Registrations</h2>
        <a href="javascript:void(0);" class="btn btn--primary" onclick="openCreateReg()">+ Create</a>

  </div>

  <div  id="successMessage" id="successMessage" style="
    margin-bottom: 20px;
    display:none;
    top:20px; right:20px;
    background:#28a745;color:#fff;
    padding:12px 30px;border-radius:8px;
    box-shadow:0 8px 20px rgba(0,0,0,0.15);
    font-weight:600; z-index:9999;">
    ✅ Success
  </div>

  <form method="get" style="margin-bottom:15px;display:flex;gap:8px;">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
           placeholder="Search competition / name / phone / email..."
           style="padding:6px 10px;border-radius:6px;border:1px solid #ccc;width:320px;">
    <button type="submit" class="btn-sm btn-view">Search</button>
    <a href="?" class="btn-sm btn-delete">Reset</a>
  </form>


  <div class="registrations-table">
    
    <table>
      <thead>
        <tr>
          <th style="width:60px;">ID</th>
          <th>Competition</th>
          <th>Name</th>
          <th style="width:140px;">Phone</th>
          <th style="width:180px;">Date</th>
          <th style="width:180px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($rows)): ?>
          <tr><td colspan="6" style="text-align:center;">No registrations found.</td></tr>
        <?php endif; ?>

        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= (int)$r["id"] ?></td>
            <td><?= htmlspecialchars($r["comp_title"]) ?></td>
            <td><?= htmlspecialchars($r["full_name"]) ?></td>
            <td><?= htmlspecialchars($r["phone"]) ?></td>
            <td><?= htmlspecialchars($r["created_at"]) ?></td>
            <td class="actions">
              <a href="javascript:void(0);" class="btn-sm btn-view"
                 onclick="openViewReg(<?= (int)$r['id'] ?>)">View</a>


                 <!-- EDIT BUTTON (inside each row) -->
            <a href="javascript:void(0);" class="btn-sm btn-edit"
              onclick="openEditReg(this)"
              data-id="<?= (int)$r['id'] ?>"
              data-name="<?= htmlspecialchars($r['full_name']) ?>"
              data-phone="<?= htmlspecialchars($r['phone']) ?>"
              data-email="<?= htmlspecialchars($r['email'] ?? '') ?>"
              data-note="<?= htmlspecialchars($r['note'] ?? '') ?>"
            > Edit </a>

            
              <a href="javascript:void(0);" class="btn-sm btn-delete"
                 onclick="deleteReg(<?= (int)$r['id'] ?>)">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        
      </tbody>
    </table>
  </div>

  <?php if($totalPages > 1): ?>
    <div style="display:flex;gap:6px;justify-content:flex-end;margin-top:12px;flex-wrap:wrap;">
      <?php if($page > 1): ?>
        <a class="btn-sm btn-edit" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">Prev</a>
      <?php endif; ?>

      <?php for($p=1;$p<=$totalPages;$p++): ?>
        <a class="btn-sm <?= $p===$page ? 'btn-view' : 'btn-edit' ?>"
           href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
      <?php endfor; ?>

      <?php if($page < $totalPages): ?>
        <a class="btn-sm btn-edit" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<!-- VIEW MODAL -->
<div id="regViewModal" class="modal">
  <div class="modal__overlay" onclick="closeViewReg()"></div>
  <div class="modal__content" style="max-width:720px;">
    <button class="modal__close" type="button" onclick="closeViewReg()">×</button>

    <h3 style="margin-bottom:10px;" id="rvTitle">Registration</h3>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
      <div><div style="font-weight:700;color:var(--color-navy);">ID</div><div id="rvId" style="color:var(--color-text-light);">-</div></div>
      <div><div style="font-weight:700;color:var(--color-navy);">Competition</div><div id="rvComp" style="color:var(--color-text-light);">-</div></div>
      <div><div style="font-weight:700;color:var(--color-navy);">Name</div><div id="rvName" style="color:var(--color-text-light);">-</div></div>
      <div><div style="font-weight:700;color:var(--color-navy);">Phone</div><div id="rvPhone" style="color:var(--color-text-light);">-</div></div>
      <div><div style="font-weight:700;color:var(--color-navy);">Email</div><div id="rvEmail" style="color:var(--color-text-light);">-</div></div>
      <div><div style="font-weight:700;color:var(--color-navy);">Created</div><div id="rvDate" style="color:var(--color-text-light);">-</div></div>
    </div>

    <div style="margin-top:10px;">
      <div style="font-weight:700;color:var(--color-navy);margin-bottom:6px;">Note</div>
      <div id="rvNote" style="color:var(--color-text-light);line-height:1.7;white-space:pre-wrap;"></div>
    </div>

    <div style="margin-top:15px;">
      <div style="font-weight:700;color:var(--color-navy);margin-bottom:8px;">Proof Image</div>
      <div id="rvImageBox" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;"></div>
    </div>

    <div style="margin-top:18px;display:flex;gap:10px;justify-content:flex-end;">
      <button class="btn btn--outline" type="button" onclick="closeViewReg()">Close</button>
    </div>
  </div>
</div>
<!-- EDIT MODAL (put once before </body>) -->
<!-- EDIT MODAL (Registration) -->
<div id="editRegModal" class="modal">
  <div class="modal__overlay" onclick="closeEditReg()"></div>

  <div class="modal__content" style="max-width:700px;">
    <button class="modal__close" onclick="closeEditReg()">×</button>

    <h3>Edit Registration</h3>

    <form id="editRegForm" class="admin-form" style="padding:0;box-shadow:none;">
      <input type="hidden" id="reId">

      <div class="form-row">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" id="reName" required>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" id="rePhone" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="reEmail">
        </div>

        <div class="form-group">
          <label>Current Proof Image (click to view)</label>

          <div id="reProofBox" style="
            width:140px;height:100px;border-radius:10px;
            overflow:hidden;background:#000;
            display:flex;align-items:center;justify-content:center;
            cursor:pointer;position:relative;
          ">
            <span id="reProofEmpty" style="color:#fff;opacity:.7;font-size:.85rem;">No image</span>
            <img id="reProofImg" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none;">
          </div>

          <div style="margin-top:10px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <label class="btn btn--outline" style="margin:0;padding:8px 12px;font-size:.85rem;">
              Replace Image
              <input type="file" id="reImage" accept="image/*" style="display:none;">
            </label>
            <span style="color:var(--color-text-light);font-size:0.85rem;">(Only 1 image per registration)</span>
          </div>

          <input type="hidden" id="reCurrentProofUrl" value="">
        </div>
      </div>

      <div class="form-group">
        <label>Note</label>
        <textarea id="reNote" rows="3"></textarea>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end;align-items:center;">
        <span id="reMsg" style="color:var(--color-text-light);font-size:0.9rem;"></span>
        <button type="button" class="btn btn--outline" onclick="closeEditReg()">Cancel</button>
        <button type="submit" class="btn btn--primary">Save</button>
      </div>
    </form>
  </div>
</div>
<!-- FULL IMAGE MODAL -->
<div id="mediaModal" class="modal">
  <div class="modal__overlay" onclick="closeMedia()"></div>
  <div class="modal__content" style="max-width:90%;background:#000;">
    <button class="modal__close" onclick="closeMedia()" style="color:#fff;">×</button>
    <div id="mediaContent" style="display:flex;justify-content:center;align-items:center;"></div>
  </div>
</div>
<div id="createRegModal" class="modal">
  <div class="modal__overlay" onclick="closeCreateReg()"></div>

  <div class="modal__content" style="max-width:700px;">
    <button class="modal__close" onclick="closeCreateReg()">×</button>

    <h3>Create Registration</h3>

    <form id="createRegForm" class="admin-form" style="padding:0;box-shadow:none;">
<!-- 
      <div class="form-group">
        <label>Competition ID</label>
        <input type="number" id="rCompetitionId" required>
      </div> -->
    <div class="form-group">
      <label>Competition</label>
      <select id="rCompetitionId" required>
        <option value="">-- Select Competition --</option>
        <?php foreach($compList as $c): ?>
          <option value="<?= (int)$c["id"] ?>">
            <?= htmlspecialchars($c["title"]) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
      <div class="form-row">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" id="rName" required>
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" id="rPhone" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Email</label>
          <input type="email" id="rEmail">
        </div>
      </div>

      <div class="form-group">
        <label>Note</label>
        <textarea id="rNote" rows="3"></textarea>
      </div>

      <div class="form-group">
        <label>Upload Proof Image</label>
        <input type="file" id="rImage" accept="image/*" required>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn--outline" onclick="closeCreateReg()">Cancel</button>
        <button type="submit" class="btn btn--primary">Create</button>
      </div>

    </form>
  </div>
</div>
<script>

const editRegModal = document.getElementById("editRegModal");

function openEditReg(btn){
  document.getElementById("reId").value = btn.dataset.id;
  document.getElementById("reName").value = btn.dataset.name || "";
  document.getElementById("rePhone").value = btn.dataset.phone || "";
  document.getElementById("reEmail").value = btn.dataset.email || "";
  document.getElementById("reNote").value = btn.dataset.note || "";
  document.getElementById("reImage").value = "";
  document.getElementById("reMsg").textContent = "";
  editRegModal.classList.add("active");
}

function closeEditReg(){
  editRegModal.classList.remove("active");
}

document.getElementById("editRegForm").addEventListener("submit", async (e)=>{
  e.preventDefault();

  const fd = new FormData();
  fd.append("id", document.getElementById("reId").value);
  fd.append("full_name", document.getElementById("reName").value);
  fd.append("phone", document.getElementById("rePhone").value);
  fd.append("email", document.getElementById("reEmail").value);
  fd.append("note", document.getElementById("reNote").value);

  const file = document.getElementById("reImage").files[0];
  if(file) fd.append("proof_image", file);

  document.getElementById("reMsg").textContent = "Saving...";

  const res = await fetch("api_registration_update.php", {
    method:"POST",
    body: fd
  });

  const j = await res.json().catch(()=>({}));

  if(res.ok && j.status === "SUCCESS"){
    showSuccess("Registration updated successfully");
    closeEditReg();
    setTimeout(()=> location.reload(), 800);
  }else{
    document.getElementById("reMsg").textContent = j.message || ("Error " + res.status);
  }
});

function showSuccess(message){
  const box = document.getElementById("successMessage");
  box.textContent = "✅ " + message;
  box.style.display = "block";
  setTimeout(() => box.style.display = "none", 2000);
}

function openImg(url){
  const c = document.getElementById("mediaContent");
  c.innerHTML = `<img src="${url}" style="max-width:90vw;max-height:80vh;border-radius:10px;">`;
  document.getElementById("mediaModal").classList.add("active");
}
function closeMedia(){
  document.getElementById("mediaModal").classList.remove("active");
  document.getElementById("mediaContent").innerHTML = "";
}

async function openViewReg(id){
  const res = await fetch("api_registration_get.php?id="+id);
  const j = await res.json();

  document.getElementById("rvId").textContent = j.id || "-";
  document.getElementById("rvComp").textContent = j.comp_title || "-";
  document.getElementById("rvName").textContent = j.full_name || "-";
  document.getElementById("rvPhone").textContent = j.phone || "-";
  document.getElementById("rvEmail").textContent = j.email || "-";
  document.getElementById("rvDate").textContent = j.created_at || "-";
  document.getElementById("rvNote").textContent = j.note || "";

  const box = document.getElementById("rvImageBox");
  box.innerHTML = "";

  if(j.proof_image_url){
    const item = document.createElement("div");
    item.style.width = "140px";
    item.style.height = "100px";
    item.style.borderRadius = "10px";
    item.style.overflow = "hidden";
    item.style.cursor = "pointer";
    item.style.background = "#000";

    const img = document.createElement("img");
    img.src = j.proof_image_url;
    img.style.width = "100%";
    img.style.height = "100%";
    img.style.objectFit = "cover";
    img.style.display = "block";

    item.appendChild(img);
    item.addEventListener("click", ()=> openImg(img.src));
    box.appendChild(item);
  } else {
    box.innerHTML = "<span style='color:var(--color-text-light);'>No image</span>";
  }

  document.getElementById("regViewModal").classList.add("active");
}
function closeViewReg(){
  document.getElementById("regViewModal").classList.remove("active");
}

async function deleteReg(id){
  if(!confirm("Delete this registration?")) return;

  const res = await fetch("api_registration_delete.php",{
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body: JSON.stringify({id})
  });

  const j = await res.json().catch(()=>({}));
  if(res.ok && j.status === "SUCCESS"){
    showSuccess("Registration deleted successfully");
    setTimeout(()=> location.reload(), 600);
  } else {
    alert(j.message || ("Error " + res.status));
  }
}
/***************CREATE******************/
const createRegModal = document.getElementById("createRegModal");

function openCreateReg(){
  createRegModal.classList.add("active");
}

function closeCreateReg(){
  createRegModal.classList.remove("active");
}

document.getElementById("createRegForm").addEventListener("submit", async function(e){
  e.preventDefault();

  const fd = new FormData();
  fd.append("competition_id", document.getElementById("rCompetitionId").value);
  fd.append("full_name", document.getElementById("rName").value);
  fd.append("phone", document.getElementById("rPhone").value);
  fd.append("email", document.getElementById("rEmail").value);
  fd.append("note", document.getElementById("rNote").value);
  fd.append("proof_image", document.getElementById("rImage").files[0]);

  const res = await fetch("api_registration_create.php", {
    method: "POST",
    body: fd
  });

  const j = await res.json();

  if(res.ok && j.status === "SUCCESS"){
    showSuccess("Registration created successfully");
    closeCreateReg();
    setTimeout(()=> location.reload(), 800);
  } else {
    alert(j.message || "Error");
  }
});

/***********************IMAGE PROFF***************************/
// EDIT modal image (single proof image)

// click current image to view full
document.getElementById("reProofBox").addEventListener("click", ()=>{
  const url = document.getElementById("reProofImg").src;
  if(url) openImg(url);
});

// load current proof when opening edit
async function openEditReg(btn){
  reId.value = btn.dataset.id;
  reName.value = btn.dataset.name || "";
  rePhone.value = btn.dataset.phone || "";
  reEmail.value = btn.dataset.email || "";
  reNote.value = btn.dataset.note || "";
  reMsg.textContent = "";

  // reset file input
  const fileInput = document.getElementById("reImage");
  fileInput.value = "";

  // load from API
  const res = await fetch("api_registration_get.php?id=" + encodeURIComponent(btn.dataset.id));
  const j = await res.json().catch(()=>({}));

  const imgEl = document.getElementById("reProofImg");
  const emptyEl = document.getElementById("reProofEmpty");
  const hiddenUrl = document.getElementById("reCurrentProofUrl");

  if(j.proof_image_url){
    const url = j.proof_image_url;

    const imgUrl = url  ;

    imgEl.src = imgUrl;
    imgEl.style.display = "block";
    emptyEl.style.display = "none";
    hiddenUrl.value = url;
  } else {
    imgEl.src = "";
    imgEl.style.display = "none";
    emptyEl.style.display = "block";
    hiddenUrl.value = "";
  }

  editRegModal.classList.add("active");
}

// preview selected replacement image (still single)
document.getElementById("reImage").addEventListener("change", (e)=>{
  const f = e.target.files && e.target.files[0];
  if(!f) return;

  const url = URL.createObjectURL(f);

  const imgEl = document.getElementById("reProofImg");
  const emptyEl = document.getElementById("reProofEmpty");

  imgEl.src = url;
  imgEl.style.display = "block";
  emptyEl.style.display = "none";
});

// submit edit (replace proof_image if selected)
document.getElementById("editRegForm").addEventListener("submit", async (e)=>{
  e.preventDefault();

  const fd = new FormData();
  fd.append("id", reId.value);
  fd.append("full_name", reName.value);
  fd.append("phone", rePhone.value);
  fd.append("email", reEmail.value);
  fd.append("note", reNote.value);

  const file = document.getElementById("reImage").files[0];
  if(file) fd.append("proof_image", file); // PHP: $_FILES["proof_image"]

  reMsg.textContent = "Saving...";

  const res = await fetch("api_registration_update.php", { method:"POST", body: fd });
  const j = await res.json().catch(()=>({}));

  if(res.ok && j.status === "SUCCESS"){
    showSuccess("Registration updated successfully");
    closeEditReg();
    setTimeout(()=> location.reload(), 800);
  } else {
    reMsg.textContent = j.message || ("Error " + res.status);
  }
});
</script>

</body>
</html>