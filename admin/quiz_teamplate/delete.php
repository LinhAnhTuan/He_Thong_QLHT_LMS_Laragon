<?php
require_once("../../config/db.php");
session_start();

require_once("../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT file_path FROM quiz_templates WHERE id=?");
$stmt->execute([$id]);
$template = $stmt->fetch();

if ($template) {
    $filePath = "../../" . $template['file_path'];
    if (file_exists($filePath)) unlink($filePath);
    $pdo->prepare("DELETE FROM quiz_templates WHERE id=?")->execute([$id]);
}

header("Location: list.php");
exit;
