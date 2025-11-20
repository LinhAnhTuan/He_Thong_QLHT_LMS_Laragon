<?php
require_once("../../config/db.php");
require_once("../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

session_start();

// ✅ Kiểm tra quyền teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = (int)$_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

// ✅ Kiểm tra khóa học có thuộc giáo viên không
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id=? AND teacher_id=?");
$stmt->execute([$course_id, $teacherId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$course) {
    die("⛔ Khóa học không tồn tại hoặc bạn không có quyền.");
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? "");
    $time_limit = (int)($_POST['time_limit'] ?? 0);
    $max_score = (int)($_POST['max_score'] ?? 10);

    if ($title === "") {
        $error = "⚠️ Vui lòng nhập tiêu đề Quiz.";
    } else {
        try {
            // ✅ Thêm quiz mới
            $stmt = $pdo->prepare("INSERT INTO quizzes (course_id, title, time_limit, max_score) VALUES (?, ?, ?, ?)");
            $stmt->execute([$course_id, $title, $time_limit, $max_score]);

            // ✅ Quay lại trang danh sách quiz
            header("Location: list.php?course_id=$course_id");
            exit;
        } catch (PDOException $e) {
            $error = "❌ Lỗi khi thêm Quiz: " . $e->getMessage();
        }
    }
}
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>➕ Thêm Quiz</title>
  <link rel="stylesheet" href="../../style.css">
</head>
<body>
<?php include "../includes/sidebar_teacher2.php"; ?>

<div class="content">
  <header class="header">
        <div class="page-title"><b><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
        <div class="user">
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
        <span>Giảng viên</span>
        <a href="../../logout.php">🚪 Đăng xuất</a>
        </div>
    </header>

  <main class="main">
    <h2>➕ Thêm Quiz cho khóa học: <?= htmlspecialchars($course['title']) ?></h2>
    
    <?php if ($error): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" class="form">
      <label>Tiêu đề Quiz:</label>
      <input type="text" name="title" required placeholder="Nhập tên Quiz">

      <label>Giới hạn thời gian (phút):</label>
      <input type="number" name="time_limit" value="0" min="0">

      <label>Điểm tối đa:</label>
      <input type="number" name="max_score" value="10" min="1">

      <button type="submit" class="btn">💾 Lưu Quiz</button> || <a href="list.php?course_id=<?= $course_id ?>" class="btn">⬅ Quay lại</a>
    </form>
  </main>
</div>
</body>
</html>
<style>
  .page-title{
      font-size: 22px;
    }
</style>