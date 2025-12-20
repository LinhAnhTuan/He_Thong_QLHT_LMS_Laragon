<?php
// teacher/students/delete.php
require_once("../../config/db.php");

session_start();

if ($_SESSION['role'] !== 'teacher') { 
    header("Location: ../../login.php"); 
    exit; 
}

$teacherId = $_SESSION['user_id'];
$course_id = (int)($_REQUEST['course_id'] ?? 0);

function removeStudentFromCourse($pdo, $enrollId, $teacherId, $course_id) {
    // Lấy student_id từ enroll
    $stmt = $pdo->prepare("
        SELECT e.student_id 
        FROM course_enrollments e
        JOIN courses c ON e.course_id=c.id
        WHERE e.id=? AND c.teacher_id=? AND c.id=?
    ");
    $stmt->execute([$enrollId, $teacherId, $course_id]);
    $row = $stmt->fetch();

    if (!$row) return;
    $student_id = $row['student_id'];

    // ❌ Xóa bài nộp
    $pdo->prepare("
        DELETE a FROM assignment_submissions a
        JOIN assignments asg ON a.assignment_id = asg.id
        WHERE a.student_id=? AND asg.course_id=?
    ")->execute([$student_id, $course_id]);

    // ❌ Xóa tiến độ bài học
    $pdo->prepare("
        DELETE lp FROM lesson_progress lp
        JOIN lessons l ON lp.lesson_id=l.id
        JOIN sections s ON l.section_id=s.id
        WHERE lp.student_id=? AND s.course_id=?
    ")->execute([$student_id, $course_id]);

    // ❌ Xóa tiến độ chương
    $pdo->prepare("
        DELETE sp FROM section_progress sp
        JOIN sections s ON sp.section_id=s.id
        WHERE sp.student_id=? AND s.course_id=?
    ")->execute([$student_id, $course_id]);

    // ❌ Xóa tiến độ khóa học
    $pdo->prepare("DELETE FROM course_progress WHERE student_id=? AND course_id=?")
        ->execute([$student_id, $course_id]);

    // ❌ Xóa kết quả quiz
    $pdo->prepare("
        DELETE qa FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id=q.id
        WHERE qa.student_id=? AND q.course_id=?
    ")->execute([$student_id, $course_id]);

    // ❌ Xóa chứng chỉ
    $pdo->prepare("DELETE FROM certificates WHERE student_id=? AND course_id=?")
        ->execute([$student_id, $course_id]);

    // ✅ Cuối cùng: Xóa enrollment
    $pdo->prepare("DELETE FROM course_enrollments WHERE id=?")->execute([$enrollId]);
}

// ✅ Nếu là xóa nhiều
if (!empty($_POST['ids']) && is_array($_POST['ids'])) {
    foreach ($_POST['ids'] as $id) {
        removeStudentFromCourse($pdo, (int)$id, $teacherId, $course_id);
    }
} elseif (!empty($_GET['id'])) {
    // ✅ Xóa đơn lẻ
    removeStudentFromCourse($pdo, (int)$_GET['id'], $teacherId, $course_id);
}

header("Location: list.php?course_id=".$course_id);
exit;
