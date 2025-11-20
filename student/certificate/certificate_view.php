<?php
require_once("../../config/db.php");
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../login.php");
    exit;
}

$certId = (int)$_GET['cert_id'];
$studentId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT c.*, co.title, co.class_name, u.name AS teacher_name
    FROM certificates c
    JOIN courses co ON c.course_id = co.id
    JOIN users u ON co.teacher_id = u.id
    WHERE c.id = ? AND c.student_id = ?
");
$stmt->execute([$certId, $studentId]);
$cert = $stmt->fetch();

if (!$cert) die("⛔ Không tìm thấy chứng chỉ!");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Chứng chỉ khóa học</title>
<link rel="stylesheet" href="../../style.css">
</head>
<body>

<div class="certificate"> 
    <h1>GIẤY CHỨNG NHẬN HOÀN THÀNH KHÓA HỌC</h1>
    <h2><?= htmlspecialchars($cert['class_name'] . " - " . $cert['title']) ?></h2>

    <p>Chứng nhận học viên</p>
    <p><b><?= htmlspecialchars($_SESSION['name']) ?></b></p>
    <p>Đã hoàn thành khóa học <?= htmlspecialchars($cert['title']) ?> .</p>

    <?php if($cert['score'] !== null): ?>
        <p><b>Điểm số:</b> <?= htmlspecialchars($cert['score']) ?></p>
    <?php endif; ?>

    <p><b>Giảng viên hướng dẫn:</b> <?= htmlspecialchars($cert['teacher_name']) ?></p>
    <p><b>Ngày cấp:</b> <?= date('d/m/Y', strtotime($cert['issued_at'])) ?></p>
        <br>
    <?php if($cert['qr_code']): ?>
        <div class="qr">
            <img src="../../<?= htmlspecialchars($cert['qr_code']) ?>" alt="QR Code">
            <p><small>Quét mã để xác minh chứng chỉ</small></p>
        </div>
    <?php endif; ?>

    <div class="signature">
        <div>
            <div class="signature-line"></div>
            <span>Giảng viên</span><br>
            <b><?= htmlspecialchars($cert['teacher_name']) ?></b>
        </div>
        <div>
            <div class="signature-line"></div>
            <span>Học viên</span><br>
            <b><?= htmlspecialchars($_SESSION['name']) ?></b>
        </div>
    </div>

    <a href="javascript:history.back()" class="btn-back">⬅ Quay lại</a>
</div>

</body>
</html>
<style>
body {
    background: radial-gradient(circle at top, #f0f4ff, #d6e4f0);
    font-family: "Segoe UI", sans-serif;
}

.certificate {
    background: #fffdf7;
    border: 12px double #34495e;
    border-radius: 20px;
    padding: 50px 60px;
    width: 800px;
    margin: 60px auto;
    text-align: center;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    position: relative;
}

.certificate::before {
    content: "";
    position: absolute;
    inset: 15px;
    border: 2px solid #bdc3c7;
    border-radius: 15px;
    pointer-events: none;
}

.certificate h1 {
    font-size: 26px;
    color: #2c3e50;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.certificate h2 {
    color: #2980b9;
    font-size: 20px;
    margin-top: 10px;
}

.certificate p {
    font-size: 17px;
    color: #2c3e50;
    margin: 10px 0;
}

.certificate b {
    color: #1a5276;
}

.qr {
    margin-top: 20px;
}

.qr img {
    width: 130px;
    border: 2px solid #7f8c8d;
    border-radius: 8px;
    background: #fff;
    padding: 5px;
}

.signature {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    padding: 0 40px;
}

.signature div {
    text-align: center;
    font-size: 14px;
    color: #555;
}

.signature-line {
    width: 200px;
    border-top: 1px solid #333;
    margin: 5px auto;
}

.logo {
    position: absolute;
    top: 30px;
    left: 50%;
    transform: translateX(-50%);
    width: 90px;
    opacity: 0.2;
}

.btn-back {
    display: inline-block;
    margin-top: 25px;
    background: #3498db;
    color: white;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 6px;
    transition: background 0.3s;
}
.btn-back:hover {
    background: #1c5980;
}
</style>