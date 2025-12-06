<?php
require_once("../../config/db.php");
session_start();
require_once("../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log
// ✅ Chỉ cho admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$error = $success = "";

// ✅ Xử lý thêm mẫu quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    if (!$name || !isset($_FILES['file'])) {
        $error = "⚠️ Vui lòng nhập tên và chọn file mẫu.";
    } else {
        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['xlsx', 'xls', 'csv', 'docx'];

        if (!in_array($ext, $allowed)) {
            $error = "❌ Định dạng file không được hỗ trợ.";
        } else {
            $targetDir = "../../uploads/templates/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            // Tạo tên file an toàn
            $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $fileName = time() . "_" . $safeName . "." . $ext;
            $targetPath = $targetDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $stmt = $pdo->prepare("INSERT INTO quiz_templates (name, file_path, file_type, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$name, "uploads/templates/" . $fileName, $ext]);
                $success = "✅ Thêm mẫu quiz thành công!";
            } else {
                $error = "❌ Lỗi khi upload file.";
            }
        }
    }
}

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>➕ Thêm mẫu quiz</title>
<link rel="stylesheet" href="../../style.css">
</head>
<body>
<?php include "../includes/sidebar2.php"; ?>

<div class="content">
    <header class="header">
        <div><b class="page-title"><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
        <div class="user">
            <img src="<?php echo htmlspecialchars($avatar); ?>" 
                 alt="avatar" style="width:40px; height:40px; border-radius:50%">
            <span>Admin</span>
            <a href="../../logout.php">🚪 Đăng xuất</a>
        </div>
    </header>

    <div class="main">
        <h2>➕ Thêm mẫu quiz</h2>

        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form">
            <label>Tên mẫu:</label>
            <input type="text" name="name" placeholder="VD: Mẫu trắc nghiệm Excel" required>

            <label>Chọn file mẫu (Excel, Word, CSV):</label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv,.docx" required>

            <br>
            <button type="submit" class="btn">💾 Lưu mẫu</button>
            <a href="list.php" class="btn" style="background:#3498db;">⬅ Quay lại</a>
        </form>
    </div>
</div>
</body>
</html>
<style>
.page-title { 
    font-size: 22px; 
}

.form label { 
    display: block; 
    margin-top: 10px; 
}

.form input[type="text"], 
.form input[type="file"] { 
    width: 100%; 
    padding: 8px; 
    margin-top: 5px; 
}

.btn { 
    padding: 8px 12px; 
    border-radius: 4px; 
    background: #27ae60; 
    color: white; 
    text-decoration: none; 
    border: none; 
    cursor: pointer; 
}

.btn:hover { 
    background: #219150; 
}

.error { 
    background: #f8d7da; 
    color: #721c24; 
    padding: 10px; 
    border-radius: 5px; 
    margin-bottom: 10px; 
}

.success { 
    background: #d4edda; 
    color: #155724; 
    padding: 10px; 
    border-radius: 5px; 
    margin-bottom: 10px; 
}
</style>

