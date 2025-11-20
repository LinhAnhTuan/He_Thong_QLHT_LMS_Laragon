<?php
session_start();
require_once("../../../config/db.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Chỉ admin mới được phép
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit;
}

// Luôn cố định xuất sinh viên
$role = "student"; 
$search = trim($_GET['search'] ?? '');

try {
    $sql = "SELECT id, name, student_code, email, class_name, phone, created_at 
            FROM users WHERE role = ?";
    $params = [$role];

    if ($search) {
        $sql .= " AND (name LIKE ? OR student_code LIKE ? OR class_name LIKE ? OR email LIKE ?)";
        $likeSearch = "%$search%";
        $params = [$role, $likeSearch, $likeSearch, $likeSearch, $likeSearch];
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

if (empty($students)) {
    die("Không có dữ liệu sinh viên để xuất.");
}

// Tạo file Excel
require '../../../vendor/autoload.php';
require_once("../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Tiêu đề cột
$sheet->setCellValue("A1", "ID");
$sheet->setCellValue("B1", "Tên");
$sheet->setCellValue("C1", "Mã sinh viên");
$sheet->setCellValue("D1", "Email");
$sheet->setCellValue("E1", "Lớp");
$sheet->setCellValue("F1", "Điện thoại");
$sheet->setCellValue("G1", "Ngày tạo");

// Ghi dữ liệu
$row = 2;
foreach ($students as $st) {
    $sheet->setCellValue("A{$row}", $st['id']);
    $sheet->setCellValue("B{$row}", $st['name']);
    $sheet->setCellValue("C{$row}", $st['student_code']);
    $sheet->setCellValue("D{$row}", $st['email']);
    $sheet->setCellValue("E{$row}", $st['class_name']);
    $sheet->setCellValue("F{$row}", $st['phone']);
    $sheet->setCellValue("G{$row}", $st['created_at']);
    $row++;
}

// Auto size cột
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Xuất file
$filename = "students_export_" . date("Ymd_His") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
