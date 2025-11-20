<?php
require_once("../../config/db.php");
session_start();

// ✅ Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM quiz_templates ORDER BY created_at DESC");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>📄 Quản lý mẫu Quiz</title>
<link rel="stylesheet" href="../../style.css">
</head>
<body>
<?php include "../includes/sidebar2.php"; ?>

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
    <div class="main">
        <h2>📄 Quản lý mẫu Quiz</h2>
        <a href="add.php" class="btn">➕ Thêm mẫu mới</a>

        <table class="table">
            <tr>
            <th>ID</th>
            <th>Tên mẫu</th>
            <th>Loại file</th>
            <th>Ngày tạo</th>
            <th>Hành động</th>
            </tr>
            <?php foreach ($templates as $t): ?>
            <tr>
            <td><?= $t['id'] ?></td>
            <td><?= htmlspecialchars($t['name']) ?></td>
            <td><?= htmlspecialchars($t['file_type']) ?></td>
            <td><?= $t['created_at'] ?></td>
            <td>
                <a href="../../<?= htmlspecialchars($t['file_path']) ?>" class="btn" download>👀 Xem</a>
                <a href="delete.php?id=<?= $t['id'] ?>" class="btn btn-del" onclick="return confirm('Xóa mẫu này?')">🗑 Xóa</a>
            </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
<style>
    .table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    }

    .table th,
    .table td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: left;
    }

    .table th {
    background: #f4f4f4;
    }

    .btn {
    padding: 6px 10px;
    background: #27ae60;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    }

    .btn:hover {
    background: #1e8449;
    }

    .btn-del {
    background: #e74c3c;
    }

    .btn-del:hover {
    background: #c0392b;
    }
</style>