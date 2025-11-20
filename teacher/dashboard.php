<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}
if ($_SESSION["role"] !== "teacher") {
    die("Bạn không có quyền truy cập trang này!");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
</head>
<body>
    <h2>Xin chào Giảng viên, <?php echo $_SESSION["name"]; ?> 👋</h2>
    <ul>
        <li><a href="my_courses.php">Khóa học của tôi</a></li>
        <li><a href="create_course.php">Tạo khóa học mới</a></li>
        <li><a href="assignments.php">Quản lý bài tập</a></li>
        <li><a href="../logout.php">Đăng xuất</a></li>
    </ul>
</body>
</html>
