<?php
// teacher/lessons/edit_lesson.php
require_once("../../config/db.php");

session_start();

// ✅ Kiểm tra quyền teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = (int)$_SESSION['user_id'];
$lesson_id = (int)($_GET['id'] ?? 0);

// ✅ Lấy thông tin bài học + section + course
$sql = "
    SELECT l.*, s.course_id, c.teacher_id, c.title AS course_title
    FROM lessons l
    JOIN sections s ON l.section_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE l.id=? AND c.teacher_id=?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$lesson_id, $teacherId]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    die("⛔ Bài học không tồn tại hoặc bạn không có quyền.");
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? "");
    $content_type = $_POST['content_type'] ?? "text";
    $order_number = (int)($_POST['order_number'] ?? 0);
    $content_link = "";

    // ✅ Upload file nếu có
    if (!empty($_FILES['content_file']['name'])) {
        $uploadDir = "../../uploads/lessons/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = time() . "_" . basename($_FILES['content_file']['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['content_file']['tmp_name'], $targetPath)) {
            $content_link = "uploads/lessons/" . $filename;
        } else {
            $error = "❌ Upload file thất bại.";
        }
    } else {
        $content_link = trim($_POST['content_link'] ?? $lesson['content_link']);
    }

    if ($title === "") {
        $error = "⚠️ Vui lòng nhập tiêu đề.";
    }

    if (!$error) {
        $stmt = $pdo->prepare("UPDATE lessons SET title=?, content_type=?, content_link=?, order_number=? WHERE id=?");
        $stmt->execute([$title, $content_type, $content_link, $order_number, $lesson_id]);

        header("Location: list.php?course_id=" . $lesson['course_id']);
        exit;
    }
}
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>✏️ Sửa bài học</title>
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

  <h2>✏️ Sửa bài học trong khóa học: <?= htmlspecialchars($lesson['course_title']) ?></h2>
  

  <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="form">
    <label>Tiêu đề:</label>
    <input type="text" name="title" value="<?= htmlspecialchars($lesson['title']) ?>" required>

    <label>Loại nội dung:</label>
    <select name="content_type">
      <option value="video" <?= $lesson['content_type']=='video'?'selected':'' ?>>Video</option>
      <option value="pdf" <?= $lesson['content_type']=='pdf'?'selected':'' ?>>PDF</option>
      <option value="text" <?= $lesson['content_type']=='text'?'selected':'' ?>>Text</option>
    </select>

    <label>Liên kết / File hiện tại:</label>
    <?php if ($lesson['content_link']): ?>
      <p>Hiện tại: <a href="../../<?= htmlspecialchars($lesson['content_link']) ?>" target="_blank">📂 Xem nội dung</a></p>
    <?php endif; ?>
    <input type="text" name="content_link" value="<?= htmlspecialchars($lesson['content_link']) ?>" placeholder="Hoặc nhập link">
    <input type="file" name="content_file">

    <label>Thứ tự hiển thị (STT):</label>
    <input type="number" name="order_number" value="<?= $lesson['order_number'] ?>">

    <button type="submit" class="btn">💾 Cập nhật</button>
     <a href="list.php?course_id=<?= $lesson['course_id'] ?>" class="btn">⬅ Quay lại</a>
  </form>
</div>
</body>
</html>
<style>
  .page-title{
      font-size: 22px;
    }

    /* Vùng nội dung */
    .content {
      padding: 20px;
      background: #f9f9f9;
      min-height: 200vh;
    }

    /* Tiêu đề */
    h2 {
      background: #2196f3;
      color: #fff;
      padding: 12px 18px;
      border-radius: 6px;
      font-size: 20px;
      margin-bottom: 20px;
    }

    /* Form sửa bài học */
    .form {
      background: #fff;
      padding: 25px 30px;
      border-radius: 8px;
      width: 650px;
      margin: 0 auto;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    /* Label */
    .form label {
      font-weight: bold;
      color: #333;
      margin-bottom: 5px;
    }

    /* Input + Select */
    .form input[type="text"],
    .form input[type="number"],
    .form select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 15px;
      transition: border 0.2s;
    }

    .form input:focus,
    .form select:focus {
      border-color: #2196f3;
      outline: none;
    }

    /* File upload */
    .form input[type="file"] {
      border: none;
      padding: 5px 0;
    }

    /* Nút bấm */
    .form .btn {
      display: inline-block;
      background: #2196f3;
      color: #fff;
      text-decoration: none;
      text-align: center;
      padding: 10px 16px;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.25s;
    }

    .form .btn:hover {
      background: #1976d2;
    }

    /* Nút quay lại */
    .form a.btn {
      background: #999;
    }

    .form a.btn:hover {
      background: #777;
    }

    /* Thông báo lỗi */
    .error {
      background: #f44336;
      color: #fff;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
    }

</style>