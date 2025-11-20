<?php
session_start();
require_once("../../config/db.php");

// Chỉ admin được phép
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Lấy danh sách khóa học
$courses = $pdo->query("SELECT id, title FROM courses ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách giảng viên
$teachers = $pdo->query("
    SELECT id, name FROM users WHERE role='teacher' ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../../uploads/default.png";

// Xử lý khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $teacher_id = $_POST['teacher_id'];
    $weekday = $_POST['weekday'];
    $period = $_POST['period'];
    $meeting_link = $_POST['meeting_link'];
    $join_link = $_POST['join_link'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $stmt = $pdo->prepare("
        INSERT INTO schedules (course_id, teacher_id, weekday, period, meeting_link, join_link, start_time, end_time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$course_id, $teacher_id, $weekday, $period, $meeting_link, $join_link, $start_time, $end_time]);

    header("Location: list.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Thêm lịch học</title>
<link rel="stylesheet" href="../../style.css">
<style>
form {max-width: 600px; margin-top: 20px;}
input, select {
  width: 100%; padding: 10px; margin: 10px 0;
  border: 1px solid #ccc; border-radius: 6px;
}
button {
  padding: 10px 20px; background: #28a745;
  color: #fff; border: none; border-radius: 6px;
  cursor: pointer;
}
button:hover {opacity: 0.9;}
</style>
</head>

<body>
<?php include("../includes/sidebar2.php"); ?>

<div class="content">
  <header class="header">
    <div><b style="font-size: 25px;"><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
    <div class="user">
      <img src="<?php echo htmlspecialchars($avatar); ?>" 
           style="width:40px; height:40px; border-radius:50%;">
      <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
      <a href="../../logout.php">🚪 Đăng xuất</a>
    </div>
  </header>

  <main class="main">
    <h1>🗓️ Tạo lịch học</h1>

    <form method="post">

      <label>Khóa học:</label>
      <select name="course_id" required>
        <option value="">-- Chọn khóa học --</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Giảng viên:</label>
      <select name="teacher_id" required>
        <option value="">-- Chọn giảng viên --</option>
        <?php foreach ($teachers as $t): ?>
          <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Ngày học (Thứ):</label>
      <select name="weekday" required>
        <option value="Mon">Thứ 2</option>
        <option value="Tue">Thứ 3</option>
        <option value="Wed">Thứ 4</option>
        <option value="Thu">Thứ 5</option>
        <option value="Fri">Thứ 6</option>
        <option value="Sat">Thứ 7</option>
        <option value="Sun">Chủ nhật</option>
      </select>

      <label>Tiết học:</label>
      <input type="text" name="period" placeholder="Ví dụ: Tiết 1-3" required>

      <label>Link giảng viên (host link):</label>
      <input type="text" name="meeting_link" placeholder="Link Zoom/Meet cho giảng viên">

      <label>Link tham gia (join link):</label>
      <input type="text" name="join_link" placeholder="Link dành cho học viên" required>

      <label>Thời gian bắt đầu:</label>
      <input type="datetime-local" name="start_time" required>

      <label>Thời gian kết thúc:</label>
      <input type="datetime-local" name="end_time" required>

      <button type="submit">✔️ Tạo lịch học</button>
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