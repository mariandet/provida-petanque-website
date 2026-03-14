<?php
$videoFile = "images/gallery_videos.json";

$videos = ["", "", ""];
if (file_exists($videoFile)) {
    $json = json_decode(file_get_contents($videoFile), true);
    if (is_array($json)) {
        $videos = array_pad($json, 3, "");
    }
}

function youtube_embed_url($url) {
    $url = trim((string)$url);
    if ($url === "") return "";

    if (preg_match('~youtube\.com/watch\?v=([^&]+)~i', $url, $m)) {
        return "https://www.youtube.com/embed/" . $m[1];
    }

    if (preg_match('~youtu\.be/([^?&]+)~i', $url, $m)) {
        return "https://www.youtube.com/embed/" . $m[1];
    }

    if (preg_match('~youtube\.com/embed/([^?&]+)~i', $url, $m)) {
        return "https://www.youtube.com/embed/" . $m[1];
    }

    return "";
}

$embedVideos = array_values(array_filter(array_map('youtube_embed_url', $videos)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Provida Pétanque Club</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .video-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
            gap:20px;
            margin-top:20px;
        }
        .video-card{
            background:#fff;
            border-radius:16px;
            overflow:hidden;
            box-shadow:0 8px 24px rgba(0,0,0,.08);
        }
        .video-frame{
            position:relative;
            width:100%;
            padding-top:56.25%;
            background:#000;
        }
        .video-frame iframe{
            position:absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            border:0;
        }
        .no-video{
            text-align:center;
            color:#666;
            margin-top:20px;
        }
.image-hover-modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.75);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
    padding:20px;
}

.image-hover-modal.active{
    display:flex;
}

.image-hover-modal img{
    max-width:90vw;
    max-height:85vh;
    border-radius:12px;
    box-shadow:0 10px 30px rgba(0,0,0,.25);
}

.image-hover-close{
    position:absolute;
    top:20px;
    right:20px;
    width:42px;
    height:42px;
    border:none;
    border-radius:999px;
    background:#fff;
    color:#111;
    font-size:28px;
    line-height:1;
    cursor:pointer;
    box-shadow:0 6px 18px rgba(0,0,0,.2);
}

.image-hover-close:hover{
    background:#f3f4f6;
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
                <a href="competition.php" class="navbar__link" data-en="Competitions" data-kh="ទំព័រការប្រកួត">Competitions</a>
                <a href="gallery.php" class="navbar__link active" data-en="Gallery" data-kh="រូបភាព">Gallery</a>
                <a href="news.php" class="navbar__link" data-en="News" data-kh="ព័ត៌មាន">News</a>
                <a href="contact.html" class="navbar__link" data-en="Contact" data-kh="ទំនាក់ទំនង">Contact</a>
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
            <h1 class="page-title"
                data-en="Photo Gallery"
                data-kh="ទំព័ររូបភាព">
                Photo Gallery
            </h1>
            <p class="page-subtitle"
               data-en="Moments from our community"
               data-kh="រូបភាពសកម្មភាពផ្សេងៗ">
               Moments from our community
            </p>
        </div>
    </div>

    <main class="section-light">
        <div class="container">
            <section class="content-section">
                <h2 class="section-title"
                    data-en="Memory Gallery"
                    data-kh="កម្រងរូបភាពអនុស្សាវរីយ៍">
                    Memory Gallery
                </h2>

                <p class="about-text"
                   data-en="Views and activities from our community"
                   data-kh="ទិដ្ឋភាព និងសកម្មភាពនានាក្នុងសហគមន៍របស់យើង"
                   style="text-align:center; max-width:800px; margin:0 auto 2rem;">
                   Views and activities from our community
                </p>

                <div class="gallery-grid">

                    <div class="gallery-item" data-category="tournaments">
                        <img src="images/gallery-1.jpg" alt="Championship">
                        <div class="gallery-overlay">
                            <span data-en="Championship" data-kh="ការប្រកួតជើងឯក">Championship</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-category="tournaments">
                        <img src="images/gallery-2.jpg" alt="Finals">
                        <div class="gallery-overlay">
                            <span data-en="Finals" data-kh="វគ្គផ្តាច់ព្រ័ត្រ">Finals</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-category="training">
                        <img src="images/gallery-3.jpg" alt="Training">
                        <div class="gallery-overlay">
                            <span data-en="Training" data-kh="ការហ្វឹកហាត់">Training</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-category="events">
                        <img src="images/gallery-4.jpg" alt="Community">
                        <div class="gallery-overlay">
                            <span data-en="Community" data-kh="សហគមន៍">Community</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-category="members">
                        <img src="images/gallery-5.jpg" alt="Team Photo">
                        <div class="gallery-overlay">
                            <span data-en="Team Photo" data-kh="រូបថតក្រុម">Team Photo</span>
                        </div>
                    </div>

                    <div class="gallery-item" data-category="tournaments">
                        <img src="images/gallery-6.jpg" alt="Awards">
                        <div class="gallery-overlay">
                            <span data-en="Awards" data-kh="រង្វាន់">Awards</span>
                        </div>
                    </div>

                </div>
            </section>

            <section class="content-section">
                <h2 class="section-title"
                    data-en="Featured Videos"
                    data-kh="វីដេអូពិសេស">
                    Featured Videos
                </h2>

                <div class="video-grid">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <?php if (!empty($embedVideos[$i])): ?>
                            <div class="video-card">
                                <div class="video-frame">
                                    <iframe
                                        src="<?= htmlspecialchars($embedVideos[$i], ENT_QUOTES, 'UTF-8') ?>"
                                        title="YouTube video"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen>
                                    </iframe>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="video-card">
                                <div class="video-frame" style="display:flex;align-items:center;justify-content:center;color:#999;">
                                    No Video
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </section>
        </div>
    </main>

    <div id="imageHoverModal" class="image-hover-modal">
        <img id="imageHoverPreview" src="" alt="Preview">
    </div>

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

    <script src="script.js"></script>
    <script>
      const hoverModal = document.getElementById("imageHoverModal");
const hoverPreview = document.getElementById("imageHoverPreview");
const hoverClose = document.getElementById("imageHoverClose");

if (hoverModal && hoverPreview) {
    document.querySelectorAll(".gallery-item").forEach(item => {
        item.addEventListener("click", function () {
            const img = item.querySelector("img");
            if (!img) return;

            hoverPreview.src = img.src;
            hoverPreview.alt = img.alt || "";
            hoverModal.classList.add("active");
        });
    });

    function closeHoverModal() {
        hoverModal.classList.remove("active");
        hoverPreview.src = "";
        hoverPreview.alt = "";
    }

    if (hoverClose) {
        hoverClose.addEventListener("click", closeHoverModal);
    }

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeHoverModal();
        }
    });
}
    </script>
</body>
</html>