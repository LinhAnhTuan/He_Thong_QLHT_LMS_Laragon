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
$submission_id = (int)($_GET['id'] ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);

// Lấy submission để kiểm tra quyền
$stmt = $pdo->prepare("
    SELECT * FROM assignment_submissions 
    WHERE id=? AND student_id=?
");
$stmt->execute([$submission_id, $studentId]);
$submission = $stmt->fetch();

if ($submission) {
    // Lấy file trong submission
    $stmt = $pdo->prepare("SELECT * FROM assignment_submission_files WHERE submission_id=?");
    $stmt->execute([$submission_id]);
    $files = $stmt->fetchAll();

    // Xóa file vật lý
    foreach ($files as $f) {
        $filePath = "../../" . $f['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Xóa file trong DB
    $stmt = $pdo->prepare("DELETE FROM assignment_submission_files WHERE submission_id=?");
    $stmt->execute([$submission_id]);

    // Xóa submission
    $stmt = $pdo->prepare("DELETE FROM assignment_submissions WHERE id=?");
    $stmt->execute([$submission_id]);
}

header("Location: submit.php?course_id=$course_id");
exit;
