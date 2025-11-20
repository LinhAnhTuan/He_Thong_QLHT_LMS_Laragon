<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}
if ($_SESSION["role"] !== "admin") {
    die("Bạn không có quyền truy cập trang này!");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
</head>
<body>
    <h2>Xin chào Admin, <?php echo $_SESSION["name"]; ?> 👋</h2>
    <ul>
        <li><a href="manage_users.php">Quản lý người dùng</a></li>
        <li><a href="manage_courses.php">Quản lý khóa học</a></li>
        <li><a href="manage_notifications.php">Quản lý thông báo</a></li>
        <li><a href="../logout.php">Đăng xuất</a></li>
    </ul>
</body>
</html>
