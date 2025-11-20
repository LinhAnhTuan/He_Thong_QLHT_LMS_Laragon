<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacherId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT s.*, c.title AS course_name
    FROM schedules s
    JOIN courses c ON s.course_id = c.id
    WHERE s.teacher_id = ?
    ORDER BY s.weekday, s.start_time
");
$stmt->execute([$teacherId]);
$schedules = $stmt->fetchAll();

$weekdayText = [
    "Mon"=>"Thứ 2","Tue"=>"Thứ 3","Wed"=>"Thứ 4",
    "Thu"=>"Thứ 5","Fri"=>"Thứ 6","Sat"=>"Thứ 7","Sun"=>"Chủ nhật"
];

$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Lịch giảng dạy</title>
<link rel="stylesheet" href="../../style.css">
</head>

<body>
<?php include("../includes/sidebar_teacher2.php"); ?>
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
    <h1>📅 Lịch giảng dạy</h1>

    <table class="table">
    <tr>
    <th>Khóa học</th>
    <th>Ngày</th>
    <th>Tiết</th>
    <th>Link giảng viên</th>
    <th>Thời gian bắt đầu khóa học</th>
    </tr>

    <?php foreach ($schedules as $s): ?>
    <tr>
    <td><?= $s['course_name'] ?></td>
    <td><?= $weekdayText[$s['weekday']] ?></td>
    <td><?= $s['period'] ?></td>
    <td><a href="<?= $s['meeting_link'] ?>" target="_blank">Vào lớp</a></td>
    <td><?= date("H:i d/m/Y", strtotime($s['start_time'])) ?></td>
    </tr>
    <?php endforeach; ?>

    </table>
  </div>
</div>

</body>
</html>
<style>
table {width:100%; border-collapse: collapse; margin-top:20px;}
th, td {border:1px solid #ddd; padding:10px; text-align:center;}
th {background:#2c3e50; color:#fff;}
a.btn {padding:6px 12px; background:#007bff; color:#fff; border-radius:6px; text-decoration:none;}
</style>