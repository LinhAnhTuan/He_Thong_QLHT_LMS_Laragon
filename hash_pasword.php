<?php
// Nhập mật khẩu bạn muốn hash
$password = "admin123";

// Hash mật khẩu bằng bcrypt
$hash = password_hash($password, PASSWORD_DEFAULT);

// In ra chuỗi hash để copy vào CSDL
echo "Mật khẩu gốc: " . $password . "<br>";
echo "Mật khẩu hash: " . $hash;
?>
