<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

$rows = $pdo->query("
    SELECT id,
           hero_title_en, hero_title_kh,
           hero_subtitle_en, hero_subtitle_kh,
           updated_at
    FROM site_settings
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Site Settings</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body class="admin-body">

<nav class="admin-navbar">
  <div class="admin-navbar__container">
    <h1 class="admin-navbar__title">Site Settings Management</h1>
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
    <h2 style="color:var(--color-navy);margin:0;">Site Settings</h2>
    <a href="javascript:void(0);" class="btn btn--primary" onclick="openCreateSetting()">+ Create</a>
  </div>

  <div id="successMessage" style="
    margin-bottom:20px;
    display:none;
    background:#28a745;color:#fff;
    padding:12px 30px;border-radius:8px;
    box-shadow:0 8px 20px rgba(0,0,0,0.15);
    font-weight:600;z-index:9999;">
    ✅ Success
  </div>

  <div class="registrations-table">
    <table>
      <thead>
        <tr>
          <th style="width:60px;">ID</th>
          <th>Hero Title EN</th>
          <th>Hero Title KH</th>
          <th>Hero Subtitle EN</th>
          <th style="width:180px;">Updated At</th>
          <th style="width:180px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($rows)): ?>
          <tr><td colspan="6" style="text-align:center;">No settings found.</td></tr>
        <?php endif; ?>

        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= (int)$r["id"] ?></td>
            <td><?= htmlspecialchars($r["hero_title_en"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["hero_title_kh"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["hero_subtitle_en"] ?? "") ?></td>
            <td><?= htmlspecialchars($r["updated_at"] ?? "") ?></td>
            <td class="actions">
              <a href="javascript:void(0);" class="btn-sm btn-view"
                 onclick="openViewSetting(<?= (int)$r['id'] ?>)">View</a>

              <a href="javascript:void(0);" class="btn-sm btn-edit"
                 onclick="openEditSetting(<?= (int)$r['id'] ?>)">Edit</a>

              <a href="javascript:void(0);" class="btn-sm btn-delete"
                 onclick="deleteSetting(<?= (int)$r['id'] ?>)">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- VIEW MODAL -->
<div id="viewSettingModal" class="modal">
  <div class="modal__overlay" onclick="closeViewSetting()"></div>
  <div class="modal__content" style="max-width:800px;">
    <button class="modal__close" type="button" onclick="closeViewSetting()">×</button>
    <h3 style="margin-bottom:15px;">View Site Setting</h3>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div><b>ID</b><div id="vs_id">-</div></div>
      <div><b>Updated At</b><div id="vs_updated_at">-</div></div>

      <div><b>Hero Title EN</b><div id="vs_hero_title_en">-</div></div>
      <div><b>Hero Title KH</b><div id="vs_hero_title_kh">-</div></div>

      <div><b>Hero Subtitle EN</b><div id="vs_hero_subtitle_en">-</div></div>
      <div><b>Hero Subtitle KH</b><div id="vs_hero_subtitle_kh">-</div></div>

      <div style="grid-column:1 / span 2;"><b>Hero Description EN</b><div id="vs_hero_description_en" style="white-space:pre-wrap;">-</div></div>
      <div style="grid-column:1 / span 2;"><b>Hero Description KH</b><div id="vs_hero_description_kh" style="white-space:pre-wrap;">-</div></div>

      <div style="grid-column:1 / span 2;"><b>About Text EN</b><div id="vs_about_text_en" style="white-space:pre-wrap;">-</div></div>
      <div style="grid-column:1 / span 2;"><b>About Text KH</b><div id="vs_about_text_kh" style="white-space:pre-wrap;">-</div></div>
    </div>

    <div style="margin-top:18px;display:flex;justify-content:flex-end;">
      <button class="btn btn--outline" type="button" onclick="closeViewSetting()">Close</button>
    </div>
  </div>
</div>

<!-- CREATE MODAL -->
<div id="createSettingModal" class="modal">
  <div class="modal__overlay" onclick="closeCreateSetting()"></div>
  <div class="modal__content" style="max-width:800px;">
    <button class="modal__close" onclick="closeCreateSetting()">×</button>
    <h3>Create Site Setting</h3>

    <form id="createSettingForm" class="admin-form" style="padding:0;box-shadow:none;">
      <div class="form-group">
        <label>Hero Title EN</label>
        <input type="text" id="c_hero_title_en">
      </div>

      <div class="form-group">
        <label>Hero Title KH</label>
        <input type="text" id="c_hero_title_kh">
      </div>

      <div class="form-group">
        <label>Hero Subtitle EN</label>
        <input type="text" id="c_hero_subtitle_en">
      </div>

      <div class="form-group">
        <label>Hero Subtitle KH</label>
        <input type="text" id="c_hero_subtitle_kh">
      </div>

      <div class="form-group">
        <label>Hero Description EN</label>
        <textarea id="c_hero_description_en" rows="3"></textarea>
      </div>

      <div class="form-group">
        <label>Hero Description KH</label>
        <textarea id="c_hero_description_kh" rows="3"></textarea>
      </div>

      <div class="form-group">
        <label>About Text EN</label>
        <textarea id="c_about_text_en" rows="4"></textarea>
      </div>

      <div class="form-group">
        <label>About Text KH</label>
        <textarea id="c_about_text_kh" rows="4"></textarea>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn--outline" onclick="closeCreateSetting()">Cancel</button>
        <button type="submit" class="btn btn--primary">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div id="editSettingModal" class="modal">
  <div class="modal__overlay" onclick="closeEditSetting()"></div>
  <div class="modal__content" style="max-width:800px;">
    <button class="modal__close" onclick="closeEditSetting()">×</button>
    <h3>Edit Site Setting</h3>

    <form id="editSettingForm" class="admin-form" style="padding:0;box-shadow:none;">
      <input type="hidden" id="e_id">

      <div class="form-group">
        <label>Hero Title EN</label>
        <input type="text" id="e_hero_title_en">
      </div>

      <div class="form-group">
        <label>Hero Title KH</label>
        <input type="text" id="e_hero_title_kh">
      </div>

      <div class="form-group">
        <label>Hero Subtitle EN</label>
        <input type="text" id="e_hero_subtitle_en">
      </div>

      <div class="form-group">
        <label>Hero Subtitle KH</label>
        <input type="text" id="e_hero_subtitle_kh">
      </div>

      <div class="form-group">
        <label>Hero Description EN</label>
        <textarea id="e_hero_description_en" rows="3"></textarea>
      </div>

      <div class="form-group">
        <label>Hero Description KH</label>
        <textarea id="e_hero_description_kh" rows="3"></textarea>
      </div>

      <div class="form-group">
        <label>About Text EN</label>
        <textarea id="e_about_text_en" rows="4"></textarea>
      </div>

      <div class="form-group">
        <label>About Text KH</label>
        <textarea id="e_about_text_kh" rows="4"></textarea>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn--outline" onclick="closeEditSetting()">Cancel</button>
        <button type="submit" class="btn btn--primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
function showSuccess(message){
  const box = document.getElementById("successMessage");
  box.textContent = "✅ " + message;
  box.style.display = "block";
  setTimeout(() => box.style.display = "none", 2000);
}

function openCreateSetting(){
  document.getElementById("createSettingModal").classList.add("active");
}
function closeCreateSetting(){
  document.getElementById("createSettingModal").classList.remove("active");
}

async function openViewSetting(id){
  const res = await fetch("api_site_settings_get.php?id=" + id);
  const j = await res.json();

  document.getElementById("vs_id").textContent = j.id || "-";
  document.getElementById("vs_updated_at").textContent = j.updated_at || "-";
  document.getElementById("vs_hero_title_en").textContent = j.hero_title_en || "-";
  document.getElementById("vs_hero_title_kh").textContent = j.hero_title_kh || "-";
  document.getElementById("vs_hero_subtitle_en").textContent = j.hero_subtitle_en || "-";
  document.getElementById("vs_hero_subtitle_kh").textContent = j.hero_subtitle_kh || "-";
  document.getElementById("vs_hero_description_en").textContent = j.hero_description_en || "-";
  document.getElementById("vs_hero_description_kh").textContent = j.hero_description_kh || "-";
  document.getElementById("vs_about_text_en").textContent = j.about_text_en || "-";
  document.getElementById("vs_about_text_kh").textContent = j.about_text_kh || "-";

  document.getElementById("viewSettingModal").classList.add("active");
}
function closeViewSetting(){
  document.getElementById("viewSettingModal").classList.remove("active");
}

async function openEditSetting(id){
  const res = await fetch("api_site_settings_get.php?id=" + id);
  const j = await res.json();

  document.getElementById("e_id").value = j.id || "";
  document.getElementById("e_hero_title_en").value = j.hero_title_en || "";
  document.getElementById("e_hero_title_kh").value = j.hero_title_kh || "";
  document.getElementById("e_hero_subtitle_en").value = j.hero_subtitle_en || "";
  document.getElementById("e_hero_subtitle_kh").value = j.hero_subtitle_kh || "";
  document.getElementById("e_hero_description_en").value = j.hero_description_en || "";
  document.getElementById("e_hero_description_kh").value = j.hero_description_kh || "";
  document.getElementById("e_about_text_en").value = j.about_text_en || "";
  document.getElementById("e_about_text_kh").value = j.about_text_kh || "";

  document.getElementById("editSettingModal").classList.add("active");
}
function closeEditSetting(){
  document.getElementById("editSettingModal").classList.remove("active");
}

document.getElementById("createSettingForm").addEventListener("submit", async function(e){
  e.preventDefault();

  const fd = new FormData();
  fd.append("hero_title_en", document.getElementById("c_hero_title_en").value);
  fd.append("hero_title_kh", document.getElementById("c_hero_title_kh").value);
  fd.append("hero_subtitle_en", document.getElementById("c_hero_subtitle_en").value);
  fd.append("hero_subtitle_kh", document.getElementById("c_hero_subtitle_kh").value);
  fd.append("hero_description_en", document.getElementById("c_hero_description_en").value);
  fd.append("hero_description_kh", document.getElementById("c_hero_description_kh").value);
  fd.append("about_text_en", document.getElementById("c_about_text_en").value);
  fd.append("about_text_kh", document.getElementById("c_about_text_kh").value);

  const res = await fetch("api_site_settings_create.php", {
    method: "POST",
    body: fd
  });

  const j = await res.json().catch(()=>({}));
  if(res.ok && j.status === "SUCCESS"){
    showSuccess("Created successfully");
    closeCreateSetting();
    setTimeout(()=> location.reload(), 700);
  }else{
    alert(j.message || "Error");
  }
});

document.getElementById("editSettingForm").addEventListener("submit", async function(e){
  e.preventDefault();

  const fd = new FormData();
  fd.append("id", document.getElementById("e_id").value);
  fd.append("hero_title_en", document.getElementById("e_hero_title_en").value);
  fd.append("hero_title_kh", document.getElementById("e_hero_title_kh").value);
  fd.append("hero_subtitle_en", document.getElementById("e_hero_subtitle_en").value);
  fd.append("hero_subtitle_kh", document.getElementById("e_hero_subtitle_kh").value);
  fd.append("hero_description_en", document.getElementById("e_hero_description_en").value);
  fd.append("hero_description_kh", document.getElementById("e_hero_description_kh").value);
  fd.append("about_text_en", document.getElementById("e_about_text_en").value);
  fd.append("about_text_kh", document.getElementById("e_about_text_kh").value);

  const res = await fetch("api_site_settings_update.php", {
    method: "POST",
    body: fd
  });

  const j = await res.json().catch(()=>({}));
  if(res.ok && j.status === "SUCCESS"){
    showSuccess("Updated successfully");
    closeEditSetting();
    setTimeout(()=> location.reload(), 700);
  }else{
    alert(j.message || "Error");
  }
});

async function deleteSetting(id){
  if(!confirm("Delete this setting?")) return;

  const res = await fetch("api_site_settings_delete.php", {
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body: JSON.stringify({id})
  });

  const j = await res.json().catch(()=>({}));
  if(res.ok && j.status === "SUCCESS"){
    showSuccess("Deleted successfully");
    setTimeout(()=> location.reload(), 600);
  }else{
    alert(j.message || "Error");
  }
}
</script>

</body>
</html>