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

// Avatar
$avatar = !empty($_SESSION['avatar']) ? "../../../" . $_SESSION['avatar'] : "../../../uploads/default.png";

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $class_name = $_POST['class_name'] ?? null;
    $student_code = $_POST['student_code'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $password = password_hash("123456", PASSWORD_DEFAULT); // Mật khẩu mặc định

    $stmt = $pdo->prepare("INSERT INTO users (name,email,role,class_name,student_code,phone,password_hash) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$name,$email,$role,$class_name,$student_code,$phone,$password]);

    header("Location: teachers.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Thêm người dùng</title>
  <link rel="stylesheet" href="../../../style.css">
  <style>
    main.main { padding: 20px; }
    form { max-width: 500px; background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 5px rgba(0,0,0,0.1);}
    label { display:block; margin-bottom:15px; font-weight:500; }
    input[type=text], input[type=email], select {
        width: 100%; padding:8px 10px; margin-top:5px; border:1px solid #ccc; border-radius:5px;
    }
    button { padding:10px 20px; background:#28a745; color:#fff; border:none; border-radius:5px; cursor:pointer; }
    button:hover { background:#218838; }
  </style>
</head>
<body>
  <!-- SIDEBAR -->
  <?php include("../../includes/sidebar3.php"); ?>

  <div class="content">
    <header class="header">
      <div><b style="font-size: 25px;"><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
      <div class="user">
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
        <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        <a href="../../../login.php">🚪 Đăng xuất</a>
      </div>
    </header>

    <main class="main">
  <h1>Thêm sinh viên mới</h1>
  
  <!-- Form nhập tay -->
  <form method="post">
    <label for="name">Tên:</label>
    <input type="text" id="name" name="name" placeholder="Nhập tên người dùng" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" placeholder="Nhập email" required>

    <label for="role">Vai trò:</label>
    <select id="role" name="role" required>
      <option value="teacher">Giảng viên</option>
    </select>

    <label for="student_code">Mã giảng viên:</label>
    <input type="text" id="student_code" name="student_code" placeholder="Nhập mã giảng viên">

    <label for="phone">Điện thoại:</label>
    <input type="text" id="phone" name="phone" placeholder="Nhập số điện thoại">

    <button type="submit">➕ Thêm người dùng</button>
    <a href="teachers.php" class="back-btn">🔙 Quay lại</a>
  </form>

  <!-- Form import Excel -->
  <form action="import_excel.php" method="post" enctype="multipart/form-data" style="margin-top:30px;">
    <label for="excel">📂 Import từ Excel (.xlsx):</label>
    <input type="file" name="excel_file" accept=".xls,.xlsx" required>
    <button type="submit">📤 Upload & Import</button>
  </form>
</main>


  </div>
</body>
</html>
<style>
  /* Main container */
  main.main {
      padding: 20px;
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      min-height: 90vh;
  }

  /* Form */
  form {
    max-width: 500px;
    margin: 0 auto;          /* căn giữa theo ngang */
    background: #fff;
    padding: 25px 30px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

  /* Form labels */
  label {
      display: block;
      margin-bottom: 0px;
      padding-top: 10px;
      font-weight: 600;
      color: #333;
      font-size: 14px;
  }

  /* Inputs & selects */
  input[type="text"],
  input[type="email"],
  select {
      width: 100%;
      padding: 10px 12px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
      box-sizing: border-box;
      transition: border 0.2s;
  }

  input[type="text"]:focus,
  input[type="email"]:focus,
  select:focus {
      border-color: #007bff;
      outline: none;
  }

  /* Submit button */
  button {

      padding: 10px 20px;
      background: #28a745;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 15px;
      margin-top: 10px;
      transition: background 0.2s;
  }

  button:hover {
      background: #218838;
  }

  /* Quay lại button */
  a.back-btn {
      display: inline-block;
      margin-top: 10px;
      padding: 10px 18px;
      background: #007bff;
      color: #fff;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      transition: background 0.2s;
  }

  a.back-btn:hover {
      background: #0056b3;
  }

  /* Form headings */
  h1 {
      font-size: 24px;
      margin-bottom: 15px;
      color: #2c3e50;
  }
  
</style>
