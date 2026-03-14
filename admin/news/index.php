<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

$perPage = 10;
$page = max(1, (int)($_GET["page"] ?? 1));
$search = trim($_GET["search"] ?? "");

$where = "";
$params = [];

if ($search !== "") {
    $where = "WHERE (
        n.title LIKE :s
        OR n.subtitle LIKE :s
        OR n.author_name LIKE :s
        OR n.content_1 LIKE :s
        OR n.content_2 LIKE :s
    )";
    $params[":s"] = "%" . $search . "%";
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM news_posts n $where");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT
        n.id,
        n.title,
        n.author_name,
        n.news_date,
        n.is_published,
        n.view_count,
        n.created_at
    FROM news_posts n
    $where
    ORDER BY n.id DESC
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

function slugify($text): string {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'news';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>News Management</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
#editModal .modal__content,
#createModal .modal__content,
#viewModal .modal__content {
    max-height: 85vh;
    overflow: auto;
}
.small-muted {
    font-size: .85rem;
    color: #777;
}
.preview-image {
    max-width: 180px;
    border-radius: 10px;
    cursor: pointer;
    display: block;
}
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    margin-top: 10px;
}
.gallery-item {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    position: relative;
}
.gallery-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
    cursor: pointer;
}
.gallery-item .meta {
    padding: 8px;
    font-size: 12px;
    color: #666;
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
.image-delete-btn{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border:none;
    background:#dc2626;
    color:#fff;
    border-radius:8px;
    padding:6px 10px;
    cursor:pointer;
    font-size:12px;
}
.image-delete-btn:hover{
    background:#b91c1c;
}
.image-delete-round{
    position:absolute;
    top:8px;
    right:8px;
    width:30px;
    height:30px;
    border:none;
    border-radius:999px;
    background:rgba(220,38,38,.95);
    color:#fff;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 6px 14px rgba(0,0,0,.18);
}
.image-delete-round:hover{
    background:#b91c1c;
}
.image-card-inline{
    display:inline-block;
    position:relative;
}
.image-icon{
    width:14px;
    height:14px;
    display:inline-block;
}
</style>
</head>
<body class="admin-body">
<nav class="admin-navbar">
  <div class="admin-navbar__container">
    <h1 class="admin-navbar__title">News Management</h1>
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
    <h2 style="color:var(--color-navy);margin:0;">News Posts</h2>
    <a href="javascript:void(0);" class="btn btn--primary" onclick="openCreate()">+ Create</a>
  </div>

  <div id="successMessage" style="
    margin-bottom:20px;display:none;
    background:#28a745;color:#fff;
    padding:12px 30px;border-radius:8px;
    box-shadow:0 8px 20px rgba(0,0,0,0.15);
    font-weight:600;z-index:9999;">
    ✅ Success
  </div>

  <form method="get" style="margin-bottom:15px;display:flex;gap:8px;flex-wrap:wrap;">
    <input
      type="text"
      name="search"
      value="<?= e($search) ?>"
      placeholder="Search title / author / content..."
      style="padding:8px 10px;border-radius:6px;border:1px solid #ccc;width:320px;"
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
          <th style="width:140px;">Author</th>
          <th style="width:120px;">Date</th>
          <th style="width:90px;">Views</th>
          <th style="width:110px;">Status</th>
          <th style="width:160px;">Created</th>
          <th style="width:220px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" style="text-align:center;">No news found.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r["id"] ?></td>
              <td><?= e($r["title"]) ?></td>
              <td><?= e($r["author_name"] ?? "-") ?></td>
              <td><?= e($r["news_date"] ?? "-") ?></td>
              <td><?= (int)($r["view_count"] ?? 0) ?></td>
              <td>
                <?php if ((int)$r["is_published"] === 1): ?>
                  <span style="color:green;font-weight:600;">PUBLISHED</span>
                <?php else: ?>
                  <span style="color:#999;font-weight:600;">DRAFT</span>
                <?php endif; ?>
              </td>
              <td><?= e($r["created_at"] ?? "-") ?></td>
              <td class="actions">
                <a href="javascript:void(0);" class="btn-sm btn-view" onclick="openView(<?= (int)$r['id'] ?>)">View</a>
                <a href="javascript:void(0);" class="btn-sm btn-edit" onclick="openEdit(<?= (int)$r['id'] ?>)">Edit</a>
                <a href="../../user/news-detail.php?id=<?= (int)$r['id'] ?>&title=<?= urlencode(slugify($r['title'] ?? '')) ?>" target="_blank" class="btn-sm btn-view">Open</a>
                <a href="javascript:void(0);" class="btn-sm btn-delete" onclick="deleteNews(<?= (int)$r['id'] ?>)">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php $queryBase = $search !== "" ? "&search=" . urlencode($search) : ""; ?>
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 . $queryBase ?>">← Prev</a>
      <?php else: ?>
        <span class="disabled">← Prev</span>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i == $page): ?>
          <span class="active"><?= $i ?></span>
        <?php else: ?>
          <a href="?page=<?= $i . $queryBase ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 . $queryBase ?>">Next →</a>
      <?php else: ?>
        <span class="disabled">Next →</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<div id="viewModal" class="modal">
  <div class="modal__overlay" onclick="closeView()"></div>
  <div class="modal__content" style="max-width:920px;">
    <button class="modal__close" type="button" onclick="closeView()">×</button>
    <h3 id="vTitle">News</h3>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:14px 0;">
      <div><b>ID</b><div id="vId">-</div></div>
      <div><b>Date</b><div id="vDate">-</div></div>
      <div><b>Status</b><div id="vStatus">-</div></div>
      <div><b>Author</b><div id="vAuthor">-</div></div>
    </div>

    <div style="margin-top:10px;"><b>Subtitle</b><div id="vSubtitle"></div></div>
    <div style="margin-top:10px;"><b>Content Part 1</b><div id="vContent1" style="white-space:pre-wrap;"></div></div>
    <div style="margin-top:10px;"><b>Content Part 2</b><div id="vContent2" style="white-space:pre-wrap;"></div></div>

    <div style="margin-top:12px;"><b>Featured Image</b><div id="vFeatured"></div></div>
    <div style="margin-top:12px;"><b>Body Image</b><div id="vBodyImage"></div></div>
    <div style="margin-top:12px;"><b>Gallery Images</b><div id="vGallery"></div></div>
    <div style="margin-top:12px;"><b>External Video URL</b><div id="vExternalVideo"></div></div>
  </div>
</div>

<div id="editModal" class="modal">
  <div class="modal__overlay" onclick="closeEdit()"></div>
  <div class="modal__content" style="max-width:920px;">
    <button class="modal__close" type="button" onclick="closeEdit()">×</button>
    <h3>Edit News</h3>

    <form id="editForm" class="admin-form" style="padding:0;box-shadow:none;">
      <input type="hidden" id="eId">

      <div class="form-group">
        <label>Title</label>
        <input type="text" id="eTitle" required>
      </div>

      <div class="form-group">
        <label>Subtitle</label>
        <input type="text" id="eSubtitle">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Author Name</label>
          <input type="text" id="eAuthor">
        </div>
        <div class="form-group">
          <label>Date</label>
          <input type="date" id="eDate">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Status</label>
          <select id="ePub">
            <option value="1">PUBLISHED</option>
            <option value="0">DRAFT</option>
          </select>
        </div>
        <div class="form-group">
          <label>External Video URL</label>
          <input type="url" id="eExternalVideo">
        </div>
      </div>

      <div class="form-group">
        <label>Content Part 1</label>
        <textarea id="eContent1" rows="6"></textarea>
      </div>

      <div class="form-group">
        <label>Content Part 2</label>
        <textarea id="eContent2" rows="6"></textarea>
      </div>

      <div class="form-group">
        <label>Featured Image</label>
        <input type="file" id="eFeaturedImage" accept="image/*">
        <div id="eFeaturedPreview" style="margin-top:10px;"></div>
      </div>

      <div class="form-group">
        <label>Body Image (optional)</label>
        <input type="file" id="eBodyImage" accept="image/*">
        <div id="eBodyPreview" style="margin-top:10px;"></div>
      </div>

      <!-- <div class="form-group">
        <label>Gallery Images (max 4)</label>
        <input type="file" id="eGalleryImages" accept="image/*" multiple>
        <small style="color:#777;">You can upload up to 4 images total.</small>
        <div id="eGalleryPreview" style="margin-top:10px;"></div>
      </div> -->

      <div class="form-group">
  <label>Gallery Images (max 4)</label>

  <button type="button" class="btn-sm btn-edit" onclick="addEditGalleryInput()">+ Add New Image</button>
  <small style="color:#777;display:block;margin-top:8px;">Add one by one. Maximum 4 images total.</small>

  <div id="eGalleryInputs" style="margin-top:10px;"></div>
  <div id="eGalleryPreview" style="margin-top:10px;"></div>
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
  <div class="modal__content" style="max-width:920px;">
    <button class="modal__close" type="button" onclick="closeCreate()">×</button>
    <h3>Create News</h3>

    <form id="createForm" class="admin-form" style="padding:0;box-shadow:none;">
      <div class="form-group">
        <label>Title</label>
        <input type="text" id="cTitle" required>
      </div>

      <div class="form-group">
        <label>Subtitle</label>
        <input type="text" id="cSubtitle">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Author Name</label>
          <input type="text" id="cAuthor" placeholder="Admin">
        </div>
        <div class="form-group">
          <label>Date</label>
          <input type="date" id="cDate">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Status</label>
          <select id="cPub">
            <option value="1">PUBLISHED</option>
            <option value="0">DRAFT</option>
          </select>
        </div>
        <div class="form-group">
          <label>External Video URL</label>
          <input type="url" id="cExternalVideo">
        </div>
      </div>

      <div class="form-group">
        <label>Content Part 1</label>
        <textarea id="cContent1" rows="6"></textarea>
      </div>

      <div class="form-group">
        <label>Content Part 2</label>
        <textarea id="cContent2" rows="6"></textarea>
      </div>

      <div class="form-group">
        <label>Featured Image</label>
        <input type="file" id="cFeaturedImage" accept="image/*">
      </div>

      <div class="form-group">
        <label>Body Image (optional)</label>
        <input type="file" id="cBodyImage" accept="image/*">
      </div>

      <div class="form-group">
        <label>Gallery Images (max 4)</label>
        <input type="file" id="cGalleryImages" accept="image/*" multiple>
        <small style="color:#777;">You can upload up to 4 images.</small>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <span id="createMsg" style="color:var(--color-text-light);font-size:.9rem;"></span>
        <button type="button" class="btn btn--outline" onclick="closeCreate()">Cancel</button>
        <button type="submit" class="btn btn--primary">Create</button>
      </div>
    </form>
  </div>
</div>

<div id="mediaModal" class="modal">
  <div class="modal__overlay" onclick="closeMedia()"></div>
  <div class="modal__content" style="max-width:90%;background:#000;">
    <button class="modal__close" type="button" onclick="closeMedia()" style="color:#fff;">×</button>
    <div id="mediaContent" style="display:flex;justify-content:center;align-items:center;"></div>
  </div>
</div>

<script>
  let editNewGalleryFiles = [];
  const galleryFiles = editNewGalleryFiles;
function trashIcon() {
  return `
    <svg class="image-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M3 6h18"></path>
      <path d="M8 6V4h8v2"></path>
      <path d="M19 6l-1 14H6L5 6"></path>
      <path d="M10 11v6"></path>
      <path d="M14 11v6"></path>
    </svg>
  `;
}

function showSuccess(message) {
  const box = document.getElementById("successMessage");
  box.textContent = "✅ " + message;
  box.style.display = "block";
  setTimeout(() => box.style.display = "none", 2000);
}

function normalizeImagePath(path) {
  if (!path) return "";
  return String(path).replace(/^\/+/, "");
}

function openImg(url) {
  document.getElementById("mediaContent").innerHTML =
    `<img src="${url}" style="max-width:90vw;max-height:80vh;border-radius:10px;">`;
  document.getElementById("mediaModal").classList.add("active");
}

function closeMedia() {
  document.getElementById("mediaModal").classList.remove("active");
  document.getElementById("mediaContent").innerHTML = "";
}

function openCreate() {
  document.getElementById("createModal").classList.add("active");
  document.getElementById("createMsg").textContent = "";
  document.getElementById("createForm").reset();
}

function closeCreate() {
  document.getElementById("createModal").classList.remove("active");
}

function closeEdit() {
  document.getElementById("editModal").classList.remove("active");
}

function closeView() {
  document.getElementById("viewModal").classList.remove("active");
}

function renderGalleryHtml(gallery) {
  if (!gallery || !gallery.length) {
    return "<span class='small-muted'>No gallery images</span>";
  }

  let html = `<div class="gallery-grid existing-gallery-grid">`;
  gallery.forEach(item => {
    const img = normalizeImagePath(item.image_path || "");
    const gid = parseInt(item.id || 0, 10);

    html += `
      <div class="gallery-item existing-item" id="gallery-item-${gid}">
        <img src="${img}" alt="Gallery image" onclick="openImg('${img}')">
        <button type="button" class="image-delete-round" onclick="deleteNewsGalleryImage(${gid})" title="Delete image">✕</button>
        <div class="meta">Sort: ${parseInt(item.sort_order || 0, 10)}</div>
      </div>
    `;
  });
  html += `</div>`;
  return html;
}
async function deleteNewsGalleryImage(id){
  if(!confirm("Delete this gallery image?")) return;

  try{
    const res = await fetch("api_news_gallery_delete.php", {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify({ id })
    });

    const j = await res.json().catch(() => ({}));

    if(res.ok && j.status === "SUCCESS"){
      const box = document.getElementById("gallery-item-" + id);
      if(box) box.remove();
      showSuccess("Gallery image deleted");
    }else{
      alert(j.message || "Delete failed");
    }
  }catch(err){
    alert(err.message || "Unexpected error");
  }
}

async function deleteNewsSingleImage(type){
  const newsId = parseInt(document.getElementById("eId").value, 10);
  if(!newsId){
    alert("Invalid news ID");
    return;
  }
  if(!confirm("Delete this image?")) return;

  try{
    const res = await fetch("api_news_single_image_delete.php", {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify({
        news_id: newsId,
        type: type
      })
    });

    const j = await res.json().catch(() => ({}));

    if(res.ok && j.status === "SUCCESS"){
      if(type === "featured"){
        document.getElementById("eFeaturedPreview").innerHTML = "<span class='small-muted'>No featured image</span>";
        document.getElementById("eFeaturedImage").value = "";
      }
      if(type === "body"){
        document.getElementById("eBodyPreview").innerHTML = "<span class='small-muted'>No body image</span>";
        document.getElementById("eBodyImage").value = "";
      }
      showSuccess("Image deleted");
    }else{
      alert(j.message || "Delete failed");
    }
  }catch(err){
    alert(err.message || "Unexpected error");
  }
}

async function openView(id) {
  try {
    const res = await fetch("api_news_get.php?id=" + encodeURIComponent(id));
    const j = await res.json();

    if (!res.ok || j.status === "ERROR") {
      alert(j.message || "Failed to load news");
      return;
    }

    document.getElementById("vId").textContent = j.id || "-";
    document.getElementById("vTitle").textContent = j.title || "-";
    document.getElementById("vDate").textContent = j.news_date || "-";
    document.getElementById("vStatus").textContent = parseInt(j.is_published || 0, 10) === 1 ? "PUBLISHED" : "DRAFT";
    document.getElementById("vAuthor").textContent = j.author_name || "-";
    document.getElementById("vSubtitle").textContent = j.subtitle || "";
    document.getElementById("vContent1").textContent = j.content_1 || "";
    document.getElementById("vContent2").textContent = j.content_2 || "";

    const featuredBox = document.getElementById("vFeatured");
    featuredBox.innerHTML = j.featured_image
      ? `<img src="${normalizeImagePath(j.featured_image)}" class="preview-image" onclick="openImg('${normalizeImagePath(j.featured_image)}')">`
      : "<span class='small-muted'>No featured image</span>";

    const bodyBox = document.getElementById("vBodyImage");
    bodyBox.innerHTML = j.body_image
      ? `<img src="${normalizeImagePath(j.body_image)}" class="preview-image" onclick="openImg('${normalizeImagePath(j.body_image)}')">`
      : "<span class='small-muted'>No body image</span>";

    document.getElementById("vGallery").innerHTML = renderGalleryHtml(j.gallery || []);

    document.getElementById("vExternalVideo").innerHTML = j.external_video_url
      ? `<a href="${j.external_video_url}" target="_blank">${j.external_video_url}</a>`
      : "<span class='small-muted'>No external video URL</span>";

    document.getElementById("viewModal").classList.add("active");
  } catch (err) {
    alert(err.message || "Unexpected error");
  }
}

function renderSinglePreviewWithDelete(path, type, emptyText = "No image") {
  const img = normalizeImagePath(path || "");
  if (!img) {
    return `<span class="small-muted">${emptyText}</span>`;
  }

  return `
    <div class="image-card-inline">
      <img src="${img}" class="preview-image" onclick="openImg('${img}')">
      <div style="margin-top:8px;">
        <button type="button" class="image-delete-btn" onclick="deleteNewsSingleImage('${type}')">
          ${trashIcon()}
          Delete image
        </button>
      </div>
    </div>
  `;
}

async function openEdit(id) {
  try {

    const res = await fetch("api_news_get.php?id=" + encodeURIComponent(id));
    const j = await res.json();

    if (!res.ok || j.status === "ERROR") {
      alert(j.message || "Failed to load news");
      return;
    }

    document.getElementById("eId").value = j.id || "";
    document.getElementById("eTitle").value = j.title || "";
    document.getElementById("eSubtitle").value = j.subtitle || "";
    document.getElementById("eAuthor").value = j.author_name || "";
    document.getElementById("eDate").value = j.news_date || "";
    document.getElementById("ePub").value = String(j.is_published ?? 0);
    document.getElementById("eContent1").value = j.content_1 || "";
    document.getElementById("eContent2").value = j.content_2 || "";
    document.getElementById("eExternalVideo").value = j.external_video_url || "";

    document.getElementById("eFeaturedImage").value = "";
    document.getElementById("eBodyImage").value = "";

    const galleryInputs = document.getElementById("eGalleryInputs");
    if (galleryInputs) {
      galleryInputs.innerHTML = "";
    }

    document.getElementById("eFeaturedPreview").innerHTML =
      renderSinglePreviewWithDelete(j.featured_image, "featured", "No featured image");

    document.getElementById("eBodyPreview").innerHTML =
      renderSinglePreviewWithDelete(j.body_image, "body", "No body image");

  editNewGalleryFiles = [];
  document.getElementById("eGalleryPreview").innerHTML = renderGalleryHtml(j.gallery || []);

    document.getElementById("editMsg").textContent = "";
    document.getElementById("editModal").classList.add("active");

  } catch (err) {
    alert(err.message || "Unexpected error");
  }
}
document.getElementById("createForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  const createMsg = document.getElementById("createMsg");
  createMsg.textContent = "Creating...";

  try {
    const payload = {
      title: document.getElementById("cTitle").value.trim(),
      subtitle: document.getElementById("cSubtitle").value.trim(),
      author_name: document.getElementById("cAuthor").value.trim(),
      news_date: document.getElementById("cDate").value,
      is_published: parseInt(document.getElementById("cPub").value, 10) || 0,
      content_1: document.getElementById("cContent1").value.trim(),
      content_2: document.getElementById("cContent2").value.trim(),
      external_video_url: document.getElementById("cExternalVideo").value.trim()
    };

    const createRes = await fetch("api_news_create.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    const rawText = await createRes.text();
    let createJson = {};

    try {
      createJson = JSON.parse(rawText);
    } catch (e) {
      createMsg.textContent = "api_news_create.php did not return valid JSON: " + rawText;
      return;
    }

    if (!createRes.ok || createJson.status !== "SUCCESS") {
      createMsg.textContent = createJson.message || "Create failed";
      return;
    }

    const newsId = parseInt(createJson.id, 10);
    if (!newsId || newsId <= 0) {
      createMsg.textContent = "Invalid created news ID";
      return;
    }

    const featured = document.getElementById("cFeaturedImage").files[0];
    const bodyImage = document.getElementById("cBodyImage").files[0];
    const galleryFiles = document.getElementById("cGalleryImages").files;

    if (galleryFiles.length > 4) {
      createMsg.textContent = "Gallery allows maximum 4 images only";
      return;
    }

    if (featured || bodyImage || galleryFiles.length) {
      const fd = new FormData();
      fd.append("news_id", String(newsId));

      if (featured) fd.append("featured_image", featured);
      if (bodyImage) fd.append("body_image", bodyImage);

      for (let i = 0; i < galleryFiles.length; i++) {
        fd.append("gallery_images[]", galleryFiles[i]);
      }

      const uploadRes = await fetch("api_news_media_upload.php", {
        method: "POST",
        body: fd
      });

      const uploadRaw = await uploadRes.text();
      let uploadJson = {};

      try {
        uploadJson = JSON.parse(uploadRaw);
      } catch (e) {
        createMsg.textContent = "Upload API invalid JSON: " + uploadRaw;
        return;
      }

      if (!uploadRes.ok || uploadJson.status !== "SUCCESS") {
        createMsg.textContent = uploadJson.message || "Image upload failed";
        return;
      }
    }

    showSuccess("News created successfully");
    closeCreate();
    setTimeout(() => location.reload(), 800);
  } catch (err) {
    createMsg.textContent = err.message || "Unexpected error";
  }
});

document.getElementById("editForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  const editMsg = document.getElementById("editMsg");
  editMsg.textContent = "Saving...";

  try {
    const newsId = parseInt(document.getElementById("eId").value, 10);
    if (!newsId || newsId <= 0) {
      editMsg.textContent = "Invalid news ID";
      return;
    }

    const payload = {
      id: newsId,
      title: document.getElementById("eTitle").value.trim(),
      subtitle: document.getElementById("eSubtitle").value.trim(),
      author_name: document.getElementById("eAuthor").value.trim(),
      news_date: document.getElementById("eDate").value,
      is_published: parseInt(document.getElementById("ePub").value, 10) || 0,
      content_1: document.getElementById("eContent1").value.trim(),
      content_2: document.getElementById("eContent2").value.trim(),
      external_video_url: document.getElementById("eExternalVideo").value.trim()
    };

    const updateRes = await fetch("api_news_update.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    const updateRaw = await updateRes.text();
    let updateJson = {};

    try {
      updateJson = JSON.parse(updateRaw);
    } catch (e) {
      editMsg.textContent = "api_news_update.php invalid JSON: " + updateRaw;
      return;
    }

    if (!updateRes.ok || updateJson.status !== "SUCCESS") {
      editMsg.textContent = updateJson.message || "Update failed";
      return;
    }

    const featured = document.getElementById("eFeaturedImage").files[0] || null;
    const bodyImage = document.getElementById("eBodyImage").files[0] || null;
    const galleryFiles = Array.isArray(editNewGalleryFiles) ? editNewGalleryFiles : [];

    const existingCount = document.querySelectorAll("#eGalleryPreview .gallery-item.existing-item").length;

    if ((existingCount + galleryFiles.length) > 4) {
      editMsg.textContent = "Gallery allows maximum 4 images only";
      return;
    }

    if (featured || bodyImage || galleryFiles.length > 0) {
      const fd = new FormData();
      fd.append("news_id", String(newsId));

      if (featured) fd.append("featured_image", featured);
      if (bodyImage) fd.append("body_image", bodyImage);

      galleryFiles.forEach(file => {
        fd.append("gallery_images[]", file);
      });

      const uploadRes = await fetch("api_news_media_upload.php", {
        method: "POST",
        body: fd
      });

      const uploadRaw = await uploadRes.text();
      let uploadJson = {};

      try {
        uploadJson = JSON.parse(uploadRaw);
      } catch (e) {
        editMsg.textContent = "Upload API invalid JSON: " + uploadRaw;
        return;
      }

      if (!uploadRes.ok || uploadJson.status !== "SUCCESS") {
        editMsg.textContent = uploadJson.message || "Image upload failed";
        return;
      }
    }

    editNewGalleryFiles = [];
    showSuccess("News updated successfully");
    closeEdit();
    setTimeout(() => location.reload(), 800);

  } catch (err) {
    editMsg.textContent = err.message || "Unexpected error";
  }
});
async function deleteNews(id) {
  if (!confirm("Delete this news post?")) return;

  try {
    const res = await fetch("api_news_delete.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });

    const raw = await res.text();
    let j = {};

    try {
      j = JSON.parse(raw);
    } catch (e) {
      alert("api_news_delete.php did not return valid JSON: " + raw);
      return;
    }

    if (res.ok && j.status === "SUCCESS") {
      showSuccess("News deleted successfully");
      setTimeout(() => location.reload(), 600);
    } else {
      alert(j.message || ("Error " + res.status));
    }
  } catch (err) {
    alert(err.message || "Unexpected error");
  }
}

document.getElementById("eFeaturedImage").addEventListener("change", function () {
  const file = this.files[0];
  if (!file) return;
  const url = URL.createObjectURL(file);
  document.getElementById("eFeaturedPreview").innerHTML =
    `<img src="${url}" class="preview-image" onclick="openImg('${url}')">`;
});

document.getElementById("eBodyImage").addEventListener("change", function () {
  const file = this.files[0];
  if (!file) return;
  const url = URL.createObjectURL(file);
  document.getElementById("eBodyPreview").innerHTML =
    `<img src="${url}" class="preview-image" onclick="openImg('${url}')">`;
});

function previewEditGalleryFile(input) {

  const file = input.files && input.files[0];
  if (!file) return;

  const previewBox = document.getElementById("eGalleryPreview");

  const url = URL.createObjectURL(file);

  const item = document.createElement("div");
  item.className = "gallery-item";

  item.innerHTML = `
      <img src="${url}" alt="Gallery image" onclick="openImg('${url}')">
      <div class="meta">New image</div>
  `;

  previewBox.appendChild(item);
}
function addEditGalleryInput() {
  const existingCount = document.querySelectorAll("#eGalleryPreview .gallery-item.existing-item").length;
  const total = existingCount + editNewGalleryFiles.length;

  if (total >= 4) {
    alert("Maximum 4 gallery images only");
    return;
  }

  const input = document.createElement("input");
  input.type = "file";
  input.accept = "image/*";
  input.style.display = "none";

  input.onchange = function () {
    const file = input.files && input.files[0];
    if (!file) return;

    const currentExisting = document.querySelectorAll("#eGalleryPreview .gallery-item.existing-item").length;
    if ((currentExisting + editNewGalleryFiles.length) >= 4) {
      alert("Maximum 4 gallery images only");
      return;
    }

    editNewGalleryFiles.push(file);
    renderEditGalleryPreview();
  };

  input.click();
}

function renderEditGalleryPreview(existingHtml = null) {
  const box = document.getElementById("eGalleryPreview");

  if (existingHtml !== null) {
    box.innerHTML = existingHtml;
  }

  const oldNewGrid = box.querySelector(".new-gallery-grid");
  if (oldNewGrid) oldNewGrid.remove();

  if (!editNewGalleryFiles.length) return;

  const grid = document.createElement("div");
  grid.className = "gallery-grid new-gallery-grid";
  grid.style.marginTop = "12px";

  editNewGalleryFiles.forEach((file, index) => {
    const url = URL.createObjectURL(file);

    const item = document.createElement("div");
    item.className = "gallery-item";

    item.innerHTML = `
      <img src="${url}" alt="Gallery image" onclick="openImg('${url}')">
      <button type="button" class="image-delete-round" onclick="removeEditNewGalleryImage(${index})" title="Remove image">✕</button>
      <div class="meta">New image ${index + 1}</div>
    `;

    grid.appendChild(item);
  });

  box.appendChild(grid);
}

function removeEditNewGalleryImage(index) {
  editNewGalleryFiles.splice(index, 1);
  renderEditGalleryPreview();
}

function removeEditGalleryInput(btn) {
  btn.closest(".gallery-upload-item").remove();
}

function previewEditGalleryFile(input) {

  const file = input.files && input.files[0];
  if (!file) return;

  const url = URL.createObjectURL(file);

  let grid = document.querySelector("#eGalleryPreview .gallery-grid");

  if (!grid) {
    grid = document.createElement("div");
    grid.className = "gallery-grid";
    document.getElementById("eGalleryPreview").appendChild(grid);
  }

  const item = document.createElement("div");
  item.className = "gallery-item";

  item.innerHTML = `
    <img src="${url}" alt="Gallery image" onclick="openImg('${url}')">
    <div class="meta">New image</div>
  `;

  grid.appendChild(item);
}
</script>
</body>
</html>