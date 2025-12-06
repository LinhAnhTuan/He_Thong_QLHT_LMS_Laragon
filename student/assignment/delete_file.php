<?php
require_once("../../config/db.php");
require_once("../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

session_start();

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../login.php");
    exit;
}

$studentId = $_SESSION['user_id'];
$file_id = (int)($_GET['id'] ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);

// Lấy file và kiểm tra quyền
$stmt = $pdo->prepare("
    SELECT f.*, s.student_id 
    FROM assignment_submission_files f
    JOIN assignment_submissions s ON f.submission_id = s.id
    WHERE f.id=? AND s.student_id=?
");
$stmt->execute([$file_id, $studentId]);
$file = $stmt->fetch();

if ($file) {
    // Xóa file vật lý
    $filePath = "../../" . $file['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Xóa file trong DB
    $stmt = $pdo->prepare("DELETE FROM assignment_submission_files WHERE id=?");
    $stmt->execute([$file_id]);
}

header("Location: submit.php?course_id=$course_id");
exit;
