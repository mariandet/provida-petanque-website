<?php
require_once "../config/db.php";

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    http_response_code(404);
    exit("News not found.");
}

$stmt = $pdo->prepare("
    SELECT
        id,
        title,
        subtitle,
        author_name,
        news_date,
        content,
        featured_image,
        body_image,
        external_video_url,
        is_published,
        view_count,
        created_at
    FROM news_posts
    WHERE id = ? AND is_published = 1
    LIMIT 1
");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    exit("News not found.");
}

/* update view count */
$pdo->prepare("
    UPDATE news_posts
    SET view_count = view_count + 1
    WHERE id = ?
")->execute([$post["id"]]);

/* popular news */
$popularStmt = $pdo->prepare("
    SELECT id, title, news_date, featured_image
    FROM news_posts
    WHERE is_published = 1 AND id <> ?
    ORDER BY view_count DESC, id DESC
    LIMIT 4
");
$popularStmt->execute([$post["id"]]);
$popularNews = $popularStmt->fetchAll(PDO::FETCH_ASSOC);

/* latest news */
$latestStmt = $pdo->prepare("
    SELECT id, title, news_date, featured_image
    FROM news_posts
    WHERE is_published = 1 AND id <> ?
    ORDER BY news_date DESC, id DESC
    LIMIT 4
");
$latestStmt->execute([$post["id"]]);
$latestNews = $latestStmt->fetchAll(PDO::FETCH_ASSOC);

/* helpers */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function asset_url(?string $path, string $fallback = "images/news-placeholder.jpg"): string {
    $path = trim((string)$path);
    if ($path === "") {
        return $fallback;
    }
    return "/" . ltrim($path, "/");
}

function renderSidebarItems(array $items): string {
    if (empty($items)) {
        return '<div class="sidebar-empty">No news available.</div>';
    }

    $html = '';
    foreach ($items as $item) {
        $thumb = asset_url($item["featured_image"] ?? "");
        $html .= '
            <a class="sidebar-item" href="news-detail.php?id=' . (int)$item["id"] . '">
                <img class="sidebar-thumb" src="' . e($thumb) . '" alt="' . e($item["title"] ?? "News") . '">
                <div class="sidebar-text">
                    <span class="sidebar-item-title">' . e($item["title"] ?? "") . '</span>
                    <span class="sidebar-item-date">' . e($item["news_date"] ?? "") . '</span>
                </div>
            </a>
        ';
    }
    return $html;
}

function getEmbedUrl(string $url): string {
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    if (preg_match('~youtube\.com/watch\?v=([^&]+)~i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }

    if (preg_match('~youtu\.be/([^?&]+)~i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }

    if (preg_match('~vimeo\.com/(\d+)~i', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }

    return '';
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$baseUrl = ($isHttps ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$pageUrl = $baseUrl . '/user/news-detail.php?id=' . (int)$post['id'];

$metaTitle = $post["title"] ?: "News";
$metaDescription = trim((string)($post["subtitle"] ?? "")) !== ""
    ? $post["subtitle"]
    : "Latest update from Provida Pétanque Club.";
$shareImage = !empty($post["featured_image"])
    ? $baseUrl . "/" . ltrim($post["featured_image"], "/")
    : "";

$embedUrl = getEmbedUrl((string)($post["external_video_url"] ?? ""));
$featuredImageUrl = "../../../provida-club-login/admin/news/" . asset_url($post["featured_image"] ?? "", "");
$bodyImageUrl = asset_url($post["body_image"] ?? "", "");

$galleryStmt = $pdo->prepare("
    SELECT image_path
    FROM news_gallery
    WHERE news_id = ?
    ORDER BY sort_order ASC, id ASC
");
$galleryStmt->execute([$post["id"]]);
$galleryImages = $galleryStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($metaTitle) ?> - Provida Pétanque Club</title>
  <meta name="description" content="<?= e($metaDescription) ?>">

  <meta property="og:title" content="<?= e($metaTitle) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <meta property="og:type" content="article">
  <meta property="og:url" content="<?= e($pageUrl) ?>">
  <?php if ($shareImage !== ''): ?>
    <meta property="og:image" content="<?= e($shareImage) ?>">
  <?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">

  <link rel="stylesheet" href="style.css">

<style>
  :root{
    --news-bg: #f8f6f1;
    --news-card: #ffffff;
    --news-text: #1f2937;
    --news-muted: #6b7280;
    --news-border: #e5e7eb;
    --news-accent: #1a2847;
    --news-gold: #d4af37;
    --news-radius: 18px;
    --news-shadow: 0 8px 24px rgba(0,0,0,0.08);
  }

  body{
    background: var(--news-bg);
    color: var(--news-text);
  }

  .page-header{
    background: linear-gradient(135deg, #1a2847, #2a3a52);
    color: #fff;
    padding: 60px 0 50px;
    text-align: center;
  }

  .page-title{
    font-size: clamp(2rem, 4vw, 3rem);
    margin: 0 0 10px;
    font-weight: 800;
    color: #fff;
  }

  .page-subtitle{
    margin: 0;
    color: rgba(255,255,255,0.9);
    font-size: 1.05rem;
  }

  .section-light{
    padding: 50px 0 70px;
  }

  .content-section{
    background: transparent;
  }

  .news-layout{
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 30px;
    align-items: start;
  }

  .news-article-page{
    background: var(--news-card);
    border-radius: var(--news-radius);
    overflow: hidden;
    box-shadow: var(--news-shadow);
    border: 1px solid rgba(0,0,0,0.05);
  }

  .news-article-top{
    padding: 28px 28px 20px;
  }

  .news-back{
    display: inline-block;
    margin-bottom: 18px;
    color: var(--news-accent);
    text-decoration: none;
    font-weight: 700;
  }

  .news-back:hover{
    color: var(--news-gold);
  }

  .news-article-title{
    font-size: clamp(1.9rem, 3vw, 2.6rem);
    line-height: 1.2;
    margin: 0 0 14px;
    color: var(--news-accent);
    font-weight: 800;
  }

  .news-article-meta{
    display: flex;
    flex-wrap: wrap;
    gap: 10px 18px;
    color: var(--news-muted);
    font-size: 0.95rem;
    padding-bottom: 4px;
  }

  .news-meta-item strong{
    color: var(--news-accent);
  }

  .news-article-hero{
    width: 100%;
    background: #edf2f7;
  }

  .news-article-hero img{
    width: 100%;
    max-height: 520px;
    object-fit: cover;
    display: block;
  }

  .news-article-content{
    padding: 28px;
  }

  .news-article-body{
    font-size: 1.05rem;
    line-height: 1.9;
    color: #374151;
    white-space: pre-wrap;
  }

  .news-body-image{
    margin: 24px 0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  }

  .news-body-image img{
    width: 100%;
    display: block;
    object-fit: cover;
  }

  .news-section-title{
    margin: 30px 0 14px;
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--news-accent);
    border-left: 4px solid var(--news-gold);
    padding-left: 12px;
  }

  .news-gallery-grid{
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }

  .news-gallery-item{
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  }

  .news-gallery-item img{
    width: 100%;
    height: 260px;
    object-fit: cover;
    display: block;
  }

  .news-video-box{
    border-radius: 16px;
    overflow: hidden;
    background: #000;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  }

  .news-video-frame{
    position: relative;
    padding-top: 56.25%;
  }

  .news-video-frame iframe{
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
  }

  .news-share-row{
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 16px;
    align-items: center;
    margin-top: 30px;
    padding-top: 22px;
    border-top: 1px solid var(--news-border);
  }

  .news-author{
    font-weight: 700;
    color: var(--news-accent);
  }

  .news-share{
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
  }

  .news-share-label{
    font-weight: 700;
    color: var(--news-muted);
  }

  .news-share-btn{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 14px;
    border-radius: 999px;
    background: #fff;
    border: 1px solid var(--news-border);
    color: var(--news-accent);
    text-decoration: none;
    font-weight: 700;
    transition: 0.25s ease;
  }

  .news-share-btn:hover{
    background: var(--news-accent);
    color: #fff;
    border-color: var(--news-accent);
  }

  .news-sidebar{
    display: flex;
    flex-direction: column;
    gap: 22px;
  }

  .sidebar-card{
    background: #fff;
    border-radius: 18px;
    padding: 20px;
    box-shadow: var(--news-shadow);
    border: 1px solid rgba(0,0,0,0.05);
  }

  .sidebar-title{
    margin: 0 0 16px;
    font-size: 1.1rem;
    color: var(--news-accent);
    font-weight: 800;
  }

  .sidebar-item{
    display: grid;
    grid-template-columns: 86px 1fr;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    padding: 12px 0;
    border-top: 1px solid #edf2f7;
  }

  .sidebar-item:first-of-type{
    border-top: none;
    padding-top: 0;
  }

  .sidebar-thumb{
    width: 86px;
    height: 68px;
    object-fit: cover;
    border-radius: 12px;
    display: block;
    background: #e5e7eb;
  }

  .sidebar-text{
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .sidebar-item-title{
    font-size: .95rem;
    font-weight: 700;
    color: #111827;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .sidebar-item-date{
    font-size: .84rem;
    color: var(--news-muted);
  }

  .sidebar-empty{
    color: var(--news-muted);
  }

  @media (max-width: 1100px){
    .news-layout{
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 768px){
    .section-light{
      padding: 35px 0 50px;
    }

    .news-article-top,
    .news-article-content{
      padding: 20px;
    }

    .news-gallery-grid{
      grid-template-columns: 1fr;
    }

    .news-gallery-item img{
      height: 220px;
    }
  }
</style>
</head>
<body>

  <nav class="navbar">
    <div class="navbar__container">
          <div class="navbar__logo">
              <img src="images/provida-logo.png" alt="Provida Logo" class="logo-img">

              <div class="logo-text">
                  <div class="logo-main">PROVIDA</div>
                  <div class="logo-sub">Pétanque Club</div>
              </div>
          </div>
      <div class="navbar__menu" id="navMenu">
        <a href="index.php" class="navbar__link">Home</a>
        <a href="about.php" class="navbar__link">About</a>
        <a href="competition.php" class="navbar__link">Competitions</a>
        <a href="gallery.php" class="navbar__link">Gallery</a>
        <a href="news.php" class="navbar__link active">News</a>
        <a href="contact.php" class="navbar__link">Contact</a>
      </div>

      <div class="navbar__actions">
        <button class="lang-toggle" id="langToggle">
          <span class="lang-en">EN</span><span class="lang-sep">/</span><span class="lang-kh">KH</span>
        </button>
        <button class="hamburger" id="hamburger"><span></span><span></span><span></span></button>
      </div>
    </div>
  </nav>

<div class="page-header">
  <div class="container">
    <h1 class="page-title"><?= e($post["title"]) ?></h1>
    <p class="page-subtitle">
      <?= !empty($post["subtitle"]) ? e($post["subtitle"]) : 'Latest updates from Provida' ?>
    </p>
  </div>
</div>

<main class="section-light">
<main class="news-main-wrap">

  <main class="news-main-wrap">
    <div class="container">
      <div class="news-layout">

        <article class="news-article-page">
          <div class="news-article-top">
            <a class="news-back" href="news.php">← Back to News</a>

            <h1 class="news-article-title"><?= e($post["title"]) ?></h1>

            <div class="news-article-meta">
              <span class="news-meta-item"><strong>Date:</strong> <?= e($post["news_date"] ?? "") ?></span>
              <span class="news-meta-item"><strong>Writer:</strong> <?= e($post["author_name"] ?: "Provida Team") ?></span>
              <span class="news-meta-item"><strong>Views:</strong> <?= e((string)(($post["view_count"] ?? 0) + 1)) ?></span>
            </div>
          </div>

          <?php if ($featuredImageUrl !== ''): ?>
            <div class="news-article-hero">
              <img src="<?=  e($featuredImageUrl) ?>" alt="<?= e($post["title"]) ?>">
            </div>
          <?php endif; ?>

          <div class="news-article-content">
            <div class="news-article-body"><?= e($post["content"] ?? "") ?></div>

            <?php if ($bodyImageUrl !== ''): ?>
              <div class="news-body-image">
                <img src="../admin/news/<?= e($bodyImageUrl) ?>" alt="<?= e($post["title"]) ?>">
              </div>
            <?php endif; ?>
            <?php if (!empty($galleryImages)): ?>
              <h3 class="news-section-title">Gallery</h3>
              <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
                <?php foreach ($galleryImages as $g): ?>
                  <div style="border-radius:18px;overflow:hidden;border:1px solid var(--news-border);background:#fff;">
                    <img
                      src="../admin/news/<?= e(ltrim($g["image_path"], "/")) ?>"
                      alt="Gallery image"
                      style="width:100%;height:260px;object-fit:cover;display:block;">
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($post["external_video_url"])): ?>
              <h3 class="news-section-title">Video</h3>

              <?php if ($embedUrl !== ''): ?>
                <div class="news-video-box">
                  <div class="news-video-frame">
                    <iframe
                      src="<?= e($embedUrl) ?>"
                      title="Video"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                      allowfullscreen>
                    </iframe>
                  </div>
                </div>

                <div style="margin-top:12px;">
                  <a class="news-share-btn" href="<?= e($post["external_video_url"]) ?>" target="_blank" rel="noopener">
                    Open original video
                  </a>
                </div>
              <?php else: ?>
                <div style="margin-top:12px;">
                  <a class="news-share-btn" href="<?= e($post["external_video_url"]) ?>" target="_blank" rel="noopener">
                    Open video link
                  </a>
                </div>
              <?php endif; ?>
            <?php endif; ?>

            <div class="news-share-row">
              <div class="news-author">
                By <?= e($post["author_name"] ?: "Provida Team") ?>
              </div>

              <div class="news-share">
                <span class="news-share-label">Share:</span>
                <a id="shareFb" class="news-share-btn" href="#" target="_blank" rel="noopener">Facebook</a>
                <a id="shareTelegram" class="news-share-btn" href="#" target="_blank" rel="noopener">Telegram</a>
                <a id="shareWhatsApp" class="news-share-btn" href="#" target="_blank" rel="noopener">WhatsApp</a>
                <a id="shareEmail" class="news-share-btn" href="#">Email</a>
              </div>
            </div>
          </div>
        </article>

        <aside class="news-sidebar">
          <div class="sidebar-card">
            <h3 class="sidebar-title">Popular</h3>
            <?= renderSidebarItems($popularNews) ?>
          </div>

          <div class="sidebar-card">
            <h3 class="sidebar-title">Latest</h3>
            <?= renderSidebarItems($latestNews) ?>
          </div>
        </aside>

      </div>
    </div>
  </main>

  <footer class="footer">
    <div class="container">
      <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Provida Pétanque Club. All rights reserved.</p>
        <a href="admin.php" class="admin-link">Admin</a>
      </div>
    </div>
  </footer>

  <script>
    const currentUrl = encodeURIComponent(window.location.href);
    const currentTitle = encodeURIComponent("<?= e($post["title"]) ?>");

    document.getElementById("shareFb").href =
      `https://www.facebook.com/sharer/sharer.php?u=${currentUrl}`;

    document.getElementById("shareTelegram").href =
      `https://t.me/share/url?url=${currentUrl}&text=${currentTitle}`;

    document.getElementById("shareWhatsApp").href =
      `https://wa.me/?text=${currentTitle}%20${currentUrl}`;

    document.getElementById("shareEmail").addEventListener("click", function(e){
      e.preventDefault();
      window.location.href = `mailto:?subject=${currentTitle}&body=${currentUrl}`;
    });
  </script>

  <script src="script.js"></script>
</body>
</html>