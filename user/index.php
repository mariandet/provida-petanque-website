<?php
require_once "../config/db.php";

$setting = $pdo->query("
    SELECT *
    FROM site_settings
    ORDER BY id DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$nextCompetitionStmt = $pdo->prepare("
    SELECT *
    FROM competitions
    WHERE is_open = 1
      AND event_date IS NOT NULL
      AND event_date >= CURDATE()
    ORDER BY event_date ASC, id ASC
    LIMIT 1
");
$nextCompetitionStmt->execute();
$nextCompetition = $nextCompetitionStmt->fetch(PDO::FETCH_ASSOC);

if (!$nextCompetition) {
    $fallbackStmt = $pdo->prepare("
        SELECT *
        FROM competitions
        WHERE is_open = 1
        ORDER BY
            CASE WHEN event_date IS NULL THEN 1 ELSE 0 END,
            event_date DESC,
            id DESC
        LIMIT 1
    ");
    $fallbackStmt->execute();
    $nextCompetition = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
}
$latestNews = $pdo->query("
    SELECT id, title, subtitle, excerpt, news_date, featured_image
    FROM news_posts
    WHERE is_published = 1
    ORDER BY
        CASE WHEN news_date IS NULL THEN 1 ELSE 0 END,
        news_date DESC,
        id DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function asset_url(?string $path, string $fallback = ""): string {
    $path = trim((string)$path);
    if ($path === "") {
        return $fallback;
    }
    return "../admin/news/" . ltrim($path, "/");
}

$heroTitleEn    = $setting["hero_title_en"] ?? "Welcome to Provida Pétanque Club";
$heroTitleKh    = $setting["hero_title_kh"] ?? "សូមស្វាគមន៍មកកាន់ក្លិប Provida Pétanque";
$heroSubtitleEn = $setting["hero_subtitle_en"] ?? "Cambodia’s Pétanque Community";
$heroSubtitleKh = $setting["hero_subtitle_kh"] ?? "សហគមន៍ប៉េតង់របស់កម្ពុជា";
$heroDescEn     = $setting["hero_description_en"] ?? "Master the sport of pétanque in a growing community of players and competitions.";
$heroDescKh     = $setting["hero_description_kh"] ?? "អភិវឌ្ឍជំនាញកីឡាប៉េតង់របស់អ្នកក្នុងសហគមន៍អ្នកលេង និងការប្រកួតដែលកំពុងរីករាយ។";
$aboutTextEn    = $setting["about_text_en"] ?? "Provida Pétanque Club is dedicated to promoting the sport of pétanque in Cambodia. The club brings together players of all ages and backgrounds to learn, practice, and enjoy the game while building a strong and friendly community.";
$aboutTextKh    = $setting["about_text_kh"] ?? "ក្លិប Provida Pétanque ផ្តោតលើការលើកស្ទួយកីឡាប៉េតង់នៅកម្ពុជា។ ក្លិបនេះប្រមូលផ្តុំអ្នកលេងគ្រប់វ័យ និងគ្រប់មជ្ឈដ្ឋានឱ្យបានរៀន អនុវត្ត និងរីករាយជាមួយការលេង ខណៈពេលកសាងសហគមន៍ដែលរឹងមាំ និងរួសរាយរាក់ទាក់។";

$compMonthEn = "MAR";
$compMonthKh = "មីនា";
$compDay     = "15";

if (!empty($nextCompetition["event_date"])) {
    $timestamp = strtotime($nextCompetition["event_date"]);
    $compMonthEn = strtoupper(date("M", $timestamp));
    $compDay = date("d", $timestamp);

    $khMonths = [
        "01" => "មករា",
        "02" => "កុម្ភៈ",
        "03" => "មីនា",
        "04" => "មេសា",
        "05" => "ឧសភា",
        "06" => "មិថុនា",
        "07" => "កក្កដា",
        "08" => "សីហា",
        "09" => "កញ្ញា",
        "10" => "តុលា",
        "11" => "វិច្ឆិកា",
        "12" => "ធ្នូ"
    ];
    $compMonthKh = $khMonths[date("m", $timestamp)] ?? $compMonthEn;
}

$compTitleEn = $nextCompetition["title"] ?? "Provida Tournament";
$compTitleKh = $nextCompetition["title"] ?? "ការប្រកួតប្រចាំរដូវ";
$compDescEn  = $nextCompetition["description"] ?? "Coming Soon!";
$compDescKh  = $nextCompetition["description"] ?? "ការប្រកួតកីឡាប៉េតង់នឹងមកដល់ឆាប់ៗនេះ";
$compPrice   = isset($nextCompetition["price"]) && $nextCompetition["price"] !== null && $nextCompetition["price"] !== ""
    ? number_format((float)$nextCompetition["price"], 2) . " " . ($nextCompetition["currency"] ?? "")
    : "FREE";

$newsFallback = "images/DSC08751.JPG";

function slugify($text): string {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'news';
}
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provida Pétanque Club - Where Precision Meets Passion</title>
    <link rel="stylesheet" href="style.css">
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
                <a href="index.php" class="navbar__link active" data-en="Home" data-kh="ទំព័រដើម">Home</a>
                <a href="about.html" class="navbar__link" data-en="About" data-kh="អំពីយើង">About</a>
                <a href="competition.php" class="navbar__link" data-en="Competitions" data-kh="ការប្រកួត">Competitions</a>
                <a href="gallery.php" class="navbar__link" data-en="Gallery" data-kh="រូបភាព">Gallery</a>
                <a href="news.php" class="navbar__link" data-en="News" data-kh="ព័ត៌មាន">News</a>
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

    <section class="hero">
        <div class="hero__slideshow">
            <div class="hero__slide active" style="background-image:url('images/DSC00429.jpg');"></div>
            <div class="hero__slide" style="background-image:url('images/DSC09721.jpg');"></div>
            <div class="hero__slide" style="background-image:url('images/DSC09961.jpg');"></div>
        </div>
        <div class="hero__overlay"></div>

        <div class="hero__content">
            <div class="hero__bg">
                <div class="hero__gradient"></div>
                <div class="hero__orb hero__orb--1"></div>
                <div class="hero__orb hero__orb--2"></div>
                <div class="petanque-ball" style="top: 10%; left: 5%; opacity: 0.05;"></div>
                <div class="petanque-ball" style="top: 60%; right: 8%; opacity: 0.04;"></div>
            </div>

            <div class="hero__content">
                <div class="hero__label"
                     data-en="<?= e($heroTitleEn) ?>"
                     data-kh="<?= e($heroTitleKh) ?>">
                    <?= e($heroTitleEn) ?>
                </div>

                <h1 class="hero__title">
                    <span data-en="<?= e($heroSubtitleEn) ?>"
                          data-kh="<?= e($heroSubtitleKh) ?>">
                        <?= e($heroSubtitleEn) ?>
                    </span>
                </h1>

                <p class="hero__subtitle"
                   data-en="<?= e($heroDescEn) ?>"
                   data-kh="<?= e($heroDescKh) ?>">
                    <?= e($heroDescEn) ?>
                </p>

                <div class="hero__cta">
                    <a href="competition.php#competitionRegistration"
                       class="btn btn--primary"
                       data-en="Register for Competition"
                       data-kh="ចុះឈ្មោះការប្រកួត">
                        Register for Competition
                    </a>
                    <a href="competition.php"
                       class="btn btn--secondary"
                       data-en="View Events"
                       data-kh="មើលព្រឹត្តិការណ៍">
                        View Events
                    </a>
                </div>
            </div>
        </div>

        <div class="hero__scroll"
             data-en="Scroll to explore"
             data-kh="រមូរដើម្បីស្វែងយល់">
            Scroll to explore
        </div>
    </section>

    <section class="about-preview section-light">
        <div class="container">
            <h2 class="section-title" data-en="About Provida" data-kh="អំពីប្រូវីដា">About Provida</h2>

            <div class="about-grid">
                <div class="about-content">
                    <p class="about-text"
                       data-en="<?= e($aboutTextEn) ?>"
                       data-kh="<?= e($aboutTextKh) ?>">
                        <?= e($aboutTextEn) ?>
                    </p>

                    <a href="about.html"
                       class="btn btn--outline"
                       data-en="Learn More"
                       data-kh="ស្វែងយល់បន្ថែម">
                        Learn More
                    </a>
                </div>

                <div class="about-stats">
                    <div class="stat-box">
                        <div class="stat-number">450+</div>
                        <div class="stat-label" data-en="Active Members" data-kh="សមាជិកសកម្ម">Active Members</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">12+</div>
                        <div class="stat-label" data-en="Annual Events" data-kh="ព្រឹត្តិការណ៍ប្រចាំឆ្នាំ">Annual Events</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">25+</div>
                        <div class="stat-label" data-en="Registered Teams" data-kh="ក្រុមដែលបានចុះឈ្មោះ">Registered Teams</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" id="yearsStrong">០+</div>
                        <div class="stat-label" id="yearsLabel"
                            data-en="Years Strong"
                            data-kh="រឹងមាំជាង">
                            Years Strong
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="next-competition section-dark"
        style="background:
        linear-gradient(rgba(0, 0, 0, 0.45), rgb(0 0 0 / 69%)),
        url(images/DSC08700.JPG) center / cover no-repeat;">
        <div class="container">
            <h2 class="section-title light"
                data-en="Next Competition"
                data-kh="ការប្រកួតបន្ទាប់">
                Next Competition
            </h2>

            <div class="competition-showcase">
                <div class="competition-card-main">
                    <div class="competition-badge" data-en="FEATURED" data-kh="លេចធ្លោ">FEATURED</div>

                    <div class="competition-date">
                        <span class="date-month"
                              data-en="<?= e($compMonthEn) ?>"
                              data-kh="<?= e($compMonthKh) ?>">
                            <?= e($compMonthEn) ?>
                        </span>
                        <span class="date-day"><?= e($compDay) ?></span>
                    </div>

                    <div class="competition-info">
                        <h3 class="competition-name"
                            data-en="<?= e($compTitleEn) ?>"
                            data-kh="<?= e($compTitleKh) ?>">
                            <?= e($compTitleEn) ?>
                        </h3>

                        <p class="competition-location"
                           data-en="Provida Pétanque Club, Phnom Penh"
                           data-kh="ក្លិប Provida Pétanque ភ្នំពេញ">
                            Provida Pétanque Club, Phnom Penh
                        </p>

                        <p class="competition-desc"
                           data-en="<?= e($compDescEn) ?>"
                           data-kh="<?= e($compDescKh) ?>">
                            <?= e($compDescEn) ?>
                        </p>

                        <!-- <div class="competition-details">
                            <div class="detail-item">
                                <span class="detail-icon">💵</span>
                                <span><?= e($compPrice) ?></span>
                            </div>

                            <?php if (!empty($nextCompetition["event_date"])): ?>
                            <div class="detail-item">
                                <span class="detail-icon">📅</span>
                                <span><?= e(date("Y-m-d", strtotime($nextCompetition["event_date"]))) ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="detail-item">
                                <span class="detail-icon">📌</span>
                                <span data-en="<?= e($compDescEn) ?>" data-kh="<?= e($compDescKh) ?>">
                                    <?= e($compDescEn) ?>
                                </span>
                            </div>
                        </div> -->

                        <a href="competition.php#competitionRegistration"
                           class="btn btn--gold"
                           id="registerCompetitionBtn"
                           data-en="Register Now"
                           data-kh="ចុះឈ្មោះឥឡូវនេះ">
                            Register Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

<section class="news-preview section-light">
    <div class="container">
        <h2 class="section-title" data-en="Latest News" data-kh="ព័ត៌មានថ្មីបំផុត">Latest News</h2>

        <div class="news-grid">
            <?php if (!empty($latestNews)): ?>
                <?php foreach ($latestNews as $news): ?>
                    <?php
                    $img = !empty($news["featured_image"])
                        ? asset_url($news["featured_image"], $newsFallback)
                        : $newsFallback;

                    $dateText = !empty($news["news_date"])
                        ? date("M d, Y", strtotime($news["news_date"]))
                        : "";
                    ?>
                  <article class="news-item">
                        <div class="news-image"
                            style="
                                min-height:220px;
                                background-image:
                                    linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)),
                                    url('<?= e($img) ?>');
                                background-position:center;
                                background-size:cover;
                                background-repeat:no-repeat;
                            ">
                        </div>

                        <div class="news-content">
                            <span class="news-date"><?= e($dateText) ?></span>
                            <h3 class="news-title"><?= e($news["title"] ?? "") ?></h3>

                            <?php if (!empty(trim((string)($news["excerpt"] ?? "")))): ?>
                                <p class="news-excerpt"><?= e($news["excerpt"]) ?></p>
                            <?php endif; ?>

                            <a href="news-detail.php?id=<?= (int)$news["id"] ?>&title=<?= urlencode(slugify($news["title"] ?? "")) ?>"
                            class="news-link"
                            data-en="Read More →"
                            data-kh="អានបន្ថែម →">
                                Read More →
                            </a>
                        </div>
                    </article>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p data-en="No news found." data-kh="មិនមានព័ត៌មានទេ។">No news found.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
    <section class="gallery-preview section-light">
        <div class="container">
            <h2 class="section-title" data-en="Photo Moments" data-kh="រូបភាពសកម្មភាព">Photo Moments</h2>

            <div class="gallery-preview-grid">
                <a href="gallery.php" class="gallery-preview-item gallery-preview-item--large">
                    <div class="gallery-prev-img" style="background:url('images/DSC01027.jpg') center/cover no-repeat;"></div>
                    <div class="gallery-prev-overlay">
                        <span class="gallery-prev-label" data-en="Championship Finals" data-kh="វគ្គផ្តាច់ព្រ័ត្រ">Championship Finals</span>
                    </div>
                </a>

                <a href="gallery.php" class="gallery-preview-item">
                    <div class="gallery-prev-img" style="background:url('images/DSC08154.JPG') center/cover no-repeat;"></div>
                    <div class="gallery-prev-overlay">
                        <span class="gallery-prev-label" data-en="Team Photos" data-kh="រូបថតក្រុម">Team Photos</span>
                    </div>
                </a>

                <a href="gallery.php" class="gallery-preview-item">
                    <div class="gallery-prev-img" style="background:url('images/DSC09751.jpg') center/cover no-repeat;"></div>
                    <div class="gallery-prev-overlay">
                        <span class="gallery-prev-label" data-en="Training Sessions" data-kh="វគ្គហ្វឹកហាត់">Training Sessions</span>
                    </div>
                </a>

                <a href="gallery.php" class="gallery-preview-item">
                    <div class="gallery-prev-img" style="background:url('images/DSC08721.JPG') center/cover no-repeat;"></div>
                    <div class="gallery-prev-overlay">
                        <span class="gallery-prev-label" data-en="Club Events" data-kh="កម្មវិធីរបស់ក្លិប">Club Events</span>
                    </div>
                </a>

                  <a href="gallery.php" class="gallery-preview-item">
                    <div class="gallery-prev-img" style="background:url('images/Finals.JPG') center/cover no-repeat;"></div>
                    <div class="gallery-prev-overlay">
                        <span class="gallery-prev-label" data-en="Club Events" data-kh="កម្មវិធីរបស់ក្លិប">Club Events</span>
                    </div>
                </a>

            </div>

            <div style="text-align:center; margin-top:3rem; margin-bottom:3rem;">
                <a href="gallery.php"
                   class="btn btn--primary"
                   data-en="View All Photos"
                   data-kh="មើលរូបភាពទាំងអស់">
                    View All Photos
                </a>
            </div>
        </div>
    </section>

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
                data-kh="© 2026 ក្ក្លិបប៉េតង់ប្រូវីដា។ រក្សាសិទ្ធិគ្រប់យ៉ាង។">
                &copy; 2026 Provida Pétanque Club. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <div class="modal" id="registerModal">
        <div class="modal__overlay"></div>
        <div class="modal__content">
            <button class="modal__close">×</button>
            <h2 data-en="Register for Competition" data-kh="ចុះឈ្មោះការប្រកួត">Register for Competition</h2>
            <form id="registerForm" class="registration-form">
                <div class="form-group">
                    <input type="text" placeholder="Full Name" data-en-placeholder="Full Name" data-kh-placeholder="ឈ្មោះពេញ" required>
                </div>
                <div class="form-group">
                    <input type="tel" placeholder="Phone" data-en-placeholder="Phone" data-kh-placeholder="ទូរស័ព្ទ" required>
                </div>
                <div class="form-group">
                    <input type="email" placeholder="Email" data-en-placeholder="Email" data-kh-placeholder="អ៊ីមែល" required>
                </div>
                <div class="form-group">
                    <input type="text" placeholder="Team Name" data-en-placeholder="Team Name" data-kh-placeholder="ឈ្មោះក្រុម" required>
                </div>
                <div class="form-group">
                    <select id="experienceSelect" required>
                        <option value="" data-en="Select Experience Level" data-kh="ជ្រើសរើសកម្រិតបទពិសោធន៍">Select Experience Level</option>
                        <option value="beginner" data-en="Beginner" data-kh="អ្នកចាប់ផ្តើម">Beginner</option>
                        <option value="intermediate" data-en="Intermediate" data-kh="មធ្យម">Intermediate</option>
                        <option value="advanced" data-en="Advanced" data-kh="ខ្ពស់">Advanced</option>
                        <option value="professional" data-en="Professional" data-kh="វិជ្ជាជីវៈ">Professional</option>
                    </select>
                </div>
                <button type="submit"
                        class="btn btn--primary"
                        style="width:100%;"
                        data-en="Submit Registration"
                        data-kh="ដាក់ស្នើ">
                    Submit Registration
                </button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
    const slides = document.querySelectorAll('.hero__slide');
    let currentSlide = 0;

    function changeSlide() {
        if (!slides.length) return;
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }

    setInterval(changeSlide, 5000);

    const langToggle = document.getElementById("langToggle");
    let currentLang = localStorage.getItem("site_lang") || "en";

    function applyLanguage(lang) {
        document.documentElement.lang = lang;

        document.querySelectorAll("[data-en][data-kh]").forEach(el => {
            el.textContent = lang === "kh" ? el.getAttribute("data-kh") : el.getAttribute("data-en");
        });

        document.querySelectorAll("[data-en-placeholder][data-kh-placeholder]").forEach(el => {
            el.placeholder = lang === "kh"
                ? el.getAttribute("data-kh-placeholder")
                : el.getAttribute("data-en-placeholder");
        });

        document.querySelectorAll("option[data-en][data-kh]").forEach(el => {
            el.textContent = lang === "kh" ? el.getAttribute("data-kh") : el.getAttribute("data-en");
        });

        localStorage.setItem("site_lang", lang);
    }

    if (langToggle) {
        langToggle.addEventListener("click", function () {
            currentLang = currentLang === "en" ? "kh" : "en";
            applyLanguage(currentLang);
        });
    }

    applyLanguage(currentLang);

    document.addEventListener("DOMContentLoaded", function () {

    const startDate = new Date("2025-01-01"); // change if needed
    const today = new Date();

    let years = today.getFullYear() - startDate.getFullYear();

    // adjust if anniversary not reached yet
    const m = today.getMonth() - startDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < startDate.getDate())) {
        years--;
    }

    if (years < 1) years = 1;

    document.getElementById("yearsStrong").textContent = years + "+";

});
    </script>
</body>
</html>