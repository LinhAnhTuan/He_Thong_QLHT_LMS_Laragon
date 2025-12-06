<?php
session_start();
require_once("../../config/db.php");
require_once("../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log
// Chỉ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID không hợp lệ!");
}

$id = intval($_GET['id']);

// Kiểm tra lịch học có tồn tại không
$stmt = $pdo->prepare("SELECT id FROM schedules WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    die("Lịch học không tồn tại!");
}

// Tiến hành xóa
$delete = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
$delete->execute([$id]);

// Quay về trang danh sách
header("Location: list.php");
exit;
?>
