<?php
require_once("../../config/db.php");
session_start();

if ($_SESSION['role'] !== 'student') { 
    header("Location: ../../login.php"); 
    exit; 
}
$studentId = $_SESSION['user_id'];

// Lấy thông báo
$stmt = $pdo->prepare("
    SELECT n.id, n.course_id, c.title AS course_name, u.name AS sender, n.message, n.created_at, n.user_id, n.is_read, 'noti' AS type
    FROM notifications n
    JOIN courses c ON n.course_id = c.id
    LEFT JOIN users u ON u.id = (
        SELECT teacher_id FROM courses WHERE id = n.course_id LIMIT 1
    )
    WHERE (n.user_id IS NULL OR n.user_id = ?)
      AND n.course_id IN (
        SELECT course_id FROM course_enrollments WHERE student_id=?
      )

    UNION

      SELECT a.id, a.course_id, c.title AS course_name, u.name AS sender,
            CONCAT(
                CAST('Có bài tập mới: ' AS CHAR CHARACTER SET utf8mb4), 
                CAST(a.title AS CHAR CHARACTER SET utf8mb4),
                IF(a.due_date IS NOT NULL, 
                    CONCAT(
                      CAST(' ⏰ Hạn nộp: ' AS CHAR CHARACTER SET utf8mb4), 
                      DATE_FORMAT(a.due_date,'%d/%m/%Y %H:%i')
                    ), 
                    ''
                )
            ) AS message,
            a.created_at,
            NULL AS user_id,
            1 AS is_read,
            'assignment' AS type
      FROM assignments a
      JOIN courses c ON a.course_id = c.id
      JOIN users u ON c.teacher_id = u.id
      WHERE a.course_id IN (
          SELECT course_id FROM course_enrollments WHERE student_id=?
      )
    ORDER BY created_at DESC
");
$stmt->execute([$studentId, $studentId, $studentId]);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>🔔 Thông báo của tôi</title>
  <link rel="stylesheet" href="../../style.css">
</head>
<body>
<?php include "../includes/sidebar2.php"; ?>
<div class="content">
  <header class="header">
        <div class="page-title"><b><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
        <div class="user">
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
        <span>Sinh viên</span>
        <a href="../../logout.php">🚪 Đăng xuất</a>
        </div>
  </header>
<div class="main">
  <h2>🔔 Thông báo</h2>
  <table class="table-noti">
    <tr>
      <th>📚 Khóa học</th>
      <th>👨‍🏫 Người gửi</th>
      <th>📩 Nội dung</th>
      <th>📅 Ngày</th>
      <th>⚙️ Hành động</th>
    </tr>
    <?php foreach ($notifications as $n): ?>
      <tr style="<?= ($n['is_read'] ? '' : 'font-weight:bold;') ?>">
        <td><?= htmlspecialchars($n['course_name']) ?></td>
        <td><?= $n['user_id'] ? htmlspecialchars($n['sender']) : "📢 Toàn lớp" ?></td>
        <td><?= strip_tags($n['message'], '<b><i><u><br>') ?></td>
        <td><?= $n['created_at'] ?></td>
        <td>
          <?php if ($n['type'] === 'noti'): ?>
            <?php if (!$n['is_read']): ?>
              <a href="read_notification.php?id=<?= $n['id'] ?>" class="btn-read">✅ Đã đọc</a>
            <?php endif; ?>
            <a href="delete_notification.php?id=<?= $n['id'] ?>" class="btn-del" onclick="return confirm('Xóa thông báo này?')">🗑</a>
          <?php else: ?>
            <i>Bài tập tự động</i>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
</div>
</body>
</html>

<style>
.page-title { font-size: 22px; }

.table-noti {
  width: 100%;
  border-collapse: collapse;
  margin-top: 15px;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.table-noti th, 
.table-noti td {
  padding: 10px 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}
.table-noti th {
  background: #3498db;
  color: white;
  font-weight: bold;
}
.table-noti tr:nth-child(even) { background: #f9f9f9; }
.table-noti tr:hover { background: #ecf6ff; }

.btn-read, .btn-del {
  display: inline-block;
  padding: 4px 8px;
  font-size: 12px;
  border-radius: 5px;
  text-decoration: none;
  margin-right: 4px;
}
.btn-read { background: #27ae60; color:#fff; }
.btn-read:hover { background: #1e8449; }
.btn-del { background: #e74c3c; color:#fff; }
.btn-del:hover { background: #c0392b; }
</style>
