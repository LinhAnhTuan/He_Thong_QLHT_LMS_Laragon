<?php
session_start();
require_once "../config/db.php";

// ===============================
// 🔒 Kiểm tra đăng nhập & quyền
// ===============================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ===============================
// ⚙️ Xử lý lọc dữ liệu
// ===============================
$userFilter = $_GET['user'] ?? '';
$actionFilter = $_GET['action'] ?? '';
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';

$where = [];
$params = [];

// lọc theo người dùng
if (!empty($userFilter)) {
    $where[] = "l.user_id = ?";
    $params[] = $userFilter;
}

// lọc theo loại hành động
if (!empty($actionFilter)) {
    $where[] = "l.action_type = ?";
    $params[] = $actionFilter;
}

// lọc theo khoảng thời gian
if (!empty($dateFrom)) {
    $where[] = "DATE(l.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $where[] = "DATE(l.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// ===============================
// 🧾 Truy vấn dữ liệu logs
// ===============================
try {
    $sql = "
        SELECT 
            l.id, 
            u.name AS user_name, 
            l.action_type, 
            l.description, 
            l.created_at, 
            l.ip_address
        FROM activity_logs l
        LEFT JOIN users u ON l.user_id = u.id
        $whereSQL
        ORDER BY l.created_at DESC
        LIMIT 200
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // lấy danh sách user và action cho combobox
    $users = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $actions = $pdo->query("SELECT DISTINCT action_type FROM activity_logs WHERE action_type IS NOT NULL ORDER BY action_type")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// Avatar mặc định
$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lịch sử hoạt động</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <!-- SIDEBAR -->
  <?php include "includes/sidebar1.php"; ?>

  <!-- MAIN -->
  <div class="content">
    <header class="header">
      <div><b style="font-size: 25px;"><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
      <div class="user">
        <img src="<?php echo htmlspecialchars($avatar); ?>" 
             alt="avatar" style="width:40px; height:40px; border-radius:50%">
        <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        <a href="../logout.php">🚪 Đăng xuất</a>
      </div>
    </header>

    <main class="main">
      <h1>🧾 Nhật ký hoạt động hệ thống</h1>
      <p>Xem và lọc lịch sử hoạt động của người dùng.</p>

      <!-- Bộ lọc -->
      <form class="filter-form" method="GET">
        <select name="user">
          <option value="">-- Tất cả người dùng --</option>
          <?php foreach ($users as $u): ?>
            <option value="<?php echo $u['id']; ?>" <?php if ($u['id']==$userFilter) echo 'selected'; ?>>
              <?php echo htmlspecialchars($u['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select name="action">
          <option value="">-- Tất cả hành động --</option>
          <?php foreach ($actions as $a): ?>
            <option value="<?php echo htmlspecialchars($a); ?>" <?php if ($a==$actionFilter) echo 'selected'; ?>>
              <?php echo htmlspecialchars($a); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <input type="date" name="from" value="<?php echo htmlspecialchars($dateFrom); ?>">
        <input type="date" name="to" value="<?php echo htmlspecialchars($dateTo); ?>">

        <button type="submit">🔍 Lọc</button>
      </form>

      <!-- Bảng log -->
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Người dùng</th>
            <th>Loại hành động</th>
            <th>Mô tả</th>
            <th>Thời gian</th>
            <th>Địa chỉ IP</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($logs)): ?>
            <?php foreach ($logs as $i => $log): ?>
              <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($log['user_name'] ?? 'Không xác định'); ?></td>
                <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                <td><?php echo htmlspecialchars($log['description']); ?></td>
                <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">Không có dữ liệu phù hợp</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </main>
  </div>
</body>
</html>
<style>
    table {width:100%; border-collapse:collapse; background:#fff; margin-top:20px;}
    table th, table td {border:1px solid #ddd; padding:10px; text-align:left;}
    table th {background:#2c3e50; color:#fff;}
    tr:hover {background:#f4f6f9;}
    .filter-form {
        display:flex; flex-wrap:wrap; gap:10px;
        background:#f4f6f9; padding:10px; border-radius:10px; margin-bottom:20px;
    }
    .filter-form select, .filter-form input[type=date] {
        padding:8px; border:1px solid #ccc; border-radius:5px;
    }
    .filter-form button {
        padding:8px 15px; background:#2c3e50; color:#fff; border:none; border-radius:5px; cursor:pointer;
    }
    .filter-form button:hover {background:#1a242f;}
  </style>