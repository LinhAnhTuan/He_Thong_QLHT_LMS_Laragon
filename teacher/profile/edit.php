<?php
// teacher/profile/edit.php
require_once("../../config/db.php");

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php"); exit;
}

$teacherId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, name, email, phone, avatar FROM users WHERE id=? AND role='teacher'");
$stmt->execute([$teacherId]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$teacher) die("⛔ Không tìm thấy thông tin giảng viên.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    $avatarPath = $teacher['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $uploadDir = "../../uploads/teachers/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['avatar']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
            $avatarPath = "uploads/teachers/" . $fileName;
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=?, avatar=? WHERE id=? AND role='teacher'");
    $stmt->execute([$name, $phone, $avatarPath, $teacherId]);

    $_SESSION['name']   = $name;
    $_SESSION['avatar'] = $avatarPath;
    $success = "✅ Cập nhật thông tin thành công!";
}

$avatar = !empty($teacher['avatar']) ? "../../" . $teacher['avatar'] : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>👨‍🏫 Chỉnh sửa thông tin cá nhân</title>
<link rel="stylesheet" href="../../style.css">
<style>
.page-title { font-size: 22px; font-weight: bold; }

/* Form container */
.form-box {
    max-width: 800px;
    margin: 30px auto;
    padding: 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}

/* Header */
.form-box h2 { text-align: center; margin-bottom: 30px; color: #2c3e50; }

/* Grid 2 cột */
.profile-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* Mobile 1 cột */
@media(max-width:768px) { .profile-form { grid-template-columns: 1fr; } }

/* Labels + Inputs */
.profile-form label { font-weight: bold; margin-bottom: 6px; display: block; padding-top: 10px;}
.profile-form input[type="text"], .profile-form input[type="email"], .profile-form input[type="file"] {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

/* Avatar preview */
.avatar-preview { display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
.avatar-preview img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #007bff;
}

/* Submit button */
.profile-form button {
    grid-column: span 2;
    padding: 12px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}
.profile-form button:hover { background: #0056b3; }

/* Success message */
.success { text-align:center; margin-bottom: 20px; color:green; font-weight:bold; }
</style>
</head>
<body>
<?php include "../includes/sidebar_teacher2.php"; ?>

<div class="content">
    <header class="header">
        <div class="page-title"><b><?= htmlspecialchars($_SESSION['name']); ?></b></div>
        <div class="user">
            <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar" style="width:40px;height:40px;border-radius:50%">
            <span>Giảng viên</span>
            <a href="../../logout.php">🚪 Đăng xuất</a>
        </div>
    </header>

    <div class="main">
        <div class="form-box">
            <h2>👨‍🏫 Chỉnh sửa thông tin cá nhân</h2>
            <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>

            <form method="post" enctype="multipart/form-data" class="profile-form">
                <div>
                    <label>Họ và tên:</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($teacher['name']) ?>" required>
            
                    <label>Email (không thể đổi):</label>
                    <input type="text" value="<?= htmlspecialchars($teacher['email']) ?>" disabled>
                </div>

                <div>
                    <label>Số điện thoại:</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($teacher['phone']) ?>">

                    <label>Ảnh đại diện:</label>
                    <div class="avatar-preview">
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar">
                    </div>
                    <input type="file" name="avatar" accept="image/*">
                </div>

                <button type="submit">💾 Lưu thay đổi</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
