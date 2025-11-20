<?php
session_start();
require_once("../../config/db.php");

// Chỉ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../../uploads/default.png";

// Lấy lịch học
$stmt = $pdo->query("
    SELECT s.*, c.title AS course_name, u.name AS teacher_name
    FROM schedules s
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN users u ON s.teacher_id = u.id
    ORDER BY s.weekday, s.start_time
");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$weekdayText = [
    "Mon" => "Thứ 2", "Tue" => "Thứ 3", "Wed" => "Thứ 4",
    "Thu" => "Thứ 5", "Fri" => "Thứ 6", "Sat" => "Thứ 7", "Sun" => "Chủ nhật"
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Danh sách lịch học</title>
<link rel="stylesheet" href="../../style.css">
<style>
table {width:100%; border-collapse: collapse; margin-top:20px;}
th, td {border:1px solid #ddd; padding:10px; text-align:center;}
th {background:#2c3e50; color:#fff;}
.btn {padding:8px 12px; background:#28a745; color:#fff; border-radius:6px; text-decoration:none;}
</style>
</head>

<body>
<?php include("../includes/sidebar2.php"); ?>

<div class="content">
<header class="header">
  <div><b style="font-size: 25px;"><?php echo $_SESSION['name']; ?></b></div>
  <div class="user">
    <img src="<?= $avatar ?>" style="width:40px;height:40px;border-radius:50%;">
    <span><?= $_SESSION['name'] ?></span>
    <a href="../../logout.php">🚪 Đăng xuất</a>
  </div>
</header>

<main class="main">
<h1>🗓️ Danh sách lịch học</h1>

<a href="add.php" class="btn">➕ Tạo lịch học</a> 


<table>
<tr>
<th>ID</th>
<th>Khóa học</th>
<th>Giảng viên</th>
<th>Ngày</th>
<th>Tiết</th>
<th>Link tham gia</th>
<th>Thời gian</th>
<th>Hành động</th>
</tr>

<?php foreach ($schedules as $s): ?>
<tr>
<td><?= $s['id'] ?></td>
<td><?= htmlspecialchars($s['course_name']) ?></td>
<td><?= htmlspecialchars($s['teacher_name']) ?></td>
<td><?= $weekdayText[$s['weekday']] ?></td>
<td><?= htmlspecialchars($s['period']) ?></td>
<td>
    <?php if ($s['join_link']): ?>
        <a href="<?= $s['join_link'] ?>" target="_blank">Link</a>
    <?php endif; ?>
</td>
<td>
<?= date("H:i d/m/Y", strtotime($s['start_time'])) ?> →  
<?= date("H:i d/m/Y", strtotime($s['end_time'])) ?>
</td>
<td>
<a href="edit.php?id=<?= $s['id'] ?>">✏️ Sửa</a> | 
<a href="delete.php?id=<?= $s['id'] ?>" onclick="return confirm('Xóa lịch học này?')">🗑️ Xóa</a>
</td>
</tr>
<?php endforeach; ?>

</table>
</main>
</div>
</body>
</html>
