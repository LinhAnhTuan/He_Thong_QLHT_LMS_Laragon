<?php
session_start();
require_once("../../../config/db.php");
require_once("../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admins.php");
    exit;
}

$id = (int)$_GET['id'];

// Lấy thông tin người dùng
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Người dùng không tồn tại!");
}

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $class_name = $_POST['class_name'] ?? null;
    $student_code = $_POST['student_code'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $new_password = $_POST['password'] ?? '';

    // Nếu nhập mật khẩu mới, hash và cập nhật
    if (!empty($new_password)) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, class_name=?, student_code=?, phone=?, password_hash=? WHERE id=?");
        $stmt->execute([$name,$email,$role,$class_name,$student_code,$phone,$password_hash,$id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, class_name=?, student_code=?, phone=? WHERE id=?");
        $stmt->execute([$name,$email,$role,$class_name,$student_code,$phone,$id]);
    }

    header("Location: admins.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Sửa người dùng</title>
<link rel="stylesheet" href="../../../style.css">
<style>
main.main { padding: 20px; font-family: Arial, sans-serif; background: #f4f6f9; min-height: 90vh; display:flex; justify-content:center; align-items:center;}
form { max-width:500px; background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1);}
label {display:block; margin-top:10px; font-weight:600;}
input, select {width:100%; padding:8px 10px; margin-top:5px; border:1px solid #ccc; border-radius:6px;}
button {height: 44px ;margin-top:15px; padding:10px 20px; background:#28a745; color:#fff; border:none; border-radius:6px; cursor:pointer;}
button:hover {background:#218838;}
a.back-btn {display:inline-block; margin-top:10px; padding:10px 18px; background:#007bff; color:#fff; border-radius:6px; text-decoration:none;}
a.back-btn:hover {background:#0056b3;}
h1 {margin-bottom:20px;}
</style>
</head>
<body>
<?php include("../../includes/sidebar3.php"); ?>
<div class="content">
<main class="main">
<form method="post">
    <h1>✏️ Sửa người dùng</h1>
    <label>Tên:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
    
    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
    
  
    <!-- Vai trò -->
    <label>Vai trò:</label>
    <select id="role" name="role" required>
        <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
    </select>

    <label>Điện thoại:</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

    <label>Mật khẩu mới (để trống nếu không đổi):</label>
    <input type="password" name="password" placeholder="Nhập mật khẩu mới nếu muốn đổi">

    <button type="submit">Cập nhật</button>
    <a href="admins.php" class="back-btn">🔙 Quay lại</a>
</form>
</main>
</div>
</body>
</html>
<script>
// Ẩn/hiện Lớp và MSSV theo vai trò
function toggleStudentFields() {
    const role = document.getElementById('role').value;
    const fields = document.querySelectorAll('.student-field');
    fields.forEach(f => f.style.display = (role === 'student') ? 'block' : 'none');
}

// Khi chọn vai trò
document.getElementById('role').addEventListener('change', toggleStudentFields);

// Khi load trang
window.addEventListener('load', toggleStudentFields);
</script>
