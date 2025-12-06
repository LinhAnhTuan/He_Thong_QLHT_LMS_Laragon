<?php
session_start();
require_once("../../../config/db.php");
require_once("../../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ids'])) {
    $ids = $_POST['ids'];
    $in = str_repeat('?,', count($ids) - 1) . '?';
    $sql = "DELETE FROM users WHERE id IN ($in)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);

    $_SESSION['msg'] = "✅ Đã xóa " . count($ids) . " người dùng!";
} else {
    $_SESSION['msg'] = "⚠️ Chưa chọn người dùng nào để xóa!";
}

header("Location: teachers.php");
exit;
