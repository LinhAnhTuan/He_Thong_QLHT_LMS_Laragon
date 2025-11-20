<?php
require_once("../../config/db.php");
session_start();

if ($_SESSION['role'] !== 'student') { header("Location: ../login.php"); exit; }
$studentId = $_SESSION['user_id'];

// Lấy danh sách khóa học đã đăng ký
$stmt = $pdo->prepare("
    SELECT c.id, c.title, c.class_name, u.name as teacher
    FROM course_enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.teacher_id = u.id
    WHERE e.student_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$studentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$course_id = (int)($_GET['course_id'] ?? 0);
$quizzes = [];
$attempts = [];

if ($course_id) {
    // Kiểm tra sinh viên có thực sự đăng ký khóa học này không
    $stmt = $pdo->prepare("SELECT 1 FROM course_enrollments WHERE course_id=? AND student_id=?");
    $stmt->execute([$course_id, $studentId]);
    $enrolled = $stmt->fetch();

    if ($enrolled) {
        // Lấy quiz trong khóa học
        $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE course_id=? ORDER BY id DESC");
        $stmt->execute([$course_id]);
        $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy lịch sử làm quiz của sinh viên
        $stmt = $pdo->prepare("SELECT quiz_id, score FROM quiz_attempts WHERE student_id=?");
        $stmt->execute([$studentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $attempts[$r['quiz_id']] = $r['score'];
        }
    }
}

// Avatar sinh viên
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>📑 Quiz của tôi</title>
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
  <h2>📑 Quiz của tôi</h2>

  <!-- Chọn khóa học -->
  <form method="get" class="select-course">
    <label>Chọn khóa học:</label>
    <select name="course_id" onchange="this.form.submit()">
      <option value="">-- Chọn --</option>
      <?php foreach ($courses as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $c['id']==$course_id?'selected':'' ?>>
          <?= htmlspecialchars($c['class_name']." - ".$c['title']." (".$c['teacher'].")") ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <!-- Danh sách quiz -->
  <?php if ($course_id): ?>
    <?php if (!$quizzes): ?>
      <p><i>Khóa học này chưa có quiz nào.</i></p>
    <?php else: ?>
      <div class="quiz-list">
        <?php foreach ($quizzes as $q): ?>
          <div class="quiz-card">
            <h3><?= htmlspecialchars($q['title']) ?></h3>
            <p>⏱ Thời gian: <?= $q['time_limit'] ? $q['time_limit']." phút" : "Không giới hạn" ?></p>
            <p>🏆 Điểm tối đa: <?= $q['max_score'] ?></p>

            <?php if (isset($attempts[$q['id']])): ?>
              <p class="quiz-status done">✅ Đã làm (<?= $attempts[$q['id']] ?>/<?= $q['max_score'] ?> điểm)</p>
              <a href="quiz_result.php?id=<?= $q['id'] ?>">📊 Xem kết quả</a>
            <?php else: ?>
              <p class="quiz-status notdone">⏳ Chưa làm</p>
              <a href="quiz_take.php?id=<?= $q['id'] ?>">👉 Bắt đầu làm</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
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
    .quiz-list {margin-top:20px;}
    .quiz-card {
      background:#fff; padding:15px; border-radius:8px; 
      box-shadow:0 2px 6px rgba(0,0,0,0.1);
      margin-bottom:15px;
    }
    .quiz-card h3 {margin:0; color:#2c3e50;}
    .quiz-card p {margin:6px 0;}
    .quiz-card a {
      display:inline-block; background:#27ae60; color:#fff;
      padding:8px 14px; border-radius:6px; text-decoration:none; font-weight:bold;
    }
    .quiz-card a:hover {background:#1e8449;}
    .quiz-status {margin-top:8px; font-weight:bold;}
    .done {color:green;}
    .notdone {color:#c0392b;}
    .select-course {margin:15px 0;}
    .select-course select {
      padding:8px 12px;
      border-radius:6px;
      border:1px solid #ccc;
      font-size:14px;
    }
  </style>