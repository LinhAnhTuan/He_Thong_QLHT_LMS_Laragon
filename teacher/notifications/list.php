<?php
require_once("../../config/db.php");
session_start();

if ($_SESSION['role'] !== 'teacher') { header("Location: ../../login.php"); exit; }
$teacherId = $_SESSION['user_id'];

// Lấy khóa học của giáo viên
$stmt = $pdo->prepare("SELECT id,title FROM courses WHERE teacher_id=?");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll();

$course_id = (int)($_GET['course_id'] ?? 0);
$notifications = [];

if ($course_id) {
    $stmt = $pdo->prepare("
        SELECT n.*, u.name 
        FROM notifications n
        LEFT JOIN users u ON n.user_id=u.id
        WHERE n.course_id=? 
          AND (n.user_id IS NULL OR n.user_id IN (
              SELECT student_id FROM course_enrollments WHERE course_id=?
          ))
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$course_id, $course_id]);
   
    $notifications = $stmt->fetchAll();
}
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>🔔 Quản lý Thông báo</title>
  <link rel="stylesheet" href="../../style.css">
</head>
<body>
<?php include "../includes/sidebar_teacher2.php"; ?>
<div class="content">
  <header class="header">
        <div class="page-title"><b><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
        <div class="user">
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
        <span>Giảng viên</span>
        <a href="../../logout.php">🚪 Đăng xuất</a>
        </div>
    </header>
<div class="main">
  <h2>🔔 Quản lý Thông báo</h2>
  <form method="get">
    <label>Chọn khóa học:</label>
    <select name="course_id" onchange="this.form.submit()">
      <option value="">-- Chọn --</option>
      <?php foreach ($courses as $c): ?>
        <option value="<?= $c['id'] ?>" <?= ($c['id']==$course_id)?'selected':'' ?>>
          <?= htmlspecialchars($c['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($course_id): ?>
    <a href="send.php?course_id=<?= $course_id ?>" class="btn">➕ Gửi thông báo</a>
    <table class="table-noti">
        <tr>
            <th>ID</th>
            <th>👨‍🎓 Sinh viên</th>
            <th>📩 Nội dung</th>
            <th>📅 Ngày</th>
            <th>📖 Đã đọc?</th>
        </tr>
        <?php foreach ($notifications as $n): ?>
            <tr>
            <td><?= $n['id'] ?></td>
            <td>
              <?= $n['user_id'] ? htmlspecialchars($n['name']) : "📢 Toàn lớp" ?>
            </td>
            <td><?= htmlspecialchars($n['message']) ?></td>
            <td><?= $n['created_at'] ?></td>
            <td>
                <?php if ($n['is_read']): ?>
                <span class="badge-read ok">✔ Đã đọc</span>
                <?php else: ?>
                <span class="badge-read no">❌ Chưa đọc</span>
                <?php endif; ?>
            </td>
            </tr>
        <?php endforeach; ?>
        </table>

  <?php endif; ?>
</div>
</div>
</body>
</html>
<style>
  .page-title{
    font-size: 22px;
  }

.content h2 {
  color: #2c3e50;
  margin-bottom: 20px;
}

.content form {
  margin-bottom: 15px;
}

.content select {
  padding: 6px 10px;
  border-radius: 5px;
  border: 1px solid #ccc;
}

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

.table-noti tr:nth-child(even) {
  background: #f9f9f9;
}

.table-noti tr:hover {
  background: #ecf6ff;
}

.badge-read {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: bold;
}

.badge-read.ok {
  background: #2ecc71;
  color: #fff;
}

.badge-read.no {
  background: #e74c3c;
  color: #fff;
}

</style>