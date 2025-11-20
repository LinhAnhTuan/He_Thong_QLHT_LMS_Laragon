<?php
session_start();
require_once("../../config/db.php");
require_once("../includes/log_helper.php");
autoLogAction($pdo);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php"); exit;
}

function getSetting($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
}

$message = '';
$logoPath = getSetting($pdo, 'logo') ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = $_POST['site_name'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';

    // Upload logo
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $uploadDir = '../../uploads/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = $uploadDir.'logo_'.time().'.'.$ext;
        if(move_uploaded_file($_FILES['logo']['tmp_name'], $filename)) {
            $logoPath = substr($filename, 6);
        }
    }

    $settings = [
        'site_name' => $site_name,
        'contact_email' => $contact_email,
        'contact_phone' => $contact_phone,
        'logo' => $logoPath
    ];

    foreach($settings as $key => $val){
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value)
            VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $stmt->execute([$key, $val]);
    }

    $message = "✅ Cập nhật cấu hình thành công!";
}

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>⚙️ Cấu hình hệ thống</title>
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
.form-box h1 { text-align: center; margin-bottom: 30px; color: #2c3e50; }

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

/* Logo preview */
.avatar-preview { display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
.avatar-preview img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 12px;
    border: 2px solid #007bff;
}

/* Submit button */
.profile-form button {
    grid-column: span 2;
    padding: 12px;
    background: #28a745;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}
.profile-form button:hover { background: #218838; }

/* Success message */
.success { text-align:center; margin-bottom: 20px; color:green; font-weight:bold; }
</style>
</head>
<body>
<?php include("../includes/sidebar2.php"); ?>

<div class="content">
<header class="header">
    <div class="page-title"><b><?= htmlspecialchars($_SESSION['name']); ?></b></div>
    <div class="user">
        <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar" style="width:40px;height:40px;border-radius:50%">
        <span>Admin</span>
        <a href="../../logout.php">🚪 Đăng xuất</a>
    </div>
</header>

<div class="main">
    <div class="form-box">
        <h1>⚙️ Cấu hình hệ thống</h1>
        <?php if(!empty($message)) echo "<p class='success'>$message</p>"; ?>

        <form method="post" enctype="multipart/form-data" class="profile-form">
            <div>
                <label for="site_name">Tên hệ thống:</label>
                <input type="text" name="site_name" value="<?= htmlspecialchars(getSetting($pdo, 'site_name')) ?>">

                <label for="contact_email">Email liên hệ:</label>
                <input type="email" name="contact_email" value="<?= htmlspecialchars(getSetting($pdo, 'contact_email')) ?>">
            </div>

            <div>
                <label for="contact_phone">Số điện thoại:</label>
                <input type="text" name="contact_phone" value="<?= htmlspecialchars(getSetting($pdo, 'contact_phone')) ?>">

                <label for="logo">Logo:</label>
                <div class="avatar-preview">
                    <?php if($logoPath): ?>
                        <img src="../../<?= $logoPath ?>" alt="Logo">
                    <?php endif; ?>
                </div>
                <input type="file" name="logo" accept="image/*">
            </div>

            <button type="submit">💾 Lưu thay đổi</button>
        </form>
    </div>
</div>
</div>
</body>
</html>
