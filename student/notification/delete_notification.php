<?php
require_once("../../config/db.php");
require_once("../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

session_start();

if ($_SESSION['role'] !== 'student') { 
    header("Location: ../../login.php"); 
    exit; 
}

$id = (int)($_GET['id'] ?? 0);
$studentId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    DELETE FROM notifications 
    WHERE id=? AND (user_id=? OR user_id IS NULL)
      AND course_id IN (SELECT course_id FROM course_enrollments WHERE student_id=?)
");
$stmt->execute([$id, $studentId, $studentId]);

header("Location: notifications.php");
exit;
