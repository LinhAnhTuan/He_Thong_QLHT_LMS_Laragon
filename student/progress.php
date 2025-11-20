<?php
require_once("../config/db.php");
session_start();

if ($_SESSION['role'] !== 'student') { 
    header("Location: ../login.php"); 
    exit; 
}
$studentId = $_SESSION['user_id'];

// Lấy danh sách khóa học mà SV đã đăng ký
$stmt = $pdo->prepare("
    SELECT c.id, c.title, c.class_name
    FROM course_enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id=?
");
$stmt->execute([$studentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Khóa học đang chọn
$course_id = (int)($_GET['course_id'] ?? 0);
$progress = null;
$lessons = [];
$tasks = [];
$quizzes = [];

if ($course_id) {
    // Kiểm tra quyền
    $stmt = $pdo->prepare("SELECT 1 FROM course_enrollments WHERE course_id=? AND student_id=?");
    $stmt->execute([$course_id, $studentId]);
    if ($stmt->fetch()) {
        // Tổng số phần (bài học + bài tập + quiz)
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM lessons l JOIN sections s ON l.section_id=s.id WHERE s.course_id=?) +
                (SELECT COUNT(*) FROM assignments WHERE course_id=?) +
                (SELECT COUNT(*) FROM quizzes WHERE course_id=?) AS total_parts
        ");
        $stmt->execute([$course_id,$course_id,$course_id]);
        $total_parts = (int)$stmt->fetchColumn();

        // Số phần hoàn thành
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM lesson_progress lp 
                 JOIN lessons l ON lp.lesson_id=l.id
                 JOIN sections s ON l.section_id=s.id
                 WHERE lp.student_id=? AND s.course_id=? AND lp.is_completed=1) +
                (SELECT COUNT(*) FROM assignment_submissions s
                 JOIN assignments a ON s.assignment_id=a.id
                 WHERE s.student_id=? AND a.course_id=?) +
                (SELECT COUNT(*) FROM quiz_attempts qa
                 JOIN quizzes q ON qa.quiz_id=q.id
                 WHERE qa.student_id=? AND q.course_id=?) AS completed_parts
        ");
        $stmt->execute([$studentId,$course_id,$studentId,$course_id,$studentId,$course_id]);
        $completed_parts = (int)$stmt->fetchColumn();

        // Tính %
        $progress = $total_parts>0 ? round(($completed_parts/$total_parts)*100,2) : 0;

        // Lấy chi tiết bài học
        $stmt = $pdo->prepare("
            SELECT l.title, lp.is_completed, lp.completed_at
            FROM lessons l
            JOIN sections s ON l.section_id=s.id
            LEFT JOIN lesson_progress lp 
                   ON lp.lesson_id=l.id AND lp.student_id=?
            WHERE s.course_id=?
            ORDER BY s.order_number, l.order_number
        ");
        $stmt->execute([$studentId, $course_id]);
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy chi tiết bài tập
        $stmt = $pdo->prepare("
            SELECT a.title, s.grade, s.submitted_at 
            FROM assignments a
            LEFT JOIN assignment_submissions s 
              ON a.id = s.assignment_id AND s.student_id=?
            WHERE a.course_id=?
        ");
        $stmt->execute([$studentId, $course_id]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy chi tiết quiz
        $stmt = $pdo->prepare("
            SELECT q.title, MAX(qa.score) as best_score, COUNT(qa.id) as attempts
            FROM quizzes q
            LEFT JOIN quiz_attempts qa 
              ON qa.quiz_id=q.id AND qa.student_id=?
            WHERE q.course_id=?
            GROUP BY q.id
        ");
        $stmt->execute([$studentId, $course_id]);
        $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>🎯 Tiến độ học tập</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
<?php include "includes/sidebar.php"; ?>
<div class="content">
  <header class="header">
      <div class="page-title"><b><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
      <div class="user">
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px;height:40px;border-radius:50%">
        <span>Sinh viên</span>
        <a href="../logout.php">🚪 Đăng xuất</a>
      </div>
  </header>
<div class="main">
  <h2>🎯 Tiến độ học tập</h2>

  <form method="get">
    <label>Chọn khóa học:</label>
    <select name="course_id" onchange="this.form.submit()">
      <option value="">-- Chọn --</option>
      <?php foreach ($courses as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $c['id']==$course_id?'selected':'' ?>>
          <?= htmlspecialchars($c['class_name']." - ".$c['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($progress !== null): ?>
    <h3>Tiến độ tổng thể: <?= $progress ?>%</h3>
    <div class="progress-bar">
      <div class="progress-fill" style="width:<?= $progress ?>%"></div>
    </div>

    <h3>📘 Bài học</h3>
    <table class="table-student">
      <tr><th>Bài học</th><th>Hoàn thành</th><th>Thời gian</th></tr>
      <?php foreach ($lessons as $l): ?>
        <tr>
          <td><?= htmlspecialchars($l['title']) ?></td>
          <td><?= $l['is_completed'] ? "✅" : "❌" ?></td>
          <td><?= $l['completed_at'] ?: "-" ?></td>
        </tr>
      <?php endforeach; ?>
    </table>

    <h3>📂 Bài tập</h3>
    <table class="table-student">
      <tr><th>Tên</th><th>Ngày nộp</th><th>Điểm</th></tr>
      <?php foreach ($tasks as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['title']) ?></td>
          <td><?= $t['submitted_at'] ?: "-" ?></td>
          <td><?= $t['grade'] !== null ? $t['grade'] : "-" ?></td>
        </tr>
      <?php endforeach; ?>
    </table>

    <h3>📝 Quiz</h3>
    <table class="table-student">
      <tr><th>Tên Quiz</th><th>Lượt làm</th><th>Điểm cao nhất</th></tr>
      <?php foreach ($quizzes as $q): ?>
        <tr>
          <td><?= htmlspecialchars($q['title']) ?></td>
          <td><?= $q['attempts'] ?></td>
          <td><?= $q['best_score'] !== null ? $q['best_score'] : "-" ?></td>
        </tr>
      <?php endforeach; ?>
    </table>

  <?php elseif ($course_id): ?>
    <p><i>Bạn chưa có dữ liệu tiến độ cho khóa học này.</i></p>
  <?php endif; ?>
</div>
</div>
</body>
</html>
<style>
  .page-title{
    font-size: 22px;
  }
    .progress-bar {
      background: #eee;
      border-radius: 8px;
      overflow: hidden;
      width: 60%;
      margin: 10px 0 20px;
      height: 20px;
      box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #27ae60, #2ecc71);
      transition: width 0.5s ease-in-out;
    }
    .table-student {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .table-student th, 
    .table-student td {
      padding: 14px 16px;
      text-align: center;
      border-bottom: 1px solid #eee;
      font-size: 14px;
    }
    .table-student th {
      background: #2980b9;
      color: white;
      text-transform: uppercase;
      font-size: 13px;
      letter-spacing: 0.5px;
    }
    .table-student tr:nth-child(even) { background: #f9f9f9; }
    .table-student tr:hover { background: #ecf7ff; transition: 0.25s; }
  </style>