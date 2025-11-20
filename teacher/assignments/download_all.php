<?php
require_once("../../config/db.php");
require_once("../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

session_start();

if ($_SESSION['role'] !== 'teacher') { exit("⛔ Không có quyền."); }

$assignment_id = (int)($_GET['assignment_id'] ?? 0);

// lấy danh sách file nộp
$stmt = $pdo->prepare("
    SELECT s.file_submission, u.student_code, u.name
    FROM assignment_submissions s
    JOIN assignments a ON s.assignment_id=a.id
    JOIN courses c ON a.course_id=c.id
    JOIN users u ON s.student_id=u.id
    WHERE s.assignment_id=? AND c.teacher_id=?
");
$stmt->execute([$assignment_id, $_SESSION['user_id']]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$files) die("⛔ Không có file nào.");

// tạo zip
$zipName = "submissions_".$assignment_id.".zip";
$zipPath = sys_get_temp_dir()."/".$zipName;
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

foreach ($files as $f) {
    if ($f['file_submission'] && file_exists("../../".$f['file_submission'])) {
        $zip->addFile("../../".$f['file_submission'], $f['student_code']."_".basename($f['file_submission']));
    }
}

$zip->close();

// tải về
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=\"$zipName\"");
readfile($zipPath);
unlink($zipPath);
exit;
