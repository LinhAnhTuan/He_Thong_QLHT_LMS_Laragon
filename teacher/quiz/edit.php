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
$id = (int)($_GET['id'] ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);

// ✅ Lấy quiz + kiểm tra quyền
$stmt = $pdo->prepare("
    SELECT q.*, c.title as course_title, c.teacher_id 
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    WHERE q.id=? AND q.course_id=? AND c.teacher_id=?
");
$stmt->execute([$id, $course_id, $teacherId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die("⛔ Quiz không tồn tại hoặc bạn không có quyền.");
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
            // ✅ Cập nhật quiz
            $stmt = $pdo->prepare("UPDATE quizzes SET title=?, time_limit=?, max_score=? WHERE id=? AND course_id=?");
            $stmt->execute([$title, $time_limit, $max_score, $id, $course_id]);

            header("Location: list.php?course_id=$course_id");
            exit;
        } catch (PDOException $e) {
            $error = "❌ Lỗi khi cập nhật Quiz: " . $e->getMessage();
        }
    }
}
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>✏️ Sửa Quiz</title>
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
    <h2>✏️ Sửa Quiz trong khóa học: <?= htmlspecialchars($quiz['course_title']) ?></h2>
    
 
    <?php if ($error): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" class="form">
      <label>Tiêu đề Quiz:</label>
      <input type="text" name="title" required value="<?= htmlspecialchars($quiz['title']) ?>">

      <label>Giới hạn thời gian (phút):</label>
      <input type="number" name="time_limit" value="<?= $quiz['time_limit'] ?>" min="0">

      <label>Điểm tối đa:</label>
      <input type="number" name="max_score" value="<?= $quiz['max_score'] ?>" min="1">

      <button type="submit" class="btn">💾 Cập nhật</button> || <a href="list.php?course_id=<?= $course_id ?>" class="btn">⬅ Quay lại</a>
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