<?php
require_once("../../config/db.php");
session_start();

if ($_SESSION['role'] !== 'teacher') { 
    header("Location: ../../login.php"); 
    exit; 
}

$teacherId = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

// Lấy danh sách khóa học
$stmt = $pdo->prepare("SELECT id,title FROM courses WHERE teacher_id=?");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll();

// Lấy danh sách bài tập
$assignments = [];
if ($course_id) {
  $stmt = $pdo->prepare("SELECT * FROM assignments WHERE course_id=? ORDER BY due_date DESC");
  $stmt->execute([$course_id]);
  $assignments = $stmt->fetchAll();
}
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>📂 Quản lý Bài tập</title>
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
  <h2>📂 Quản lý Bài tập</h2>

  <form method="get" style="margin-bottom: 15px;">
    <label><b>Chọn khóa học:</b></label>
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
    <a href="add.php?course_id=<?= $course_id ?>" class="btn">➕ Tạo bài tập</a>

    <?php if (!$assignments): ?>
      <p><i>Chưa có bài tập nào</i></p>
    <?php else: ?>
      <table class="table">
          <tr>
              <th>ID</th>
              <th>Tiêu đề</th>
              <th>Mô tả</th>
              <th>Hạn nộp</th>
              <th>Tệp đính kèm</th>
              <th>Thao tác</th>
          </tr>
          <?php foreach ($assignments as $a): ?>
              <tr>
                  <td><?= $a['id'] ?></td>
                  <td><?= htmlspecialchars($a['title']) ?></td>

                  <!-- Mô tả -->
                  <td style="max-width: 300px;">
                      <?= nl2br(htmlspecialchars($a['description'] ?? 'Không có')) ?>
                  </td>

                  <td><?= $a['due_date'] ? date("d/m/Y H:i", strtotime($a['due_date'])) : '-' ?></td>

                  <!-- File đính kèm -->
                  <td>
                      <?php if (!empty($a['file_attachment'])): ?>
                          <a href="../../<?= $a['file_attachment'] ?>" target="_blank">📎 File</a>
                      <?php else: ?>
                          -
                      <?php endif; ?>
                  </td>

                  <td>
                      <a href="submissions.php?assignment_id=<?= $a['id'] ?>" class="btn">📤 Xem bài nộp</a>
                  </td>
              </tr>
          <?php endforeach; ?>
      </table>

    <?php endif; ?>
  <?php endif; ?>
</div>
</div>
</body>
</html>
<style>
  .page-title{
      font-size: 22px;
    }
    /* Container nội dung */
    .content {
        
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    /* Tiêu đề */
    .content h2 {
        font-size: 22px;
        margin-bottom: 15px;
        color: #333;
    }

    /* Form chọn khóa học */
    .content form {
        margin-bottom: 15px;
    }
    .content label {
        font-weight: bold;
        margin-right: 10px;
    }
    .content select {
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
    }

    
    .btn:hover {
        color: red;
    }

    /* Bảng danh sách */
    .content table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .content table th, 
    .content table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }
    .content table th {
        background: #f8f9fa;
        color: #333;
        font-weight: 600;
    }
    .content table tr:nth-child(even) {
        background: #fafafa;
    }
    .content table tr:hover {
        background: #f1f7ff;
    }

    /* Link hành động */
    .content a {
        color: #007bff;
        text-decoration: none;
    }
    .content a:hover {
        text-decoration: underline;
    }

</style>