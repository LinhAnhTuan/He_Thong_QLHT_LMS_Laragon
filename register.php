<!-- <?php
session_start();
require 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"]);
  $email = trim($_POST["email"]);
  $phone = $_POST["phone"] ?? null;
  $role  = $_POST["role"];
  $class = $_POST["class_name"] ?? null;
  $student_code = $_POST["student_code"] ?? null;
  $password = $_POST["password"];

  // Kiểm tra email tồn tại
  $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
  $stmt->execute([$email]);
  if ($stmt->fetchColumn() > 0) {
    $error = "Email đã tồn tại!";
  } else {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Xử lý upload avatar
    $avatarPath = "uploads/default.png"; // ảnh mặc định
    if (!empty($_FILES["avatar"]["name"])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Tạo thư mục nếu chưa có
        }

        $fileName = time() . "_" . basename($_FILES["avatar"]["name"]);
        $target = $uploadDir . $fileName;

        $imageFileType = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "gif"];

        if (in_array($imageFileType, $allowed)) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target)) {
                $avatarPath = $target; // Lưu đường dẫn file vào DB
            }
        }
    }

    // Lưu vào DB
    $stmt = $conn->prepare("
      INSERT INTO users (role, name, email, password_hash, avatar, class_name, student_code, phone)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$role, $name, $email, $password_hash, $avatarPath, $class, $student_code, $phone]);

    $_SESSION['success'] = "Đăng ký thành công, vui lòng đăng nhập!";
    header("Location: login.php");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký</title>
</head>
<body>
    <h2>Đăng ký tài khoản</h2>
    <?php if (!empty($error)) echo "<p style='color:red'>$error</p>"; ?>
    <?php if (!empty($_SESSION['success'])) { echo "<p style='color:green'>".$_SESSION['success']."</p>"; unset($_SESSION['success']); } ?>

    <form method="POST" enctype="multipart/form-data">
        Họ tên: <input type="text" name="name" required><br>
        Email: <input type="email" name="email" required><br>
        Điện thoại: <input type="text" name="phone"><br>
        Vai trò: 
        <select name="role">
            <option value="student">Sinh viên</option>
            <option value="teacher">Giảng viên</option>
        </select><br>
        Lớp: <input type="text" name="class_name"><br>
        Mã sinh viên: <input type="text" name="student_code"><br>
        Mật khẩu: <input type="password" name="password" required><br>
        Ảnh đại diện: <input type="file" name="avatar" accept="image/*"><br>
        <button type="submit">Đăng ký</button>
    </form>

    <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
</body>
</html> -->
