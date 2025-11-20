<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}
if ($_SESSION["role"] !== "student") {
    die("Bạn không có quyền truy cập trang này!");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
</head>
<body>
    <h2>Xin chào Sinh viên, <?php echo $_SESSION["name"]; ?> 👋</h2>
    <ul>
        <li><a href="courses.php">Khóa học đã đăng ký</a></li>
        <li><a href="quizzes.php">Làm bài kiểm tra</a></li>
        <li><a href="assignments.php">Nộp bài tập</a></li>
        <li><a href="certificates.php">Chứng chỉ</a></li>
        <li><a href="../logout.php">Đăng xuất</a></li>
    </ul>
</body>
</html>
