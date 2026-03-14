<?php
require_once __DIR__ . "/../../config/db.php"; // FIXED PATH

$id = (int)($_GET["id"] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM competitions WHERE id=?");
    $stmt->execute([$id]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $c = $pdo->query("SELECT * FROM competitions ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

if (!$c) {
    echo "<h2>No competition found.</h2>";
    echo '<p><a href="index.php">Back</a></p>';
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($c["title"]) ?></title>
<style>
body { font-family: Arial; margin: 40px; }
.card { border:1px solid #ddd; padding:20px; border-radius:8px; max-width:700px; }
.btn { padding:8px 14px; text-decoration:none; border-radius:4px; display:inline-block; }
.btn-back { background:#ccc; color:#000; }
.btn-register { background:green; color:#fff; }
.btn-closed { background:red; color:#fff; }
</style>
</head>
<body>

<div class="card">
    <h1><?= htmlspecialchars($c["title"]) ?></h1>

    <p><?= nl2br(htmlspecialchars($c["description"] ?? "")) ?></p>

    <p>
        <b>Date:</b> <?= htmlspecialchars($c["event_date"] ?? "-") ?><br>
        <b>Fee:</b>
        <?php 
            if ($c["price"] === null) echo "FREE";
            else echo htmlspecialchars($c["price"] . " " . $c["currency"]);
        ?><br>
        <b>Status:</b> <?= ((int)$c["is_open"] === 1) ? "OPEN" : "CLOSED" ?>
    </p>

    <hr>

    <?php if ((int)$c["is_open"] === 1): ?>
        <a class="btn btn-register" href="/provida-club/register.html">Register Now</a>
    <?php else: ?>
        <span class="btn btn-closed">Registration Closed</span>
    <?php endif; ?>

    <br><br>

    <a class="btn btn-back" href="index.php">← Back to List</a>
</div>

</body>
</html>