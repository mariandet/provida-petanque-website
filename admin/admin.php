<!-- <?php require_once __DIR__ . "/../auth.php"; ?>

<h1>Admin Dashboard</h1>

<ul>
  <li><a href="competitions_list.php">Competition List</a></li>
  <li><a href="competition_create.php">Create Competition</a></li>
  <li><a href="/provida-club/competition.php">Public Competition Page</a></li>
  <li><a href="competition/index.php">Competition CRUD</a></li>
  <li><a href="/provida-club/logout.php">Logout</a></li>

</ul> -->
<?php
require_once __DIR__ . "/../auth.php"; // session check
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Provida Admin Dashboard</title>
  <link rel="stylesheet" href="assets/style.css">
</head>

<body class="admin-body">

  <!-- Top Admin Navbar -->
  <nav class="admin-navbar">
    <div class="admin-navbar__container">
      <h1 class="admin-navbar__title">PROVIDA Admin Dashboard</h1>

      <div style="display:flex;gap:10px;align-items:center;">
        <a class="btn btn--outline" href="../user/index.php">Public Site</a>
        <a class="btn btn--gold" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <!-- Dashboard Cards -->
  <main class="admin-dashboard">
    <h2 style="color: var(--color-navy); margin-bottom: 10px;">Control Panel</h2>
    <p style="color: var(--color-text-light); margin-bottom: 20px;">
      Choose a module to manage.
    </p>

    <div class="control-panel">

      <!-- Competitions -->
      <div class="control-item">
        <label>🏆 Competitions</label>
        <p class="status-text">Create, view, edit, delete competitions (CRUD with popup).</p>
        <a class="btn btn--outline" href="competition/index.php">Open</a>
      </div>

      <!-- Registrations -->
      <div class="control-item">
        <label>📝 Registrations</label>
        <p class="status-text">View all registrations and export later.</p>
        <a class="btn btn--outline" href="registrations/index.php">Open</a>
      </div>

      <!-- Blog / News -->
      <div class="control-item">
        <label>📰 News / Blog</label>
        <p class="status-text">Create and manage news posts.</p>
        <a class="btn btn--outline" href="news/index.php">Open</a>
      </div>
      <div class="control-item">
        <label>Site image </label>
        <p class="status-text">Change/Replace images</p>
        <a class="btn btn--outline" href="siteImage/site_images_index.php">Open</a>
      </div>
      <!-- Settings -->
      <div class="control-item">
        <label>⚙️ Settings</label>
        <p class="status-text">Control registration open/close and site info.</p>
        <a class="btn btn--outline" href="setting/index.php">Open</a>
      </div>

    </div>

    <div style="margin-top:25px;">
      <a class="btn btn--outline" href="/provida-club-login/admin/admin.php">Refresh</a>
    </div>
  </main>

</body>
</html>