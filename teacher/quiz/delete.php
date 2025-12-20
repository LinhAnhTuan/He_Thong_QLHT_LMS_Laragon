<?php
require_once("../../config/db.php");
session_start();

// ✅ Kiểm tra quyền teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);

// ✅ Kiểm tra quiz thuộc về giáo viên
$stmt = $pdo->prepare("
    SELECT q.id FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    WHERE q.id=? AND q.course_id=? AND c.teacher_id=?
");
$stmt->execute([$id, $course_id, $teacherId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die("⛔ Quiz không tồn tại hoặc bạn không có quyền.");
}

try {
    // ✅ Xóa quiz (kèm theo câu hỏi + options nếu cần)
    $pdo->prepare("DELETE FROM quiz_options WHERE question_id IN (SELECT id FROM quiz_questions WHERE quiz_id=?)")->execute([$id]);
    $pdo->prepare("DELETE FROM quiz_questions WHERE quiz_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM quiz_attempts WHERE quiz_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM quizzes WHERE id=?")->execute([$id]);

    header("Location: list.php?course_id=$course_id");
    exit;
} catch (PDOException $e) {
    die("❌ Lỗi khi xóa Quiz: " . $e->getMessage());
}
