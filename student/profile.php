<?php
require_once("../config/db.php");
require_once("../includes/log_helper.php");
autoLogAction($pdo);

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../login.php"); exit;
}

$studentId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name, email, phone, avatar, student_code, class_name FROM users WHERE id = ?");
$stmt->execute([$studentId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name']);
    $phone  = trim($_POST['phone']);
    $class  = trim($_POST['class_name']);

    $avatarPath = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["avatar"]["name"]);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile)) {
            $avatarPath = "uploads/" . $fileName;
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, class_name=?, avatar=? WHERE id=?");
    $stmt->execute([$name, $phone, $class, $avatarPath, $studentId]);

    $_SESSION['name'] = $name;
    $_SESSION['avatar'] = $avatarPath;
    $success = "Cập nhật thông tin thành công!";
}

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>👤 Chỉnh sửa hồ sơ</title>
<link rel="stylesheet" href="../style.css">
<style>
.page-title{
  font-size: 22px
}
/* Container form */
.profile-container {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    max-width: 800px;
    width: 100%;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}

/* Header */
.profile-container h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #2c3e50;
}

/* Grid 2 cột */
.profile-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* Full width for mobile */
@media(max-width:768px) {
    .profile-form {
        grid-template-columns: 1fr;
    }
}

/* Form fields */
.profile-form label {
    font-weight: bold;
    margin-bottom: 6px;
    display: block;
    color: #34495e;
}
.profile-form input[type="text"],
.profile-form input[type="email"],
.profile-form input[type="file"] {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

/* Avatar preview */
.avatar-preview {
    display: flex;
    align-items: center;
    gap: 20px;
}
.avatar-preview img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #2980b9;
}

/* Submit button */
.profile-form button {
    grid-column: span 2;
    padding: 12px;
    background: #2980b9;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}
.profile-form button:hover {
    background: #1f5e87;
}

/* Success message */
.success-msg {
    text-align: center;
    margin-bottom: 20px;
    color: green;
    font-weight: bold;
}
</style>
</head>
<body>
<?php include "includes/sidebar.php"; ?>

<div class="content">
    <header class="header">
      <div class="page-title"><b><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
      <div class="user">
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px;height:40px;border-radius:50%">
        <span>Sinh viên</span>
        <a href="../logout.php">🚪 Đăng xuất</a>
      </div>
  </header>
      <div class="main">
        <h2>👤 Hồ sơ cá nhân</h2>
        <?php if (!empty($success)): ?>
            <div class="success-msg"><?= $success ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="profile-form">
            <div>
                <label>Họ và tên:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

                <label>Email (không thể đổi):</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>

                <label>Mã sinh viên:</label>
                <input type="text" value="<?= htmlspecialchars($user['student_code']) ?>" disabled>
            </div>

            <div>
                <label>Lớp:</label>
                <input type="text" name="class_name" value="<?= htmlspecialchars($user['class_name']) ?>">

                <label>Số điện thoại:</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

                <label>Ảnh đại diện:</label>
                <div class="avatar-preview">
                    <img src="../<?= htmlspecialchars($user['avatar'] ?: 'uploads/default.png') ?>" alt="avatar">
                </div>
                <input type="file" name="avatar" accept="image/*">
            </div>

            <button type="submit">💾 Lưu thay đổi</button>
        </form>
       
    </div>
</div>
</body>
</html>
