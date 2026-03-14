<?php
require_once __DIR__ . "/../auth.php";
require_once __DIR__ . "/../../config/db.php";

$id = (int)($_GET["id"] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM competitions WHERE id=?");
$stmt->execute([$id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$c) exit("Not found");

$msg = "";

if($_SERVER["REQUEST_METHOD"] === "POST"){

  $title = trim($_POST["title"] ?? "");
  $desc  = trim($_POST["description"] ?? "");
  $date  = $_POST["event_date"] ?? null;
  $price = trim($_POST["price"] ?? "");
  $ccy   = trim($_POST["currency"] ?? "");
  $open  = isset($_POST["is_open"]) ? 1 : 0;

  $priceVal = ($price === "") ? null : (float)$price;
  $ccyVal   = ($priceVal === null) ? null : ($ccy === "" ? "USD" : $ccy);

  if($title === ""){
    $msg = "Title is required";
  } else {

    /* update competition */
    $u = $pdo->prepare("
      UPDATE competitions
      SET title=?, description=?, event_date=?, price=?, currency=?, is_open=?
      WHERE id=?
    ");

    $u->execute([
      $title,
      $desc,
      $date ?: null,
      $priceVal,
      $ccyVal,
      $open,
      $id
    ]);

    /* remove old images if requested */
    if(isset($_POST["delete_images"])){

      $stmt = $pdo->prepare("SELECT media_url FROM competition_media WHERE competition_id=?");
      $stmt->execute([$id]);
      $imgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

      foreach($imgs as $img){

        if(!empty($img["media_url"])){

          $file = __DIR__ . "/".$img["media_url"];

          if(file_exists($file)){
            unlink($file);
          }
        }
      }

      $pdo->prepare("DELETE FROM competition_media WHERE competition_id=?")->execute([$id]);
    }

    header("Location: index.php");
    exit;
  }
}
?>

<h2>Edit Competition</h2>
<p style="color:red;"><?= htmlspecialchars($msg) ?></p>

<form method="post">

  <input name="title" value="<?= htmlspecialchars($c["title"]) ?>" required><br><br>

  <textarea name="description" rows="5" cols="50"><?= htmlspecialchars($c["description"] ?? "") ?></textarea><br><br>

  <label>Date:</label>
  <input type="date" name="event_date" value="<?= htmlspecialchars($c["event_date"] ?? "") ?>"><br><br>

  <label>Price (optional):</label>
  <input name="price" value="<?= htmlspecialchars($c["price"] ?? "") ?>"><br><br>

  <label>Currency:</label>
  <input name="currency" value="<?= htmlspecialchars($c["currency"] ?? "") ?>"><br><br>

  <label>
    <input type="checkbox" name="is_open" <?= ((int)$c["is_open"]===1) ? "checked" : "" ?>>
    Registration Open
  </label><br><br>

  <label>
    <input type="checkbox" name="delete_images">
    Remove all images
  </label><br><br>

  <button type="submit">Update</button>

</form>

<p><a href="index.php">Back</a></p>