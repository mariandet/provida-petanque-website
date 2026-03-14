<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

$perPage = 10;
$page = max(1, (int)($_GET["page"] ?? 1));
$search = trim($_GET["search"] ?? "");

$where = "";
$params = [];

if ($search !== "") {
    $where = "WHERE title LIKE :search OR description LIKE :search OR term_condition LIKE :search";
    $params[":search"] = "%" . $search . "%";
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM competitions $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT id, title, description, term_condition, event_date, price, currency, is_open
    FROM competitions
    $where
    ORDER BY id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}

$stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Competitions</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
#editModal .modal__content,
#createModal .modal__content,
#viewModal .modal__content {
  max-height: 85vh;
  overflow: auto;
}
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
.pagination {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 18px;
}
.pagination a,
.pagination span {
  display: inline-block;
  padding: 8px 12px;
  border-radius: 6px;
  text-decoration: none;
  border: 1px solid #ddd;
  color: #333;
  background: #fff;
}
.pagination .active {
  background: var(--color-navy, #0f3b6f);
  color: #fff;
  border-color: var(--color-navy, #0f3b6f);
}
.pagination .disabled {
  color: #aaa;
  background: #f3f4f6;
}
</style>
</head>
<body class="admin-body">

<nav class="admin-navbar">
  <div class="admin-navbar__container">
    <h1 class="admin-navbar__title">Competition Management</h1>
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

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h2 style="color:var(--color-navy);margin:0;">Competitions</h2>
    <a href="javascript:void(0);" class="btn btn--primary" onclick="openCreate()">+ Create</a>
  </div>

  <div id="successMessage" style="
    display:none;
    background:#28a745;
    color:#ffffff;
    padding:12px 18px;
    border-radius:8px;
    box-shadow:0 8px 20px rgba(0,0,0,0.15);
    font-weight:600;
    z-index:9999;
    margin-bottom:15px;
  ">✅ Success</div>

  <form method="get" style="margin-bottom:15px;display:flex;gap:8px;">
    <input
      type="text"
      name="search"
      value="<?= e($search) ?>"
      placeholder="Search title, description or term and condition..."
      style="padding:6px 10px;border-radius:6px;border:1px solid #ccc;width:300px;"
    >
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
        <?php if (empty($rows)): ?>
          <tr><td colspan="6" style="text-align:center;">No competitions found.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r["id"] ?></td>
              <td><?= e($r["title"]) ?></td>
              <td><?= e($r["event_date"] ?? "-") ?></td>
              <td>
                <?php
                  if ($r["price"] === null || $r["price"] === "") echo "FREE";
                  else echo e($r["price"] . " " . $r["currency"]);
                ?>
              </td>
              <td>
                <?php if ((int)$r["is_open"] === 1): ?>
                  <span style="color:green;font-weight:600;">OPEN</span>
                <?php else: ?>
                  <span style="color:red;font-weight:600;">CLOSED</span>
                <?php endif; ?>
              </td>
              <td class="actions">
                <a href="javascript:void(0);"
                   class="btn-sm btn-view"
                   onclick="openView(this)"
                   data-id="<?= (int)$r["id"] ?>"
                   data-title="<?= e($r["title"]) ?>"
                   data-date="<?= e($r["event_date"] ?? '-') ?>"
                   data-price="<?= e($r["price"] === null || $r["price"] === '' ? 'FREE' : ($r["price"].' '.$r["currency"])) ?>"
                   data-status="<?= ((int)$r["is_open"]===1) ? 'OPEN' : 'CLOSED' ?>"
                   data-description="<?= e($r["description"] ?? '') ?>"
                   data-termcondition="<?= e($r["term_condition"] ?? '') ?>">
                  View
                </a>

                <a href="javascript:void(0);"
                   class="btn-sm btn-edit editBtn"
                   data-id="<?= (int)$r["id"] ?>"
                   data-title="<?= e($r["title"]) ?>"
                   data-date="<?= e($r["event_date"] ?? '') ?>"
                   data-price="<?= e($r["price"] ?? '') ?>"
                   data-currency="<?= e($r["currency"] ?? '') ?>"
                   data-isopen="<?= (int)$r["is_open"] ?>"
                   data-description="<?= e($r["description"] ?? '') ?>"
                   data-termcondition="<?= e($r["term_condition"] ?? '') ?>">
                  Edit
                </a>

                <a class="btn-sm btn-delete"
                   href="delete.php?id=<?= (int)$r["id"] ?>"
                   onclick="return confirm('Delete this competition?')">
                  Delete
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Prev</a>
      <?php else: ?>
        <span class="disabled">Prev</span>
      <?php endif; ?>

      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <?php if ($p === $page): ?>
          <span class="active"><?= $p ?></span>
        <?php else: ?>
          <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
      <?php else: ?>
        <span class="disabled">Next</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

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

    <div style="margin-top:10px;">
      <div style="font-weight:700;color:var(--color-navy);margin-bottom:6px;">Term and Condition</div>
      <div id="mTermCondition" style="color:var(--color-text-light);line-height:1.7;white-space:pre-wrap;"></div>
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

      <div class="form-group">
        <label>Term and Condition</label>
        <textarea id="eTermCondition" rows="4"></textarea>
      </div>

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

      <div style="display:flex;gap:10px;justify-content:flex-end;align-items:center;margin-top:12px;">
        <span id="editMsg" style="color:var(--color-text-light);font-size:0.9rem;"></span>
        <button class="btn btn--outline" type="button" onclick="closeEdit()">Cancel</button>
        <button class="btn btn--primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

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
        <label>Term and Condition</label>
        <textarea id="cTermCondition" rows="5"></textarea>
      </div>

      <div class="form-group">
        <label>Upload Images</label>
        <input type="file" id="cImages" multiple accept="image/*">
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <span id="createMsg"></span>
        <button type="button" class="btn btn--outline" onclick="closeCreate()">Cancel</button>
        <button type="submit" class="btn btn--primary">Create</button>
      </div>
    </form>
  </div>
</div>

<div id="mediaModal" class="modal">
  <div class="modal__overlay" onclick="closeMedia()"></div>
  <div class="modal__content" style="max-width:90%;background:#000;">
    <button class="modal__close" onclick="closeMedia()" style="color:#fff;">×</button>
    <div id="mediaContent" style="display:flex;justify-content:center;align-items:center;"></div>
  </div>
</div>

<script>
const editModal = document.getElementById("editModal");
const editOverlay = document.getElementById("editOverlay");
const viewModal = document.getElementById("viewModal");
const viewOverlay = document.getElementById("viewOverlay");
const createModal = document.getElementById("createModal");

function showSuccess(message){
  const box = document.getElementById("successMessage");
  box.textContent = "✅ " + message;
  box.style.display = "block";
  setTimeout(() => {
    box.style.display = "none";
  }, 2000);
}

function openCreate(){ createModal.classList.add("active"); }
function closeCreate(){ createModal.classList.remove("active"); }

function closeEdit(){ editModal.classList.remove("active"); }
function closeView(){ viewModal.classList.remove("active"); }

editOverlay.addEventListener("click", closeEdit);
viewOverlay.addEventListener("click", closeView);

function openImg(url){
  const c = document.getElementById("mediaContent");
  c.innerHTML = `<img src="${url}" style="max-width:90vw;max-height:80vh;border-radius:10px;">`;
  document.getElementById("mediaModal").classList.add("active");
}

function closeMedia(){
  document.getElementById("mediaModal").classList.remove("active");
  document.getElementById("mediaContent").innerHTML = "";
}

async function loadEditImages(competitionId){
  const res = await fetch("api_competition_get.php?id=" + competitionId);
  const j = await res.json().catch(() => ({}));

  const box = document.getElementById("eMediaBox");
  box.innerHTML = "";

  const imgs = (j.media || []).filter(m => m.media_type === "IMAGE");

  if(!imgs.length){
    box.innerHTML = "<span style='color:var(--color-text-light);'>No images</span>";
    return;
  }

  imgs.forEach(m => {
    const item = document.createElement("div");
    item.className = "media-item";

    const img = document.createElement("img");
    img.src = m.media_url + "?t=" + Date.now();
    img.className = "media-thumb";
    img.onclick = () => openImg(m.media_url);

    const del = document.createElement("button");
    del.type = "button";
    del.className = "media-x";
    del.textContent = "✕";

    del.onclick = async (e) => {
      e.preventDefault();
      e.stopPropagation();

      if (!confirm("Remove this image?")) return;

      const r = await fetch("api_media_delete.php", {
        method: "POST",
        headers: {"Content-Type":"application/json"},
        body: JSON.stringify({ id: m.id })
      });

      const jj = await r.json().catch(() => ({}));

      if (r.ok && jj.status === "SUCCESS") {
        showSuccess("Image removed");
        loadEditImages(competitionId);
      } else {
        alert(jj.message || ("Error " + r.status));
      }
    };

    item.appendChild(img);
    item.appendChild(del);
    box.appendChild(item);
  });
}

async function openEdit(btn){
  document.getElementById("eId").value = btn.dataset.id;
  document.getElementById("eTitle").value = btn.dataset.title || "";
  document.getElementById("eDate").value = btn.dataset.date || "";
  document.getElementById("ePrice").value = btn.dataset.price || "";
  document.getElementById("eCurrency").value = btn.dataset.currency || "";
  document.getElementById("eOpen").value = btn.dataset.isopen || "0";
  document.getElementById("eDescription").value = btn.dataset.description || "";
  document.getElementById("eTermCondition").value = btn.dataset.termcondition || "";
  document.getElementById("editMsg").textContent = "";
  editModal.classList.add("active");

  await loadEditImages(btn.dataset.id);
}

document.querySelectorAll(".editBtn").forEach(btn => {
  btn.addEventListener("click", () => openEdit(btn));
});

document.getElementById("editForm").addEventListener("submit", async function(e){
  e.preventDefault();

  const payload = {
    id: document.getElementById("eId").value,
    title: document.getElementById("eTitle").value,
    event_date: document.getElementById("eDate").value,
    price: document.getElementById("ePrice").value,
    currency: document.getElementById("eCurrency").value,
    is_open: document.getElementById("eOpen").value,
    description: document.getElementById("eDescription").value,
    term_condition: document.getElementById("eTermCondition").value
  };

  const editMsg = document.getElementById("editMsg");
  editMsg.textContent = "Saving...";

  try {
    const updateRes = await fetch("api_competition_update.php", {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify(payload)
    });

    const updateRaw = await updateRes.text();
    let updateJson = {};

    try {
      updateJson = JSON.parse(updateRaw);
    } catch (err) {
      editMsg.textContent = "Invalid update response: " + updateRaw;
      return;
    }

    if (!updateRes.ok || updateJson.status !== "SUCCESS") {
      editMsg.textContent = updateJson.message || ("Error " + updateRes.status);
      return;
    }

    const files = document.getElementById("eImages").files;

    if (files.length > 0) {
      const fd = new FormData();
      fd.append("competition_id", payload.id);

      for (let i = 0; i < files.length; i++) {
        fd.append("images[]", files[i]);
      }

      const uploadRes = await fetch("api_media_upload_image.php", {
        method: "POST",
        body: fd
      });

      const uploadRaw = await uploadRes.text();

      let uploadJson = {};
      try {
        uploadJson = JSON.parse(uploadRaw);
      } catch (err) {
        editMsg.textContent = "Invalid upload response: " + uploadRaw;
        return;
      }

      if (!uploadRes.ok || uploadJson.status !== "SUCCESS") {
        editMsg.textContent = uploadJson.message || "Upload failed";
        return;
      }
    }

    editMsg.textContent = "✅ Updated";
    showSuccess("Competition updated successfully");
    setTimeout(() => location.reload(), 500);

  } catch (err) {
    editMsg.textContent = err.message || "Unexpected error";
  }
});

document.getElementById("eImages").addEventListener("change", () => {
  const input = document.getElementById("eImages");
  const files = input.files;
  const box = document.getElementById("eMediaBox");
  if(!files || !files.length) return;

  box.querySelectorAll(".temp-preview").forEach(x => x.remove());

  Array.from(files).forEach((file, index) => {
    const item = document.createElement("div");
    item.className = "media-item temp-preview";

    const img = document.createElement("img");
    img.className = "media-thumb";
    img.src = URL.createObjectURL(file);
    img.onload = () => URL.revokeObjectURL(img.src);
    img.onclick = () => openImg(img.src);

    const remove = document.createElement("button");
    remove.type = "button";
    remove.className = "media-x";
    remove.textContent = "✕";

    remove.onclick = (e) => {
      e.preventDefault();
      e.stopPropagation();

      item.remove();

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

document.getElementById("createForm").addEventListener("submit", async function(e){
  e.preventDefault();

  const createMsg = document.getElementById("createMsg");

  const payload = {
    title: document.getElementById("cTitle").value,
    event_date: document.getElementById("cDate").value,
    price: document.getElementById("cPrice").value,
    currency: document.getElementById("cCurrency").value,
    is_open: document.getElementById("cOpen").value,
    description: document.getElementById("cDescription").value,
    term_condition: document.getElementById("cTermCondition").value
  };

  try {
    createMsg.textContent = "Creating...";

    const createRes = await fetch("api_competition_create.php", {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify(payload)
    });

    const createRaw = await createRes.text();
    let createJson = {};

    try {
      createJson = JSON.parse(createRaw);
    } catch (err) {
      createMsg.textContent = "Invalid create response: " + createRaw;
      return;
    }

    if (!createRes.ok || createJson.status !== "SUCCESS") {
      createMsg.textContent = createJson.message || "Create failed";
      return;
    }

    const id = createJson.id;
    const files = document.getElementById("cImages").files;

    if (files.length > 0) {
      const fd = new FormData();
      fd.append("competition_id", id);

      for (let i = 0; i < files.length; i++) {
        fd.append("images[]", files[i]);
      }

      const uploadRes = await fetch("api_media_upload_image.php", {
        method: "POST",
        body: fd
      });

      const uploadRaw = await uploadRes.text();

      let uploadJson = {};
      try {
        uploadJson = JSON.parse(uploadRaw);
      } catch (err) {
        createMsg.textContent = "Invalid upload response: " + uploadRaw;
        return;
      }

      if (!uploadRes.ok || uploadJson.status !== "SUCCESS") {
        createMsg.textContent = uploadJson.message || "Image upload failed";
        return;
      }
    }

    closeCreate();
    showSuccess("Competition created successfully");
    setTimeout(() => location.reload(), 800);

  } catch (err) {
    createMsg.textContent = err.message || "Unexpected error";
  }
});

async function openView(btn){
  document.getElementById("mId").textContent = btn.dataset.id || "-";
  document.getElementById("mTitle").textContent = btn.dataset.title || "-";
  document.getElementById("mDate").textContent = btn.dataset.date || "-";
  document.getElementById("mPrice").textContent = btn.dataset.price || "-";
  document.getElementById("mDescription").textContent = btn.dataset.description || "-";
  document.getElementById("mTermCondition").textContent = btn.dataset.termcondition || "-";

  const s = btn.dataset.status || "-";
  const mStatus = document.getElementById("mStatus");
  mStatus.textContent = s;
  mStatus.style.fontWeight = "700";
  mStatus.style.color = (s === "OPEN") ? "green" : "red";

  const res = await fetch("api_competition_get.php?id=" + btn.dataset.id);
  const j = await res.json().catch(() => ({}));

  const box = document.getElementById("mImages");
  box.innerHTML = "";

  if (j.media && j.media.length) {
    j.media
      .filter(m => m.media_type === "IMAGE")
      .forEach(m => {
        const wrapper = document.createElement("div");
        wrapper.style.position = "relative";
        wrapper.style.cursor = "pointer";

        const img = document.createElement("img");
        img.src = m.media_url;
        img.style.width = "120px";
        img.style.height = "120px";
        img.style.objectFit = "cover";
        img.style.borderRadius = "8px";
        img.style.transition = "0.2s";
        img.onmouseenter = () => img.style.transform = "scale(1.05)";
        img.onmouseleave = () => img.style.transform = "scale(1)";
        img.onclick = () => openImg(m.media_url);

        wrapper.appendChild(img);
        box.appendChild(wrapper);
      });
  } else {
    box.innerHTML = "<span style='color:var(--color-text-light);'>No images</span>";
  }

  viewModal.classList.add("active");
}
</script>

</body>
</html>