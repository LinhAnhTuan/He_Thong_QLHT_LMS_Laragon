<?php
session_start();
require_once("../../config/db.php");

// Chỉ student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../login.php");
    exit;
}

$studentId = $_SESSION['user_id'];

// Lấy lịch học các khóa mà học viên đã đăng ký
$stmt = $pdo->prepare("
    SELECT s.*, c.title AS course_name, u.name AS teacher_name
    FROM schedules s
    JOIN course_enrollments ce ON s.course_id = ce.course_id
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN users u ON s.teacher_id = u.id
    WHERE ce.student_id = ?
    ORDER BY s.weekday, s.start_time
");
$stmt->execute([$studentId]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hiển thị tên ngày
$weekdayText = [
    "Mon"=>"Thứ 2","Tue"=>"Thứ 3","Wed"=>"Thứ 4",
    "Thu"=>"Thứ 5","Fri"=>"Thứ 6","Sat"=>"Thứ 7","Sun"=>"Chủ nhật"
];

$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Lịch học của tôi</title>
<link rel="stylesheet" href="../../style.css">
<style>
table {width:100%; border-collapse: collapse; margin-top:20px;}
th, td {border:1px solid #ddd; padding:10px; text-align:center;}
th {background:#2c3e50; color:#fff;}
a.btn {padding:6px 12px; background:#007bff; color:#fff; border-radius:6px; text-decoration:none;}
</style>
</head>

<body>
<?php include("../includes/sidebar2.php"); ?>

<div class="content">
<header class="header">
  <div><b style="font-size:25px;"><?= $_SESSION['name'] ?></b></div>
  <div class="user">
    <img src="<?= $avatar ?>" style="width:40px;height:40px;border-radius:50%;">
    <span><?= $_SESSION['name'] ?></span>
    <a href="../../logout.php">🚪 Đăng xuất</a>
  </div>
</header>

<main class="main">
<h1>📅 Lịch học của tôi</h1>

<?php if (empty($schedules)): ?>
<p>Chưa có lịch học nào.</p>
<?php else: ?>
<table>
<tr>
<th>Khóa học</th>
<th>Giảng viên</th>
<th>Ngày</th>
<th>Tiết</th>
<th>Link tham gia</th>
<th>Thời gian</th>
</tr>

<?php foreach ($schedules as $s): ?>
<tr>
<td><?= htmlspecialchars($s['course_name']) ?></td>
<td><?= htmlspecialchars($s['teacher_name']) ?></td>
<td><?= $weekdayText[$s['weekday']] ?></td>
<td><?= htmlspecialchars($s['period']) ?></td>
<td>
<?php if($s['join_link']): ?>
<a href="<?= $s['join_link'] ?>" target="_blank" class="btn">Vào lớp</a>
<?php endif; ?>
</td>
<td>
<?= date("H:i d/m/Y", strtotime($s['start_time'])) ?> → 
<?= date("H:i d/m/Y", strtotime($s['end_time'])) ?>
</td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>

</main>
</div>
</body>
</html>
