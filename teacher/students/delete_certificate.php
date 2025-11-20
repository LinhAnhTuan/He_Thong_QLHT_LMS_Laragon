<?php
// teacher/students/delete_certificate.php
require_once("../../config/db.php");
require_once("../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

session_start();

if ($_SESSION['role'] !== 'teacher') { 
    header("Location: ../../login.php"); 
    exit; 
}

$teacherId = $_SESSION['user_id'];
$student_id = (int)($_GET['student_id'] ?? 0);
$course_id  = (int)($_GET['course_id'] ?? 0);

// Kiểm tra quyền
$stmt = $pdo->prepare("SELECT id FROM courses WHERE id=? AND teacher_id=?");
$stmt->execute([$course_id, $teacherId]);
$course = $stmt->fetch();

if (!$course) {
    die("⛔ Không có quyền xóa chứng chỉ trong khóa học này.");
}

// Xóa chứng chỉ
$stmt = $pdo->prepare("DELETE FROM certificates WHERE student_id=? AND course_id=?");
$stmt->execute([$student_id, $course_id]);

header("Location: list.php?course_id=".$course_id);
exit;
