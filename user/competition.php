<?php
require_once "../config/db.php";

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_price($price, $currency): string {
    if ($price === null || $price === "") {
        return "FREE";
    }
    return number_format((float)$price, 2) . " " . ($currency ?: "USD");
}

function kh_month_short($monthNumber): string {
    $months = [
        1 => "មករា",
        2 => "កុម្ភៈ",
        3 => "មីនា",
        4 => "មេសា",
        5 => "ឧសភា",
        6 => "មិថុនា",
        7 => "កក្កដា",
        8 => "សីហា",
        9 => "កញ្ញា",
        10 => "តុលា",
        11 => "វិច្ឆិកា",
        12 => "ធ្នូ"
    ];
    return $months[(int)$monthNumber] ?? "";
}

$successMessage = "";
$errorMessage = "";

$oldCompetitionId = (int)($_POST["competition_id"] ?? 0);
$oldFullName = trim($_POST["full_name"] ?? "");
$oldPhone = trim($_POST["phone"] ?? "");
$oldEmail = trim($_POST["email"] ?? "");
$oldAgree = isset($_POST["is_agree_terms"]) ? 1 : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "register_competition") {
    try {
        if (!($pdo instanceof PDO)) {
            throw new Exception("Database connection failed.");
        }

        $competitionId = (int)($_POST["competition_id"] ?? 0);
        $fullName = trim($_POST["full_name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $isAgreeTerms = isset($_POST["is_agree_terms"]) ? 1 : 0;

        if ($competitionId <= 0) {
            throw new Exception("Competition is required.");
        }

        if ($fullName === "") {
            throw new Exception("Full name is required.");
        }

        if ($phone === "") {
            throw new Exception("Phone is required.");
        }

        if ($isAgreeTerms !== 1) {
            throw new Exception("You must agree to term and condition.");
        }

        $check = $pdo->prepare("
            SELECT id, title, is_open
            FROM competitions
            WHERE id = ?
            LIMIT 1
        ");
        $check->execute([$competitionId]);
        $comp = $check->fetch(PDO::FETCH_ASSOC);

        if (!$comp) {
            throw new Exception("Competition not found.");
        }

        if ((int)$comp["is_open"] !== 1) {
            throw new Exception("Registration closed.");
        }

        $proofImageUrl = null;

        if (isset($_FILES["proof_image"]) && $_FILES["proof_image"]["error"] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES["proof_image"]["error"] !== UPLOAD_ERR_OK) {
                throw new Exception("Image upload failed. Error code: " . (int)$_FILES["proof_image"]["error"]);
            }

            $ext = strtolower(pathinfo($_FILES["proof_image"]["name"], PATHINFO_EXTENSION));
            $allowedExt = ["jpg", "jpeg", "png", "webp", "gif"];

            if (!in_array($ext, $allowedExt, true)) {
                throw new Exception("Invalid image type.");
            }

            $uploadDir = __DIR__ . "/../admin/registrations/uploads/";

            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception("Cannot create upload folder.");
                }
            }

            if (!is_writable($uploadDir)) {
                throw new Exception("Upload folder not writable.");
            }

            $fileName = "reg_" . time() . "_" . mt_rand(1000, 9999) . "." . $ext;
            $dest = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES["proof_image"]["tmp_name"], $dest)) {
                throw new Exception("Image upload failed while moving file.");
            }

            $proofImageUrl = "uploads/" . $fileName;
        }

        $sql = "
            INSERT INTO registrations
            (
                competition_id,
                full_name,
                phone,
                email,
                note,
                is_agree_terms,
                proof_image_url,
                created_at
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $pdo->prepare($sql);

        $noteValue = null;

        $ok = $stmt->execute([
            $competitionId,
            $fullName,
            $phone,
            $email,
            $noteValue,
            $isAgreeTerms,
            $proofImageUrl
        ]);

        if (!$ok) {
            $info = $stmt->errorInfo();
            throw new Exception("Database insert failed: " . ($info[2] ?? "unknown error"));
        }

        $newId = (int)$pdo->lastInsertId();

        if ($newId <= 0) {
            throw new Exception("Insert failed.");
        }

        $verify = $pdo->prepare("
            SELECT id
            FROM registrations
            WHERE id = ?
            LIMIT 1
        ");
        $verify->execute([$newId]);

        if (!$verify->fetchColumn()) {
            throw new Exception("Registration was not saved to database.");
        }

        $successMessage = "Registration saved successfully.";

        $oldCompetitionId = 0;
        $oldFullName = "";
        $oldPhone = "";
        $oldEmail = "";
        $oldAgree = 0;

    } catch (Throwable $e) {
        $errorMessage = "Registration failed: " . $e->getMessage();
    }
}

$upcomingStmt = $pdo->prepare("
    SELECT
        id,
        title,
        description,
        event_date,
        price,
        currency,
        is_open
    FROM competitions
    WHERE is_open = 1
    ORDER BY
        CASE
            WHEN event_date IS NULL THEN 1
            WHEN event_date >= CURDATE() THEN 0
            ELSE 1
        END,
        CASE
            WHEN event_date >= CURDATE() THEN event_date
            ELSE NULL
        END ASC,
        id DESC
");
$upcomingStmt->execute();
$upcomingCompetitions = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);

$pastStmt = $pdo->prepare("
    SELECT
        id,
        title,
        description,
        event_date,
        price,
        currency,
        is_open
    FROM competitions
    WHERE is_open = 0
       OR (event_date IS NOT NULL AND event_date < CURDATE())
    ORDER BY event_date DESC, id DESC
    LIMIT 8
");
$pastStmt->execute();
$pastCompetitions = $pastStmt->fetchAll(PDO::FETCH_ASSOC);

$competitionData = array_map(function ($comp) {
    return [
        "id" => (int)$comp["id"],
        "note" => trim((string)($comp["description"] ?? "")) !== ""
            ? (string)$comp["description"]
            : "Please read and follow all competition rules before registering."
    ];
}, $upcomingCompetitions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competitions - Provida Pétanque Club</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .events-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(280px, 340px));
            gap:24px;
            justify-content:center;
            align-items:start;
        }

        .event-card{
            width:100%;
            max-width:340px;
            margin:0 auto;
        }

        .event-header-img{
            width:100%;
            height:180px;
            object-fit:cover;
        }

        .event-body{
            padding:18px;
        }

        .event-body h3{
            font-size:1.2rem;
            margin-bottom:10px;
        }

        .event-desc{
            font-size:0.95rem;
            line-height:1.6;
        }

        .event-details{
            display:flex;
            flex-direction:column;
            gap:6px;
            margin:12px 0;
        }

        .event-date{
            transform:scale(0.9);
            transform-origin:top right;
        }

        .competition-detail-box{
            display:none;
            background:#e5c1586b;
            border-radius:18px;
            padding:24px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
            margin:0 0 24px;
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
            <a href="index.php" class="navbar__link" data-en="Home" data-kh="ទំព័រដើម">Home</a>
            <a href="about.html" class="navbar__link" data-en="About" data-kh="អំពីយើង">About</a>
            <a href="competition.php" class="navbar__link active" data-en="Competitions" data-kh="ទំព័រការប្រកួត">Competitions</a>
            <a href="gallery.php" class="navbar__link" data-en="Gallery" data-kh="រូបភាព">Gallery</a>
            <a href="news.php" class="navbar__link" data-en="News" data-kh="ព័ត៌មាន">News</a>
            <a href="contact.php" class="navbar__link" data-en="Contact" data-kh="ទំនាក់ទំនង">Contact</a>
        </div>

        <div class="navbar__actions">
            <button class="lang-toggle" id="langToggle">
                <span class="lang-en">EN</span><span class="lang-sep">/</span><span class="lang-kh">KH</span>
            </button>
            <button class="hamburger" id="hamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1 class="page-title" data-en="Competitions Tournaments" data-kh="ទំព័រការប្រកួត (Competitions)">
            Competitions Tournaments
        </h1>
        <p class="page-subtitle" data-en="Events & Registration" data-kh="ការប្រកួត និងព្រឹត្តិការណ៍កីឡា">
            Events & Registration
        </p>
    </div>
</div>

<main class="section-light">
    <div class="container">

        <?php if ($successMessage !== ""): ?>
            <div style="background:#22c55e;color:#fff;padding:14px 18px;border-radius:10px;margin-bottom:20px;">
                <?= e($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ""): ?>
            <div style="background:#ef4444;color:#fff;padding:14px 18px;border-radius:10px;margin-bottom:20px;">
                <?= e($errorMessage) ?>
            </div>
        <?php endif; ?>

        <section class="content-section">
            <h2 class="section-title" data-en="Upcoming Events" data-kh="ព្រឹត្តិការណ៍នាពេលខាងមុខ">
                Upcoming Events
            </h2>

            <p class="about-text"
               data-en="Join exciting competitions and discover new experiences through our upcoming events."
               data-kh="ចូលរួមប្រកួតប្រជែងសមត្ថភាព និងស្វែងរកបទពិសោធន៍ថ្មីៗជាមួយព្រឹត្តិការណ៍ដែលនឹងមកដល់ឆាប់ៗនេះ។"
               style="text-align:center; max-width:520px; margin:0 auto 2rem;">
               Join exciting competitions and discover new experiences through our upcoming events.
            </p>

            <div class="events-grid">
                <?php if (!empty($upcomingCompetitions)): ?>
                    <?php foreach ($upcomingCompetitions as $comp): ?>
                        <?php
                        $ts = !empty($comp["event_date"]) ? strtotime($comp["event_date"]) : false;
                        $monthEn = $ts ? strtoupper(date("M", $ts)) : "TBA";
                        $monthKh = $ts ? kh_month_short(date("n", $ts)) : "នឹងជូនដំណឹង";
                        $day = $ts ? date("d", $ts) : "--";
                        ?>
                        <div class="event-card">
                            <div class="event-header">
                                <img src="images/event1.jpg" class="event-header-img" alt="<?= e($comp["title"]) ?>">

                                <span class="event-badge" data-en="Registration Open" data-kh="បើកចុះឈ្មោះ">
                                    Registration Open
                                </span>

                                <div class="event-date">
                                    <span class="event-month" data-en="<?= e($monthEn) ?>" data-kh="<?= e($monthKh) ?>">
                                        <?= e($monthEn) ?>
                                    </span>
                                    <span class="event-day"><?= e($day) ?></span>
                                </div>
                            </div>

                            <div class="event-body">
                                <h3 data-en="<?= e($comp["title"]) ?>" data-kh="<?= e($comp["title"]) ?>">
                                    <?= e($comp["title"]) ?>
                                </h3>

                                <p class="event-location"
                                   data-en="Provida Pétanque Club, Phnom Penh"
                                   data-kh="ក្លឹបប្រូវីដា ប៉េតង់ ភ្នំពេញ">
                                   Provida Pétanque Club, Phnom Penh
                                </p>

                                <div class="event-details">
                                    <?php if (!empty($comp["event_date"])): ?>
                                        <span>📅 <?= e(date("Y-m-d", strtotime($comp["event_date"]))) ?></span>
                                    <?php endif; ?>
                                    <!-- <span>💵 <?= e(format_price($comp["price"], $comp["currency"])) ?></span> -->
                                    <span>✅ Open Registration</span>
                                </div>

                                <p class="event-desc"
                                   data-en="<?= e($comp["description"] ?: "Upcoming competition event.") ?>"
                                   data-kh="<?= e($comp["description"] ?: "ព្រឹត្តិការណ៍ប្រកួតនាពេលខាងមុខ។") ?>">
                                   <?= e($comp["description"] ?: "Upcoming competition event.") ?>
                                </p>

                                <a href="#competitionRegistration"
                                   class="btn btn--primary event-register-btn"
                                   onclick="setCompetitionValue('<?= (int)$comp['id'] ?>')"
                                   data-en="Register Now"
                                   data-kh="ចុះឈ្មោះឥឡូវនេះ">
                                   Register Now
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center;">
                        <span data-en="No upcoming competitions found." data-kh="មិនទាន់មានការប្រកួតនាពេលខាងមុខទេ។">
                            No upcoming competitions found.
                        </span>
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <section class="content-section" id="competitionRegistration">
            <h2 class="section-title" data-en="Competition Registration" data-kh="ចុះឈ្មោះចូលរួមប្រកួត">
                Competition Registration
            </h2>

            <p class="about-text"
               data-en="Select a competition to view term and condition before registration."
               data-kh="សូមជ្រើសរើសការប្រកួត ដើម្បីមើលលក្ខខណ្ឌ មុនពេលចុះឈ្មោះ។"
               style="text-align:center; max-width:820px; margin:0 auto 2rem;">
               Select a competition to view term and condition before registration.
            </p>

            <div class="registration-container">
                <form id="competitionForm" class="registration-form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="register_competition">

                    <div class="form-row">
                        <div class="form-group">
                            <label data-en="Full Name" data-kh="ឈ្មោះពេញ">Full Name</label>
                            <input type="text" name="full_name" value="<?= e($oldFullName) ?>" placeholder="John Doe" required>
                        </div>

                        <div class="form-group">
                            <label data-en="Email" data-kh="អ៊ីមែល">Email</label>
                            <input type="email" name="email" value="<?= e($oldEmail) ?>" placeholder="john@example.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label data-en="Phone" data-kh="លេខទូរស័ព្ទ">Phone</label>
                            <input type="tel" name="phone" value="<?= e($oldPhone) ?>" placeholder="+855 (23) 123-4567" required>
                        </div>

                        <div class="form-group">
                            <label data-en="Event" data-kh="ព្រឹត្តិការណ៍">Event</label>
                            <select name="competition_id" id="competition_id" required onchange="renderCompetitionDetail(this.value)">
                                <option value="" data-en="Select Event" data-kh="ជ្រើសរើសព្រឹត្តិការណ៍">
                                    Select Event
                                </option>
                                <?php foreach ($upcomingCompetitions as $comp): ?>
                                    <option value="<?= (int)$comp["id"] ?>" <?= $oldCompetitionId === (int)$comp["id"] ? "selected" : "" ?>>
                                        <?= e($comp["title"]) ?>
                                        <?php if (!empty($comp["event_date"])): ?>
                                            (<?= e(date("M d", strtotime($comp["event_date"]))) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="competition-detail-box" id="competitionDetailBox">
                        <strong data-en="Term and Condition" data-kh="លក្ខខណ្ឌ">Term and Condition</strong>
                        <div id="detailNote" style="margin-top:10px; line-height:1.8; white-space:pre-wrap;"></div>
                    </div>

                    <div class="form-group">
                        <label data-en="Upload Proof of Payment" data-kh="បញ្ចូលភស្តុតាងនៃការបង់ប្រាក់">
                            Upload Proof of Payment
                        </label>
                        <input type="file" name="proof_image" id="paymentProof" accept="image/*">
                        <small class="hint"
                               data-en="Upload screenshot/photo of your payment receipt (optional if not paid yet)."
                               data-kh="សូមបញ្ចូលរូបថត ឬ ស្គ្រីនស្ដ្រីននៃបង្កាន់ដៃបង់ប្រាក់ (អាចមិនបញ្ចូលបាន ប្រសិនបើមិនទាន់បង់)។">
                            Upload screenshot/photo of your payment receipt (optional if not paid yet).
                        </small>
                    </div>

                    <label class="checkbox">
                        <input type="checkbox" name="is_agree_terms" value="1" <?= $oldAgree === 1 ? "checked" : "" ?> required>
                        <span data-en="I agree to the rules and term and condition" data-kh="ខ្ញុំយល់ព្រមតាមច្បាប់ និងលក្ខខណ្ឌ">
                            I agree to the rules and term and condition
                        </span>
                    </label>

                    <button type="submit"
                            class="btn btn--primary"
                            style="width: 100%;"
                            data-en="Submit Registration"
                            data-kh="ដាក់ស្នើការចុះឈ្មោះ">
                        Submit Registration
                    </button>
                </form>
            </div>
        </section>

        <section class="content-section">
            <h2 class="section-title" data-en="Past Competitions" data-kh="ការប្រកួតកន្លងមក">
                Past Competitions
            </h2>

            <div class="past-events">
                <?php if (!empty($pastCompetitions)): ?>
                    <?php foreach ($pastCompetitions as $past): ?>
                        <div class="past-event">
                            <span class="past-date">
                                <?= !empty($past["event_date"]) ? e(date("F Y", strtotime($past["event_date"]))) : "TBA" ?>
                            </span>
                            <span class="past-title" data-en="<?= e($past["title"]) ?>" data-kh="<?= e($past["title"]) ?>">
                                <?= e($past["title"]) ?>
                            </span>
                            <span class="past-stats" data-en="<?= e(format_price($past["price"], $past["currency"])) ?>" data-kh="<?= e(format_price($past["price"], $past["currency"])) ?>">
                                <?= e(format_price($past["price"], $past["currency"])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p data-en="No past competitions found." data-kh="មិនមានប្រវត្តិការប្រកួតទេ។">
                        No past competitions found.
                    </p>
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
                    data-kh="ក្លឹបប៉េតង់ប្រូវីដា ប្រមូលផ្តុំអ្នកលេងដែលមានចំណង់ចំណូលចិត្ត នៅក្នុងសហគមន៍ដ៏រស់រវើកដែលផ្តោតលើភាពល្អឥតខ្ចោះ និងមិត្តភាព។">
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
                data-kh="© 2026 ក្លឹបប៉េតង់ប្រូវីដា។ រក្សាសិទ្ធិគ្រប់យ៉ាង។">
                &copy; 2026 Provida Pétanque Club. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
<!-- <script src="script.js"></script> -->
<script>
const competitionData = <?= json_encode($competitionData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function setCompetitionValue(id) {
    const select = document.getElementById("competition_id");
    if (select) {
        select.value = id;
        renderCompetitionDetail(id);
    }

    const section = document.getElementById("competitionRegistration");
    if (section) {
        section.scrollIntoView({ behavior: "smooth" });
    }
}

function renderCompetitionDetail(id) {
    const box = document.getElementById("competitionDetailBox");
    const detailNote = document.getElementById("detailNote");

    if (!box || !detailNote) return;

    if (!id) {
        box.style.display = "none";
        detailNote.textContent = "";
        return;
    }

    const item = competitionData.find(c => String(c.id) === String(id));

    if (!item) {
        box.style.display = "none";
        detailNote.textContent = "";
        return;
    }

    detailNote.textContent = item.note || "";
    box.style.display = "block";
}

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
renderCompetitionDetail(document.getElementById("competition_id").value);
</script>

</body>
</html>