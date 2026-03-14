<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

$id = (int)($_GET["id"] ?? 0);

if ($id > 0) {

    /* get images of this competition */
    $stmt = $pdo->prepare("SELECT media_url FROM competition_media WHERE competition_id=?");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($images as $img) {

        if (!empty($img["media_url"])) {

            $filePath = __DIR__ . "/" . $img["media_url"];

            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /* delete media records */
    $pdo->prepare("DELETE FROM competition_media WHERE competition_id=?")->execute([$id]);

    /* delete competition */
    $pdo->prepare("DELETE FROM competitions WHERE id=?")->execute([$id]);
}

header("Location: index.php");
exit;