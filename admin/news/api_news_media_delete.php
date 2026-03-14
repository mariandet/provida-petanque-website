<?php
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../config/db.php";

header("Content-Type: application/json; charset=UTF-8");

function json_response($status, $message = "", $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {
        json_response("ERROR", "Invalid JSON");
    }

    $id = (int)($data["id"] ?? 0);

    if ($id <= 0) {
        json_response("ERROR", "Invalid gallery image ID");
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id, image_path, news_id
        FROM news_gallery
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        json_response("ERROR", "Gallery image not found");
    }

    $deleteStmt = $pdo->prepare("DELETE FROM news_gallery WHERE id = ?");
    $deleteStmt->execute([$id]);

    if ($deleteStmt->rowCount() < 1) {
        throw new Exception("Failed to delete gallery image");
    }

    $pdo->commit();

    $imagePath = trim((string)($row["image_path"] ?? ""));
    if ($imagePath !== "") {
        $fullPath = __DIR__ . "/" . ltrim($imagePath, "/");
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    json_response("SUCCESS", "Gallery image deleted successfully", [
        "deleted_id" => $id,
        "news_id" => (int)$row["news_id"]
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response("ERROR", $e->getMessage());
}
?>