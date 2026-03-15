<?php
require_once "../config/db.php";

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatNewsDate($date): string {
    if (!$date) return "";
    $ts = strtotime($date);
    if (!$ts) return "";
    return date("F Y", $ts);
}

function asset_url($path, $fallback = "images/news-placeholder.jpg"): string {
    $path = trim((string)$path);

    if ($path === "") {
        return $fallback;
    }

    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    $path = str_replace("\\", "/", $path);
    $path = ltrim($path, "/");

    return $path;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            subtitle,
            excerpt,
            content,
            author_name,
            news_date,
            featured_image,
            is_published,
            created_at
        FROM news_posts
        WHERE is_published = 1
        ORDER BY
            CASE WHEN news_date IS NULL THEN 1 ELSE 0 END,
            news_date DESC,
            id DESC
    ");
    $stmt->execute();
    $newsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $newsList = [];
}
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News - Provida Pétanque Club</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- NAVIGATION -->
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
                <a href="index.php" class="navbar__link" data-en="Home" data-kh="ទំព័រដើម">Home</a>
                <a href="about.html" class="navbar__link" data-en="About" data-kh="អំពីយើង">About</a>
                <a href="competition.php" class="navbar__link" data-en="Competitions" data-kh="ការប្រកួត">Competitions</a>
                <a href="gallery.php" class="navbar__link" data-en="Gallery" data-kh="រូបភាព">Gallery</a>
                <a href="news.php" class="navbar__link active" data-en="News" data-kh="ព័ត៌មាន">News</a>
                <a href="contact.html" class="navbar__link" data-en="Contact" data-kh="ទំនាក់ទំនង">Contact</a>
            </div>

            <div class="navbar__actions">
                <button class="lang-toggle" id="langToggle" aria-label="Toggle language">
                    <span class="lang-en">EN</span><span class="lang-sep">/</span><span class="lang-kh">KH</span>
                </button>
                <button class="hamburger" id="hamburger" aria-label="Menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title"
                data-en="News & Blog"
                data-kh="ព័ត៌មាន និងអត្ថបទ">
                News & Blog
            </h1>
            <p class="page-subtitle"
               data-en="Latest updates from Provida Pétanque Club"
               data-kh="ព័ត៌មានថ្មីៗពីក្លិបប៉េតង់ប្រូវីដា">
               Latest updates from Provida Pétanque Club
            </p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="section-light">
        <div class="container">

            <!-- NEWS SECTION -->
            <section class="content-section">
                <h2 class="section-title"
                    data-en="Latest News"
                    data-kh="ព័ត៌មានថ្មីៗ">
                    Latest News
                </h2>

                <div class="news-grid">
                    <?php if (!empty($newsList)): ?>
                        <?php foreach ($newsList as $news): ?>
                            <?php
                                $title = $news["title"] ?? "";
                                $excerpt = trim((string)($news["excerpt"] ?? ""));
                                $content = trim((string)($news["content"] ?? ""));
                                $summary = $excerpt !== "" ? $excerpt : mb_substr(strip_tags($content), 0, 180) . (mb_strlen(strip_tags($content)) > 180 ? "..." : "");
                                $image = asset_url($news["featured_image"] ?? "", "images/news-placeholder.jpg");
                                $dateText = formatNewsDate($news["news_date"] ?? "");
                            ?>
                            <article class="news-item">
                                <img src="../admin/news/<?= e($image) ?>" alt="<?= e($title) ?>" class="news-image">
                                <div class="news-content">
                                    <span class="news-date"
                                          data-en="<?= e($dateText) ?>"
                                          data-kh="<?= e($dateText) ?>">
                                          <?= e($dateText) ?>
                                    </span>

                                    <h3 class="news-title"
                                        data-en="<?= e($title) ?>"
                                        data-kh="<?= e($title) ?>">
                                        <?= e($title) ?>
                                    </h3>

                                    <p class="news-excerpt"
                                       data-en="<?= e($summary) ?>"
                                       data-kh="<?= e($summary) ?>">
                                       <?= e($summary) ?>
                                    </p>

                                    <a href="news-detail.php?id=<?= (int)$news["id"] ?>" class="news-link"
                                       data-en="Read More"
                                       data-kh="អានបន្ថែម">
                                       Read More
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column:1/-1;text-align:center;padding:40px 20px;">
                            <h3>No news available</h3>
                            <p>Please check back later.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>

   <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4 data-en="About" data-kh="អំពី">About</h4>
                    <p
                    data-en="Provida Pétanque Club brings together passionate players in a vibrant community dedicated to excellence and friendship."
                    data-kh="ក្លិបប៉េតង់ប្រូវីដា ប្រមូលផ្តុំអ្នកលេងដែលមានចំណង់ចំណូលចិត្ត នៅក្នុងសហគមន៍ដ៏រស់រវើកដែលផ្តោតលើភាពល្អឥតខ្ចោះ និងមិត្តភាព។">
                    Provida Pétanque Club brings together passionate players in a vibrant community dedicated to excellence and friendship.
                    </p>
                </div>

                <div class="footer-col">
                    <h4 data-en="Page" data-kh="ទំព័រ">Page</h4>
                    <ul>
                        <li><a href="index.php" data-en="Home" data-kh="ទំព័រដើម">Home</a></li>
                        <li><a href="about.html" data-en="About" data-kh="អំពីយើង">About</a></li>
                        <li><a href="competition.php" data-en="Competitions" data-kh="ទំព័រការប្រកួត">Competitions</a></li>
                        <li><a href="gallery.php" data-en="Gallery" data-kh="រូបភាព">Gallery</a></li>
                        <li><a href="news.php" data-en="News" data-kh="ព័ត៌មាន">News</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4 data-en="Contact" data-kh="ទំនាក់ទំនង">Contact</h4>
                    <p>📍 Phnom Penh, Cambodia</p>
                    <p>📧 info@provida.kh</p>
                    <p>📞 +855 (23) 123-4567</p>
                </div>

            </div>

            <div class="footer-bottom">
                <p
                data-en="© 2026 Provida Pétanque Club. All rights reserved."
                data-kh="© 2026 ក្លិបប៉េតង់ប្រូវីដា។ រក្សាសិទ្ធិគ្រប់យ៉ាង។">
                &copy; 2026 Provida Pétanque Club. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
    <script src="script.js"></script>
</body>
</html>