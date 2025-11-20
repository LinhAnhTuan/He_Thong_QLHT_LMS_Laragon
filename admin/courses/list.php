<?php
session_start();
require_once("../../config/db.php");

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Lấy từ khóa tìm kiếm
$search = trim($_GET['search'] ?? '');

try {
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.name AS teacher_name 
            FROM courses c
            JOIN users u ON c.teacher_id = u.id
            WHERE c.title LIKE ? OR c.class_name LIKE ? OR u.name LIKE ?
            ORDER BY c.created_at DESC
        ");
        $like = "%$search%";
        $stmt->execute([$like, $like, $like]);
    } else {
        $stmt = $pdo->query("
            SELECT c.*, u.name AS teacher_name 
            FROM courses c
            JOIN users u ON c.teacher_id = u.id
            ORDER BY c.created_at DESC
        ");
    }
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Quản lý khóa học</title>
<link rel="stylesheet" href="../../style.css">
<style>
table {width:100%; border-collapse: collapse; margin-top:20px;}
th, td {border:1px solid #ddd; padding:10px; text-align:center;}
th {background:#2c3e50; color:#fff;}
tr:hover {background:#f1f1f1;}
.btn {padding:8px 12px; border-radius:6px; background:#28a745; color:#fff; text-decoration:none; margin-bottom:10px; display:inline-block;}
a.edit {color:#007bff; text-decoration:none; margin-right:5px;}
a.delete {color:#dc3545; text-decoration:none;}
a.edit:hover, a.delete:hover {text-decoration:underline;}
form.search-form {display:flex; gap:10px; margin-bottom:10px;}
form.search-form input {flex:1; padding:8px; border-radius:6px; border:1px solid #ccc;}
form.search-form button {padding:8px 15px; border:none; background:#007bff; color:#fff; border-radius:6px; cursor:pointer;}
form.search-form a.btn-reset {background:#6c757d;}
</style>
</head>
<body>
<?php include("../includes/sidebar2.php"); ?>

<div class="content">
    <header class="header">
      <div><b style="font-size: 25px;"><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
      <div class="user">
        <img src="<?php echo htmlspecialchars($avatar); ?>" 
             alt="avatar" style="width:40px; height:40px; border-radius:50%">
        <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        <a href="../../logout.php">🚪 Đăng xuất</a>
      </div>
    </header>
<main class="main">
<h1>📚 Quản lý khóa học</h1>

<form method="get" class="search-form">
  <input type="text" name="search" placeholder="Tìm theo tên, lớp, giảng viên" value="<?= htmlspecialchars($search) ?>">
  <button type="submit">🔍 Tìm kiếm</button>
  <a href="list.php" class="btn btn-reset">🔄 Reset</a>
</form>

<a href="add.php" class="btn">➕ Thêm khóa học</a>
<a href="export.php?search=<?= urlencode($search) ?>" class="btn">📤 Xuất Excel</a>


<table>
<tr>
<th>ID</th>
<th>Ảnh bìa</th>
<th>Tên khóa học</th>
<th>Lớp</th>
<th>Giảng viên</th>
<th>Hành động</th>
</tr>

<?php if(empty($courses)): ?>
<tr><td colspan="6" style="text-align:center;">Chưa có dữ liệu</td></tr>
<?php endif; ?>

<?php foreach($courses as $c): ?>
<tr>
<td><?= $c['id'] ?></td>
<td><?php if($c['cover_image']): ?><img src="../../<?= $c['cover_image'] ?>" width="60"><?php endif; ?></td>
<td><?= htmlspecialchars($c['title']) ?></td>
<td><?= htmlspecialchars($c['class_name']) ?></td>
<td><?= htmlspecialchars($c['teacher_name']) ?></td>
<td>
<a href="edit.php?id=<?= $c['id'] ?>" class="edit">✏️ Sửa</a> | 
<a href="delete.php?id=<?= $c['id'] ?>" class="delete" onclick="return confirm('Xóa khóa học này?')">🗑️ Xóa</a>
</td>
</tr>
<?php endforeach; ?>
</table>
</main>
</div>
</body>
</html>
