<?php
require_once __DIR__ . "/../auth.php";
require_once __DIR__ . "/../config/db.php";

$rows = $pdo->query("SELECT id,title,is_open,event_date,price,currency FROM competitions ORDER BY id DESC")->fetchAll();
?>

<h2>Competition List</h2>
<p><a href="competition_create.php">+ Create New</a> | <a href="dashboard.php">Back</a></p>

<table border="1" cellpadding="8">
  <tr>
    <th>ID</th><th>Title</th><th>Date</th><th>Fee</th><th>Status</th><th>View</th>
  </tr>

  <?php foreach($rows as $r): ?>
    <tr>
      <td><?= (int)$r["id"] ?></td>
      <td><?= htmlspecialchars($r["title"]) ?></td>
      <td><?= htmlspecialchars($r["event_date"] ?? "-") ?></td>
      <td>
        <?php
          if ($r["price"] === null) echo "FREE";
          else echo htmlspecialchars($r["price"] . " " . $r["currency"]);
        ?>
      </td>
      <td><?= ((int)$r["is_open"] === 1) ? "OPEN" : "CLOSED" ?></td>
      <td><a href="/provida-club/competition.php?id=<?= (int)$r["id"] ?>">Open</a></td>
    </tr>
  <?php endforeach; ?>
</table>