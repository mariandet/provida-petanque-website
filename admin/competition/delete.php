<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

$id = (int)($_GET["id"] ?? 0);

$stmt = $pdo->prepare("DELETE FROM competitions WHERE id=?");
$stmt->execute([$id]);

header("Location: index.php");
exit;