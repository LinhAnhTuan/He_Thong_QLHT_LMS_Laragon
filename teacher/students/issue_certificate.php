<?php
require_once("../../config/db.php");
require '../../vendor/autoload.php';
require_once("../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

session_start();

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = $_SESSION['user_id'];
$studentId = (int)($_GET['student_id']);
$courseId  = (int)($_GET['course_id']);

// ✅ Kiểm tra quyền sở hữu khóa học
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id=? AND teacher_id=?");
$stmt->execute([$courseId, $teacherId]);
$course = $stmt->fetch();
if (!$course) {
    die("⛔ Bạn không có quyền cấp chứng chỉ cho khóa học này!");
}

// ✅ Lấy thông tin sinh viên
$stmt = $pdo->prepare("SELECT name, student_code FROM users WHERE id=?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();
if (!$student) die("Không tìm thấy sinh viên!");

// ✅ Kiểm tra trùng chứng chỉ
$stmt = $pdo->prepare("SELECT id FROM certificates WHERE student_id=? AND course_id=?");
$stmt->execute([$studentId, $courseId]);
if ($stmt->fetch()) {
    die("⚠️ Sinh viên này đã có chứng chỉ!");
}

// ✅ Lấy tổng số bài tập của khóa học
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM assignments WHERE course_id=?");
$stmt->execute([$courseId]);
$totalAssignments = (int)$stmt->fetchColumn();

// ✅ Lấy số bài học viên đã nộp
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT assignment_id) as submitted_count 
    FROM assignment_submissions 
    WHERE student_id=? AND assignment_id IN (SELECT id FROM assignments WHERE course_id=?)
");
$stmt->execute([$studentId, $courseId]);
$submittedCount = (int)$stmt->fetchColumn();

// ✅ Kiểm tra tiến độ: ≥70%
$stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE course_id=?");
$stmt->execute([$courseId]);
$totalAssignments = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT assignment_id) FROM assignment_submissions WHERE student_id=? AND assignment_id IN (SELECT id FROM assignments WHERE course_id=?)");
$stmt->execute([$studentId, $courseId]);
$submittedCount = (int)$stmt->fetchColumn();

$progress = $totalAssignments ? ($submittedCount / $totalAssignments) * 100 : 0;
if ($progress < 70) {
    die("<script>alert('⚠️ Học viên chưa hoàn thành từ đủ 70% tiến độ khóa học. Hiện tại: ".round($progress,1)."%'); window.history.back();</script>");
}

// ✅ Tạo nội dung QR
$qrData = "Chúc mừng bạn đã hoàn thành khóa học: {$course['title']}\n".
          "Học viên: {$student['name']} ({$student['student_code']})\n".
          "Ngày cấp: " . date('d/m/Y');

// ✅ Tạo QR code
$qrCode = new QrCode(
    data: $qrData,
    encoding: new Encoding('UTF-8'),
    errorCorrectionLevel: ErrorCorrectionLevel::Low
);

$writer = new PngWriter();
$result = $writer->write($qrCode);

// ✅ Lưu ảnh QR
$qrDir = '../../uploads/qrcodes/';
if (!is_dir($qrDir)) mkdir($qrDir, 0777, true);
$qrFile = $qrDir . "cert_{$studentId}_{$courseId}.png";
$result->saveToFile($qrFile);

// ✅ Ghi vào CSDL
$stmt = $pdo->prepare("INSERT INTO certificates (student_id, course_id, score, issued_at, issued_by, qr_code)
                       VALUES (?, ?, NULL, NOW(), ?, ?)");
$stmt->execute([$studentId, $courseId, $teacherId, substr($qrFile, 6)]);

// ✅ Chuyển hướng
header("Location: list.php?course_id=$courseId&msg=success");
exit;
?>
