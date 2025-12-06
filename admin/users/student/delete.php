<?php
session_start();
require_once("../../../config/db.php");
require_once("../../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log
// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit;
}

// Lấy ID người dùng cần xóa
if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit;
}

$id = (int)$_GET['id'];

// Không cho xóa chính admin đang đăng nhập (tùy chọn)
if ($id == $_SESSION['user_id']) {
    die("Bạn không thể xóa chính mình!");
}

// Thực hiện xóa
$stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
$stmt->execute([$id]);

header("Location: students.php");
exit;
