<?php
session_start();
require_once("../../../config/db.php");
require '../../../vendor/autoload.php';
require_once("../../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file_tmp = $_FILES['excel_file']['tmp_name'];
    $file_ext = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);

    if (!in_array($file_ext, ['xls','xlsx'])) {
        die("❌ Chỉ chấp nhận file Excel (.xls, .xlsx)");
    }

    try {
        $spreadsheet = IOFactory::load($file_tmp);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Lấy dòng tiêu đề
        $headers = array_map('trim', $rows[0]);

        // Xác định vị trí cột dựa theo tên
        $colIndex = [
            'name'     => array_search('Họ tên', $headers),
            'usercode' => array_search('Mã SV/GV', $headers),
            'role'     => array_search('Vai trò', $headers),
            'email'    => array_search('Email', $headers),
            'phone'    => array_search('Số điện thoại', $headers),
            'class'    => array_search('Lớp', $headers)
        ];

        $count = 0;
        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // bỏ qua tiêu đề

            $name  = trim($row[$colIndex['name']] ?? '');
            $code  = trim($row[$colIndex['usercode']] ?? '');
            $role  = strtolower(trim($row[$colIndex['role']] ?? 'student'));
            $email = trim($row[$colIndex['email']] ?? '');
            $phone = trim($row[$colIndex['phone']] ?? '');
            $class = trim($row[$colIndex['class']] ?? '');

            if ($name && $email) {
                $password = password_hash("123456", PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users 
                    (name, student_code, role, email, phone, class_name, password_hash) 
                    VALUES (?,?,?,?,?,?,?)");

                try {
                    $stmt->execute([$name, $code, $role, $email, $phone, $class, $password]);
                    $count++;
                } catch (PDOException $e) {
                    // Nếu email trùng thì bỏ qua
                    continue;
                }
            }
        }

        $_SESSION['msg'] = "✅ Đã import $count người dùng thành công!";
        header("Location: students.php");
        exit;

    } catch (Exception $e) {
        die("❌ Lỗi khi đọc file: " . $e->getMessage());
    }
}
