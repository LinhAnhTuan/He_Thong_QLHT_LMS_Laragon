<?php
require_once("../../config/db.php");

session_start();

// ✅ Kiểm tra quyền teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = (int)$_SESSION['user_id'];
$lesson_id = (int)($_GET['id'] ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);

// ✅ Lấy thông tin bài học để check quyền
$sql = "
    SELECT l.id, s.course_id, c.teacher_id
    FROM lessons l
    JOIN sections s ON l.section_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE l.id=? AND c.teacher_id=?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$lesson_id, $teacherId]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    die("⛔ Bài học không tồn tại hoặc bạn không có quyền.");
}

// ✅ Xóa
$stmt = $pdo->prepare("DELETE FROM lessons WHERE id=?");
$stmt->execute([$lesson_id]);

header("Location: list.php?course_id=" . $course_id);
exit;
