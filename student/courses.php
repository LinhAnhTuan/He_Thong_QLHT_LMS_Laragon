<?php
require_once("../config/db.php");
session_start();

if ($_SESSION['role'] !== 'student') { header("Location: ../../login.php"); exit; }
$studentId = $_SESSION['user_id'];

// Lấy danh sách khóa học đã đăng ký
$stmt = $pdo->prepare("
    SELECT c.id, c.title, c.class_name, u.name as teacher, e.progress
    FROM course_enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.teacher_id = u.id
    WHERE e.student_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$studentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../uploads/default.png";


?>

<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>📚 Khóa học của tôi</title>
        <link rel="stylesheet" href="../style.css">
    </head>
<body>
<?php include "includes/sidebar.php"; ?>
<div class="content">
    <header class="header">
        <div class="page-title"><b><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
            <div class="user">
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
            <span>Sinh viên</span>
            <a href="../logout.php">🚪 Đăng xuất</a>
        </div>
  </header>
<div class="main">
  <h2>📚 Khóa học đã đăng ký</h2>
  <?php if (!$courses): ?>
    <p><i>Bạn chưa đăng ký khóa học nào.</i></p>
  <?php else: ?>
    <table class="table-student">
      <tr>
        <th>Mã lớp</th>
        <th>Tên khóa học</th>
        <th>Giảng viên</th>
        <th>Tiến độ</th>
        <th>Chi tiết</th>
      </tr>
      <?php foreach ($courses as $c): ?>
        <?php
        // Lấy tổng số phần
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM lessons l JOIN sections s ON l.section_id=s.id WHERE s.course_id=?) +
                (SELECT COUNT(*) FROM assignments WHERE course_id=?) +
                (SELECT COUNT(*) FROM quizzes WHERE course_id=?) AS total_parts
        ");
        $stmt->execute([$c['id'],$c['id'],$c['id']]);
        $total_parts = (int)$stmt->fetchColumn();

        // Lấy số phần đã hoàn thành
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
        $stmt->execute([$studentId,$c['id'],$studentId,$c['id'],$studentId,$c['id']]);
        $completed_parts = (int)$stmt->fetchColumn();

        $progress = $total_parts > 0 ? round(($completed_parts/$total_parts)*100, 2) : 0;
        ?>
        <tr>
            <td><?= htmlspecialchars($c['class_name']) ?></td>
            <td><?= htmlspecialchars($c['title']) ?></td>
            <td><?= htmlspecialchars($c['teacher']) ?></td>
            <td><?= $progress ?>%</td>
            <td><a href="courses_detail.php?id=<?= $c['id'] ?>">🔎 Xem</a></td>
        </tr>
    <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
</div>
</body>
</html>

<style>
    .page-title {
    font-size: 22px;
    }
    /* ==== BẢNG KHÓA HỌC SINH VIÊN ==== */
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

.table-student tr:nth-child(even) {
  background: #f9f9f9;
}

.table-student tr:hover {
  background: #ecf7ff;
  transition: 0.25s;
}

/* Link Chi tiết */
.table-student a {
  background: #27ae60;
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 13px;
  font-weight: bold;
}

.table-student a:hover {
  background: #1e8449;
}

</style>