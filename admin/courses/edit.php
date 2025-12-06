<?php
session_start();
require_once("../../config/db.php");
require_once("../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log
// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id=?");
$stmt->execute([$id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Khóa học không tồn tại!");
}

// Lấy danh sách giảng viên
$stmt = $pdo->query("SELECT id, name FROM users WHERE role='teacher' ORDER BY name ASC");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $teacher_id = $_POST['teacher_id'] ?? '';
    $class_name = trim($_POST['class_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cover_image = $course['cover_image'];

    if (empty($title) || empty($teacher_id)) {
        $error = "Vui lòng nhập đầy đủ tiêu đề và chọn giảng viên!";
    } else {
        // Upload ảnh mới (nếu có)
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];
            if (in_array($ext, $allowed)) {
                $uploadDir = '../../uploads/courses';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $filename = $uploadDir.'/'.time().'_'.rand(1000,9999).'.'.$ext;
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $filename)) {
                    $cover_image = substr($filename, 6);
                }
            } else {
                $error = "Chỉ cho phép upload ảnh (jpg, jpeg, png, gif)";
            }
        }

        if (!$error) {
            try {
                $stmt = $pdo->prepare("UPDATE courses SET title=?, teacher_id=?, class_name=?, description=?, cover_image=? WHERE id=?");
                $stmt->execute([$title, $teacher_id, $class_name, $description, $cover_image, $id]);
                header("Location: list.php");
                exit;
            } catch (PDOException $e) {
                $error = "Lỗi sửa khóa học: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>✏️ Sửa khóa học</title>
<link rel="stylesheet" href="../../style.css">
<style>
main.main { padding:20px; font-family: Arial, sans-serif; background:#f4f6f9; min-height:90vh; display:flex; justify-content:center; align-items:center;}
form { max-width:600px; background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1);}
label {display:block; margin-top:10px; font-weight:600;}
input, select, textarea {width:100%; padding:8px 10px; margin-top:5px; border:1px solid #ccc; border-radius:6px;}
textarea {resize: vertical;}
button {height:44px; margin-top:15px; padding:10px 20px; background:#28a745; color:#fff; border:none; border-radius:6px; cursor:pointer;}
button:hover {background:#218838;}
a.back-btn {display:inline-block; margin-top:10px; padding:10px 18px; background:#007bff; color:#fff; border-radius:6px; text-decoration:none;}
a.back-btn:hover {background:#0056b3;}
.error {color:red; font-weight:bold; margin-bottom:10px;}
</style>
</head>
<body>
<?php include("../includes/sidebar2.php"); ?>
<div class="content">
<main class="main">
<form method="post" enctype="multipart/form-data">
    <h1>✏️ Sửa khóa học</h1>
    <?php if($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <label>Tiêu đề khóa học:</label>
    <input type="text" name="title" value="<?= htmlspecialchars($course['title']) ?>" required>

    <label>Giảng viên:</label>
    <select name="teacher_id" required>
        <option value="">-- Chọn giảng viên --</option>
        <?php foreach($teachers as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $t['id']==$course['teacher_id']?'selected':'' ?>>
                <?= htmlspecialchars($t['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="class_name">Lớp:</label>
    <input type="text" id="class_name" name="class_name" value="<?= htmlspecialchars($course['class_name']) ?>">

    <label>Mô tả:</label>
    <textarea name="description" rows="4"><?= htmlspecialchars($course['description']) ?></textarea>

    <label>Ảnh cover (hiện tại):</label><br>
    <?php if($course['cover_image']): ?>
        <img src="../../<?= $course['cover_image'] ?>" width="120" style="margin:10px 0;">
    <?php endif; ?>
    <input type="file" name="cover_image" accept="image/*">

    <button type="submit">Cập nhật</button>
    <a href="list.php" class="back-btn">🔙 Quay lại</a>
</form>
</main>
</div>
</body>
</html>
