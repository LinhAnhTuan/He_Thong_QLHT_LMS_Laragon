<?php
session_start();
require_once("../../config/db.php");
require_once("../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log
// Chỉ admin mới được xuất
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Gọi thư viện PhpSpreadsheet
require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Lấy từ khóa tìm kiếm (nếu có)
$search = trim($_GET['search'] ?? '');

try {
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.name AS teacher_name 
            FROM courses c
            JOIN users u ON c.teacher_id = u.id
            WHERE c.title LIKE ? OR c.class_name LIKE ? OR u.name LIKE ?
            ORDER BY c.created_at DESC
        ");
        $like = "%$search%";
        $stmt->execute([$like, $like, $like]);
    } else {
        $stmt = $pdo->query("
            SELECT c.*, u.name AS teacher_name 
            FROM courses c
            JOIN users u ON c.teacher_id = u.id
            ORDER BY c.created_at DESC
        ");
    }
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// Tạo file Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Tiêu đề cột
$sheet->setCellValue('A1', 'ID')
      ->setCellValue('B1', 'Tên khóa học')
      ->setCellValue('C1', 'Lớp')
      ->setCellValue('D1', 'Giảng viên')
      ->setCellValue('E1', 'Mô tả')
      ->setCellValue('F1', 'Ngày tạo');

// Đổ dữ liệu
$row = 2;
foreach ($courses as $c) {
    $sheet->setCellValue('A'.$row, $c['id']);
    $sheet->setCellValue('B'.$row, $c['title']);
    $sheet->setCellValue('C'.$row, $c['class_name']);
    $sheet->setCellValue('D'.$row, $c['teacher_name']);
    $sheet->setCellValue('E'.$row, $c['description']);
    $sheet->setCellValue('F'.$row, $c['created_at']);
    $row++;
}

// Xuất file
$writer = new Xlsx($spreadsheet);
$filename = "danh_sach_khoa_hoc.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
$writer->save("php://output");
exit;
