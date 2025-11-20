<?php
// teacher/quiz/list.php
require_once("../../config/db.php");
session_start();

// ✅ Chỉ teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = $_SESSION['user_id'];

// Lấy tất cả khóa học của teacher
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id=? ORDER BY created_at DESC");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Khóa học được chọn
$course_id = (int)($_GET['course_id'] ?? 0);
$quizzes = [];

if ($course_id > 0) {
    // Kiểm tra khóa học thuộc về teacher
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id=? AND teacher_id=?");
    $stmt->execute([$course_id, $teacherId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) die("⛔ Bạn không có quyền quản lý khóa học này.");

    // Lấy Quiz
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE course_id=? ORDER BY id ASC");
    $stmt->execute([$course_id]);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ✅ Lấy danh sách mẫu quiz do admin thêm
$stmtTemp = $pdo->query("SELECT * FROM quiz_templates ORDER BY created_at DESC");
$templates = $stmtTemp->fetchAll(PDO::FETCH_ASSOC);

$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>📝 Quản lý Quiz</title>
  <link rel="stylesheet" href="../../style.css">
</head>
<body>
<?php include "../includes/sidebar_teacher2.php"; ?>

<div class="content">
  <header class="header">
    <div class="page-title"><?= htmlspecialchars($_SESSION['name']) ?></div>
    <div class="user">
      <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar" style="width:40px;height:40px;border-radius:50%">
      <span>Giảng viên</span>
      <a href="../../logout.php">🚪 Đăng xuất</a>
    </div>
  </header>

  <div class="main">
    <h2>📝 Quản lý Quiz</h2>

    <form method="get">
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
      <h3>📚 Khóa học: <?= htmlspecialchars($course['title']) ?></h3>

      <div class="sample-section">
        <label><b>📄 Lấy mẫu quiz:</b></label><br>
        <?php if (count($templates) > 0): ?>
          <?php foreach ($templates as $t): ?>
            <a href="../../<?= htmlspecialchars($t['file_path']) ?>" class="btn" download>
              📎 <?= htmlspecialchars($t['name']) ?>
            </a><br>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:gray;">(Chưa có mẫu nào được thêm bởi admin)</p>
        <?php endif; ?>
      </div>

      <a href="add.php?course_id=<?= $course_id ?>" class="btn">➕ Thêm Quiz</a>

      <?php if (!$quizzes): ?>
        <p><i>Chưa có Quiz nào</i></p>
      <?php else: ?>
        <?php foreach ($quizzes as $q): ?>
          <div class="quiz-box">
            <h3>📝 <?= htmlspecialchars($q['title']) ?></h3>
            <p>
              <b>⏱ Thời gian:</b> <?= $q['time_limit'] ?> phút |
              <b>Điểm tối đa:</b> <?= $q['max_score'] ?>
            </p>
            <p>
              <a href="import.php?quiz_id=<?= $q['id'] ?>" class="btn">📥 Import câu hỏi</a>
              <a href="edit.php?id=<?= $q['id'] ?>&course_id=<?= $course_id ?>" class="btn">✏️ Sửa</a>
              <a href="delete.php?id=<?= $q['id'] ?>&course_id=<?= $course_id ?>" class="btn btn-del"
                 onclick="return confirm('Xóa quiz này?')">🗑️ Xóa</a>
            </p>

            <?php if (!empty($q['import_file'])): ?>
              <p>📂 <b>Quiz đã thêm:</b> 
                <a href="../../<?= htmlspecialchars($q['import_file']) ?>" download>
                  <?= basename($q['import_file']) ?>
                </a>
                <p>
                    <a href="view_attempts.php?quiz_id=<?= $q['id'] ?>&course_id=<?= $course_id ?>" class="btn">👁 Xem bài làm</a>
                </p>

              </p>
            <?php else: ?>
              <p><i>Chưa import câu hỏi</i></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
</body>
</html>

<style>
body {
  background: #f8f9fa;
  font-family: Arial, sans-serif;
}
.page-title {
  font-size: 22px;
  font-weight: bold;
}
.main {
  background: white;
  padding: 20px;
  border-radius: 10px;
  margin-top: 15px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
select {
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
  margin-top: 5px;
}
.btn {
  display: inline-block;
  padding: 8px 12px;
  background: #27ae60;
  color: #fff;
  text-decoration: none;
  border-radius: 5px;
  margin: 4px 2px;
  transition: 0.2s;
}
.btn:hover {
  background: #1e8449;
}
.btn[download] {
  background: #2980b9;
}
.btn[download]:hover {
  background: #1f6391;
}
.quiz-box {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  margin-top: 15px;
  background: #fafafa;
}
.quiz-box h3 {
  margin: 0 0 5px 0;
  color: #2c3e50;
}
.quiz-box p {
  margin: 4px 0;
}
.sample-section {
  background: #eef5ff;
  padding: 10px;
  border-radius: 8px;
  margin: 15px 0;
  border: 1px solid #d6e4ff;
}
.sample-section b {
  color: #2f80ed;
}
.btn-del {
  background: #e74c3c;
}
.btn-del:hover {
  background: #c0392b;
}
</style>
