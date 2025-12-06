<?php
session_start();
require_once("../../config/db.php");
require_once("../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

// ✅ Chỉ cho phép admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$course_id = $_GET['id'] ?? '';
if (!$course_id || !is_numeric($course_id)) {
    die("❌ ID khóa học không hợp lệ.");
}

try {
    // Lấy thông tin khóa học để ghi log
    $stmt = $pdo->prepare("SELECT title, cover_image FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        die("❌ Không tìm thấy khóa học.");
    }

    // Xóa ảnh bìa (nếu có và không phải ảnh mặc định)
    if (!empty($course['cover_image']) && $course['cover_image'] !== 'uploads/courses/default.jpg') {
        $filePath = "../../" . $course['cover_image'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Xóa khóa học
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);

    // 🧾 Ghi log hành động
    addLog(
        $pdo,
        $_SESSION['user_id'],
        'delete_course',
        'Xóa khóa học: ' . $course['title']
    );

    header("Location: list.php");
    exit;
} catch (PDOException $e) {
    die("Lỗi khi xóa: " . $e->getMessage());
}
