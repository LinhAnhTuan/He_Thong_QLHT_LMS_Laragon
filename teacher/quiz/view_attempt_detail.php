<?php
require_once("../../config/db.php");
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php"); exit;
}

$teacherId = $_SESSION['user_id'];
$quiz_id = (int)($_GET['quiz_id'] ?? 0);
$student_id = (int)($_GET['student_id'] ?? 0);

// Kiểm tra quyền teacher
$stmt = $pdo->prepare("
    SELECT q.*, c.teacher_id
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    WHERE q.id=? AND c.teacher_id=?
");
$stmt->execute([$quiz_id, $teacherId]);
$quiz = $stmt->fetch();
if (!$quiz) die("⛔ Không có quyền xem quiz này.");

// Lấy tên sinh viên
$stmt = $pdo->prepare("SELECT name FROM users WHERE id=?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) die("⛔ Sinh viên không tồn tại.");

// Lấy tất cả lần làm của sinh viên
$stmt = $pdo->prepare("
    SELECT a.id AS attempt_id, a.score, a.submitted_at
    FROM quiz_attempts a
    WHERE a.quiz_id=? AND a.student_id=?
    ORDER BY a.submitted_at ASC
");
$stmt->execute([$quiz_id, $student_id]);
$attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Chi tiết bài làm của <?= htmlspecialchars($student['name']) ?></title>
<link rel="stylesheet" href="../../style.css">
<style>
.table { width:100%; border-collapse: collapse; margin-top:10px; }
.table th, .table td { border:1px solid #ccc; padding:8px; text-align:left; }
.table th { background:#3498db; color:#fff; }
.table tr:nth-child(even){ background:#f9f9f9; }
.btn { display:inline-block; padding:6px 10px; background:#27ae60; color:#fff; text-decoration:none; border-radius:5px; margin:2px; }
.btn:hover { background:#1e8449; }
</style>
</head>
<body>
    <div class="content">
      <header class="header">
        <div class="page-title"><b><?= htmlspecialchars($_SESSION['name']) ?></b></div>
        <div class="user">
        <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar" style="width:40px;height:40px;border-radius:50%">
        <span>Giảng viên</span>
        <a href="../../logout.php">🚪 Đăng xuất</a>
        </div>
    </header>
    <div class="main">
<h2>📄 Chi tiết bài làm của: <?= htmlspecialchars($student['name']) ?></h2>
<a href="view_attempts.php?quiz_id=<?= $quiz_id ?>&course_id=<?= $quiz['course_id'] ?>" class="btn">🔙 Quay lại</a>

<?php if ($attempts): ?>
    <table class="table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Ngày nộp</th>
                <th>Điểm</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($attempts as $i => $a): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= $a['submitted_at'] ?></td>
                <td><?= $a['score'] ?></td>
                <td>
                    <a href="view_answer_detail.php?attempt_id=<?= $a['attempt_id'] ?>" class="btn">👁 Xem câu trả lời</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p><i>Chưa có lần làm nào.</i></p>
<?php endif; ?>
</div>
    </div>
</body>
</html>
