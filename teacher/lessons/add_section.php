<?php
// teacher/lessons/add_section.php
require_once("../../config/db.php");


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
    $order_number = (int)($_POST['order_number'] ?? 0);

    if ($title === "") {
        $error = "⚠️ Vui lòng nhập tiêu đề Section.";
    } else {
        try {
            // ✅ Chèn section mới
            $stmt = $pdo->prepare("INSERT INTO sections (course_id, title, order_number) VALUES (?, ?, ?)");
            $stmt->execute([$course_id, $title, $order_number]);

            // ✅ Quay lại trang danh sách
            header("Location: list.php?course_id=$course_id");
            exit;
        } catch (PDOException $e) {
            $error = "❌ Lỗi khi thêm Section: " . $e->getMessage();
        }
    }
}
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>➕ Thêm Section</title>
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
    <h2>➕ Thêm Section cho khóa học: <?= htmlspecialchars($course['title']) ?></h2>
    
    <?php if ($error): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" class="form">
      <label>Tiêu đề chương:</label>
      <input type="text" name="title" required placeholder="Nhập tên Section">

      <label>Thứ tự chương hiển thị (STT):</label>
      <input type="number" name="order_number" value="0" min="0">

      <button type="submit" class="btn">💾 Lưu Section</button>
      <a href="list.php?course_id=<?= $course_id ?>" class="btn">⬅ Quay lại</a>
    </form>
  </main>
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
    min-height: 100vh;
  }

  /* Tiêu đề */
  .main h2 {
    background: #2196f3;
    color: #fff;
    padding: 12px 18px;
    border-radius: 6px;
    font-size: 20px;
    margin-bottom: 20px;
  }

  /* Form thêm section */
  .form {
    background: #fff;
    padding: 25px 30px;
    border-radius: 8px;
    max-width: 600px;
    margin: 0 auto;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 15px;
  }

  /* Label */
  .form label {
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
  }

  /* Input */
  .form input[type="text"],
  .form input[type="number"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 15px;
    transition: border 0.2s;
  }

  .form input:focus {
    border-color: #2196f3;
    outline: none;
  }

  /* Nút bấm */
  .form .btn {
    display: inline-block;
    background: #2196f3;
    color: white;
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
    color: white;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 6px;
  }

</style>