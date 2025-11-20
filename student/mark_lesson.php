<?php
require_once("../config/db.php");
session_start();

if ($_SESSION['role'] !== 'student') {
    echo json_encode(["status" => "error", "message" => "Không có quyền"]);
    exit;
}

$studentId = $_SESSION['user_id'];
$lessonId = (int)($_POST['lesson_id'] ?? 0);

if ($lessonId <= 0) {
    echo json_encode(["status" => "error", "message" => "Thiếu ID bài học"]);
    exit;
}

// ✅ Cập nhật hoặc chèn vào bảng lesson_progress
$stmt = $pdo->prepare("
    INSERT INTO lesson_progress (student_id, lesson_id, is_completed, completed_at)
    VALUES (?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE is_completed=1, completed_at=NOW()
");
$stmt->execute([$studentId, $lessonId]);

echo json_encode(["status" => "success", "message" => "Đã đánh dấu hoàn thành"]);
