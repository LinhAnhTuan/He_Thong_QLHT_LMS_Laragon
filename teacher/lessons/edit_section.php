<?php
// teacher/lessons/edit_section.php
require_once("../../config/db.php");

session_start();

// Kiểm tra quyền teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

// Lấy section và kiểm tra quyền
$stmt = $pdo->prepare("
    SELECT s.*, c.teacher_id, c.title as course_title
    FROM sections s
    JOIN courses c ON s.course_id = c.id
    WHERE s.id=? AND c.teacher_id=?
");
$stmt->execute([$id, $teacherId]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$section) {
    die("⛔ Section không tồn tại hoặc bạn không có quyền.");
}

$course_id = $section['course_id'];

// Xử lý khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $order_number = (int)($_POST['order_number'] ?? 0);

    if ($title === '') {
        $error = "⚠️ Vui lòng nhập tiêu đề Section.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE sections SET title=?, order_number=? WHERE id=?");
            $stmt->execute([$title, $order_number, $id]);

            header("Location: list.php?course_id=" . $course_id);
            exit;
        } catch (PDOException $e) {
            $error = "Lỗi khi cập nhật Section: " . $e->getMessage();
        }
    }
}
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>✏️ Sửa Section</title>
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
    <h2>✏️ Sửa Section - <?= htmlspecialchars($section['course_title']) ?></h2>
    
    <?php if (!empty($error)): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" class="form">
      <label>Tiêu đề Section:</label>
      <input type="text" name="title" value="<?= htmlspecialchars($section['title']) ?>" required>

      <label>Thứ tự hiển thị (STT):</label>
      <input type="number" name="order_number" value="<?= $section['order_number'] ?>">

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


    /* Main */
    .main h2 {
      margin-bottom: 20px;
      font-size: 20px;
      color: #2c3e50;
   
    }

    .error {
      background: #ffe0e0;
      color: #d32f2f;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 15px;
    }

    .form {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      max-width: 500px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);

      margin: 0 auto; 
    }


    .form label {
      display: block;
      margin: 10px 0 5px;
      font-weight: bold;
    }

    .form input[type="text"],
    .form input[type="number"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 15px;
    }

    .btn {
      display: inline-block;
      margin-top: 15px;
      padding: 10px 18px;
      border-radius: 6px;
      background: #3498db;
      color: #fff;
      text-decoration: none;
      font-size: 15px;
      transition: 0.2s;
    }

    .btn:hover {
      background: #2980b9;
    }

    .btn + .btn {
      background: #95a5a6;
    }

    .btn + .btn:hover {
      background: #7f8c8d;
    }

</style>