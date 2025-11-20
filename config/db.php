<?php
$host = "localhost";
$dbname = "lms_db";   // Tên database bạn đã tạo
$username = "root";     // User mặc định XAMPP
$password = "";         // Password mặc định là rỗng (nếu chưa đặt)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Kết nối CSDL thất bại: " . $e->getMessage());
}
