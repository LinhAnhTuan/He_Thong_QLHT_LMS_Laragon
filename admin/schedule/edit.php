<?php
session_start();
require_once("../../config/db.php");

// Chỉ admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

// Lấy lịch học
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->execute([$id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) { die("Không tìm thấy lịch học!"); }

// Danh sách khóa học
$courses = $pdo->query("SELECT id, title FROM courses")->fetchAll();

// Danh sách giảng viên
$teachers = $pdo->query("SELECT id, name FROM users WHERE role='teacher'")->fetchAll();

$weekdayText = [
    "Mon" => "Thứ 2", "Tue" => "Thứ 3", "Wed" => "Thứ 4",
    "Thu" => "Thứ 5", "Fri" => "Thứ 6", "Sat" => "Thứ 7", "Sun" => "Chủ nhật"
];

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../../uploads/default.png";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE schedules SET 
            course_id=?, teacher_id=?, weekday=?, period=?, 
            meeting_link=?, join_link=?, start_time=?, end_time=?
        WHERE id=?
    ");

    $stmt->execute([
        $_POST['course_id'], $_POST['teacher_id'], $_POST['weekday'],
        $_POST['period'], $_POST['meeting_link'], $_POST['join_link'],
        $_POST['start_time'], $_POST['end_time'], $id
    ]);

    header("Location: list.php?updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Sửa lịch học</title>
<link rel="stylesheet" href="../../style.css">
<style>
form {max-width: 600px; margin-top: 20px;}
input, select {
  width: 100%; padding: 10px; margin: 10px 0;
  border: 1px solid #ccc; border-radius: 6px;
}
button {
  padding: 10px 20px; background:#007bff; color:#fff;
  border:none; border-radius:6px; cursor:pointer;
}
</style>
</head>

<body>
<?php include("../includes/sidebar2.php"); ?>

<div class="content">
<header class="header">
  <b style="font-size: 25px;"><?= $_SESSION['name'] ?></b>
  <div class="user">
    <img src="<?= $avatar ?>" style="width:40px;height:40px;border-radius:50%;">
    <span><?= $_SESSION['name'] ?></span>
    <a href="../../logout.php">🚪 Đăng xuất</a>
  </div>
</header>

<main class="main">
<h1>✏️ Sửa lịch học</h1>

<form method="post">
    <label>Khóa học:</label>
    <select name="course_id" required>
        <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']==$schedule['course_id']?'selected':'' ?>>
                <?= htmlspecialchars($c['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Giảng viên:</label>
    <select name="teacher_id" required>
        <?php foreach ($teachers as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $t['id']==$schedule['teacher_id']?'selected':'' ?>>
                <?= htmlspecialchars($t['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Ngày học:</label>
    <select name="weekday" required>
        <?php foreach ($weekdayText as $k => $v): ?>
            <option value="<?= $k ?>" <?= ($k==$schedule['weekday'])?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
    </select>

    <label>Tiết học:</label>
    <input type="text" name="period" value="<?= $schedule['period'] ?>">

    <label>Link giảng viên:</label>
    <input type="text" name="meeting_link" value="<?= $schedule['meeting_link'] ?>">

    <label>Link tham gia:</label>
    <input type="text" name="join_link" value="<?= $schedule['join_link'] ?>">

    <label>Thời gian bắt đầu:</label>
    <input type="datetime-local" name="start_time"
    value="<?= date('Y-m-d\TH:i', strtotime($schedule['start_time'])) ?>">

    <label>Thời gian kết thúc:</label>
    <input type="datetime-local" name="end_time"
    value="<?= date('Y-m-d\TH:i', strtotime($schedule['end_time'])) ?>">

    <button type="submit">Lưu thay đổi</button>
</form>

</main>
</div>
</body>
</html>
<style>
    a.back-btn {
      display: inline-block;
      margin-left: 50px;
      margin-top: 10px;
      padding: 10px 18px;
      background: #007bff;
      color: #fff;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      transition: background 0.2s;
  }

  a.back-btn:hover {
      background: #0056b3;
  }
</style>