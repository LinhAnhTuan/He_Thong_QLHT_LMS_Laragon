<?php
require_once("../../config/db.php");

session_start();

if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = (int)$_POST['submission_id'];
    $grade = (float)$_POST['grade'];
    $feedback = trim($_POST['feedback']);

    // kiểm tra submission có thuộc về bài tập của teacher không
    $stmt = $pdo->prepare("
        SELECT s.assignment_id, c.teacher_id, a.course_id
        FROM assignment_submissions s
        JOIN assignments a ON s.assignment_id=a.id
        JOIN courses c ON a.course_id=c.id
        WHERE s.id=? AND c.teacher_id=?
    ");
    $stmt->execute([$submission_id, $_SESSION['user_id']]);
    $check = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$check) die("⛔ Không có quyền.");

    // update điểm & nhận xét
    $stmt = $pdo->prepare("UPDATE assignment_submissions SET grade=?, feedback=? WHERE id=?");
    $stmt->execute([$grade, $feedback, $submission_id]);

    header("Location: submissions.php?assignment_id=".$check['assignment_id']);
    exit;
}
