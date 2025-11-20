<?php
session_start();
require_once("../../../config/db.php");

// Chỉ admin mới được truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit;
}

$avatar = !empty($_SESSION['avatar']) ? "../../../" . $_SESSION['avatar'] : "../../../uploads/default.png";
$search = trim($_GET['search'] ?? '');
$filter_role = "teacher"; // Cố định

try {
    $sql = "SELECT * FROM users WHERE role = ?";
    $params = [$filter_role];

    if ($search) {
        $sql .= " AND (name LIKE ? OR student_code LIKE ? OR class_name LIKE ? OR email LIKE ?)";
        $likeSearch = "%$search%";
        $params = [$filter_role, $likeSearch, $likeSearch, $likeSearch, $likeSearch];
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý quản trị viên</title>
<link rel="stylesheet" href="../../../style.css">
</head>
<body>
<?php include("../../includes/sidebar3.php"); ?>

<div class="content">
<header class="header">
  <div><b style="font-size: 25px;"><?= htmlspecialchars($_SESSION['name']) ?></b></div>
  <div class="user">
    <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
    <span><?= htmlspecialchars($_SESSION['name']) ?></span>
    <a href="../../../logout.php">🚪 Đăng xuất</a>
  </div>
</header>

<main class="main">
  <h1>Quản lý giảng viên</h1>

  <form method="get" class="search-form">
    <input type="text" name="search" placeholder="Tìm kiếm" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">🔍 Tìm kiếm</button>
    <a href="teacher.php" class="btn">🔄 Reset</a>
  </form>

  <form method="post" action="bulk_delete.php" onsubmit="return confirm('Bạn có chắc muốn xóa các quản trị viên đã chọn?')">
    <a href="add_teacher.php" class="btn">➕ Thêm giảng viên</a>
    <a href="export_users.php?role=admin&search=<?= urlencode($search) ?>" class="btn">📥 Xuất Excel</a>
    

    <table>
      <tr class="centered">
        <th><input type="checkbox" id="checkAll"></th>
        <th>ID</th>
        <th>Tên</th>
        <th>Mã Giảng viên</th>
        <th>Email</th>
        <th>Điện thoại</th>
        <th>Hành động</th>
      </tr>

      <?php if (empty($users)): ?>
        <tr><td colspan="6" class="no-data">Chưa có dữ liệu</td></tr>
      <?php endif; ?>

      <?php foreach ($users as $u): ?>
      <tr>
        <td><input type="checkbox" name="ids[]" value="<?= $u['id'] ?>"></td>
        <td><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['student_code']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars($u['phone']) ?></td>
        <td>
          <a href="edit.php?id=<?= $u['id'] ?>" class="edit">✏️ Sửa</a> | 
          <a href="delete.php?id=<?= $u['id'] ?>" class="delete" onclick="return confirm('Xóa quản trị viên này?')">🗑️ Xóa</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table><br>
    <button type="submit" class="btn" style="background:#dc3545;">🗑️ Xóa đã chọn</button>
  </form>

  <script>
  document.getElementById('checkAll').addEventListener('change', function(){
      document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
  });
  </script>
</main>
</div>
</body>
</html>
<style>
     table { 
        width: 100%; 
        border-collapse: collapse; 
        background: #fff; 
        margin-top: 20px; 
      }
      table th, table td { 
          padding: 10px; 
          border: 1px solid #ddd; 
          text-align: center; 
          vertical-align: middle;
      }
      table th { 
          background: #2c3e50; 
          color: #fff; 
      }
      table tr:hover { 
          background: #f1f1f1; 
      }


    /* --- Nút Thêm / Sửa / Xóa --- */
    .btn { padding: 8px 12px; background: #28a745; color: #fff; border-radius: 5px; text-decoration: none; display: inline-block; margin-bottom: 10px; }
    a.edit { color: #007bff; font-weight: bold; text-decoration: none; margin-right: 5px; }
    a.delete { color: #dc3545; font-weight: bold; text-decoration: none; }
    a.edit:hover, a.delete:hover { text-decoration: underline; }

    /* --- Form tìm kiếm --- */
    form.search-form {
        margin-top: 20px;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    form.search-form input {
        flex: 1;
        padding: 12px 15px;
        font-size: 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
        transition: border 0.2s;
        min-width: 900px;
    }

    form.search-form input:focus {
        border-color: #007bff;
        outline: none;
    }

    form.search-form button {
        padding: 12px 25px;
        font-size: 15px;
        border: none;
        border-radius: 6px;
        background: #007bff;
        color: #fff;
        cursor: pointer;
        transition: background 0.2s;
    }

    form.search-form button:hover {
        background: #0056b3;
    }

    form.search-form a.btn {
        padding: 12px 25px;
        font-size: 15px;
        border-radius: 6px;
        background: #28a745;
        color: #fff;
        text-decoration: none;
        transition: background 0.2s;
    }

    form.search-form a.btn:hover {
        background: #218838;
    }

    .no-data { text-align:center; font-style:italic; color:#555; }
    tr.centered th,
    tr.centered td { text-align: center; vertical-align: middle; }

    /* --- Bộ lọc vai trò --- */
    .filter-btns { margin-top: 15px; }
    .filter-btns a {
        padding: 10px 18px;
        margin-right: 8px;
        border-radius: 6px;
        background: #6c757d;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        transition: background 0.2s;
    }
    .filter-btns a.active { background: #007bff; }
    .filter-btns a:hover { background: #5a6268; }
</style>