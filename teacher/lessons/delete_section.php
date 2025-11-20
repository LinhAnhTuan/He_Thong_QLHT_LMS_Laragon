<?php
require_once("../../config/db.php");
require_once("../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = (int)$_SESSION['user_id'];
$section_id = (int)($_GET['id'] ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);

// Kiểm tra quyền sở hữu section qua course
$stmt = $pdo->prepare("
    SELECT s.id 
    FROM sections s 
    JOIN courses c ON s.course_id=c.id 
    WHERE s.id=? AND c.teacher_id=?");
$stmt->execute([$section_id, $teacherId]);
$section = $stmt->fetch();

if (!$section) {
    die("⛔ Không tìm thấy section hoặc bạn không có quyền.");
}

// Xóa tất cả bài học trong section
$pdo->prepare("DELETE FROM lessons WHERE section_id=?")->execute([$section_id]);

// Xóa section
$pdo->prepare("DELETE FROM sections WHERE id=?")->execute([$section_id]);

header("Location: list.php?course_id=" . $course_id);
exit;
