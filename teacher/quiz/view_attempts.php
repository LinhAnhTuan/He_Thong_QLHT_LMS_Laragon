<?php
require_once("../../config/db.php");
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php"); exit;
}

$teacherId = $_SESSION['user_id'];
$quiz_id = (int)($_GET['quiz_id'] ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);

// Kiểm tra quyền teacher với khóa học
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id=? AND teacher_id=?");
$stmt->execute([$course_id, $teacherId]);
$course = $stmt->fetch();
if (!$course) die("⛔ Bạn không có quyền quản lý khóa học này.");

// Lấy quiz
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id=? AND course_id=?");
$stmt->execute([$quiz_id, $course_id]);
$quiz = $stmt->fetch();
if (!$quiz) die("⛔ Quiz không tồn tại.");

// Lấy danh sách sinh viên đã làm quiz + số lần + điểm cao nhất + ngày nộp gần nhất
$stmtAttempts = $pdo->prepare("
    SELECT u.id AS student_id, u.name, 
           COUNT(a.id) AS times_taken, 
           MAX(a.score) AS max_score,
           MAX(a.submitted_at) AS last_submitted
    FROM quiz_attempts a
    JOIN users u ON a.student_id = u.id
    WHERE a.quiz_id = ?
    GROUP BY u.id
    ORDER BY u.name ASC
");
$stmtAttempts->execute([$quiz_id]);
$attempts = $stmtAttempts->fetchAll(PDO::FETCH_ASSOC);



$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>📊 Bài làm Quiz: <?= htmlspecialchars($quiz['title']) ?></title>
<link rel="stylesheet" href="../../style.css">
</head>
<body>
<?php include "../includes/sidebar_teacher2.php"; ?>

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
        <h2>📊 Bài làm Quiz: <?= htmlspecialchars($quiz['title']) ?></h2>
        <a href="list.php?course_id=<?= $course_id ?>" class="btn">🔙 Quay lại</a>

        <?php if ($attempts): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Họ tên</th>
                        <th>Số lần làm</th>
                        <th>Điểm cao nhất</th>
                        <th>Ngày nộp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $i => $a): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($a['name']) ?></td>
                        <td><?= $a['times_taken'] ?></td>
                        <td><?= round($a['max_score'], 0) ?></td>
                        <td><?= $a['last_submitted'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><i>Chưa có sinh viên nào làm Quiz này.</i></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<style>
.page-title{
    font-size: 22px;
}
.table { 
    width:100%; 
    border-collapse: collapse; 
    margin-top:10px; 
}
.table th, .table td { 
    border:1px solid #ccc; 
    padding:8px; 
    text-align:left; 
}
.table th { 
    background:#3498db; 
    color:#fff; 
}
.table tr:nth-child(even){ 
    background:#f9f9f9; 
}
.btn { 
    display:inline-block; 
    padding:6px 10px; 
    background:#27ae60; 
    color:#fff; 
    text-decoration:none; 
    border-radius:5px; 
    margin:2px; 
}
.btn:hover { 
    background:#1e8449; 
    }
</style>