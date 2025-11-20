<?php
require_once("../../config/db.php");
require_once("../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

session_start();

if ($_SESSION['role'] !== 'teacher') { 
    header("Location: ../../login.php"); 
    exit; 
}

$teacherId = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

// ✅ Kiểm tra khóa học thuộc giáo viên
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id=? AND teacher_id=?");
$stmt->execute([$course_id, $teacherId]);
$course = $stmt->fetch();
if (!$course) {
    die("⛔ Bạn không có quyền với khóa học này.");
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date    = $_POST['due_date'] ?? null;

    // ✅ Upload file
    $filePath = null;
    if (!empty($_FILES['file_attachment']['name'])) {
        $uploadDir = "uploads/assignments/";
        if (!is_dir("../../" . $uploadDir)) {
            mkdir("../../" . $uploadDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES['file_attachment']['name']);
        $target   = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['file_attachment']['tmp_name'], "../../" . $target)) {
            $filePath = $target;
        } else {
            $error = "⚠️ Lỗi khi upload file.";
        }
    }

    if (!$error) {
        // ✅ Lưu bài tập
        $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, description, file_attachment, due_date) VALUES (?,?,?,?,?)");
        $stmt->execute([$course_id, $title, $description, $filePath, $due_date]);

        // ✅ Gửi thông báo cho toàn bộ sinh viên của khóa học
        $msg = "📚 Có bài tập mới: <b>" . htmlspecialchars($title) . "</b>";
        if ($due_date) {
            $msg .= " ⏰ Hạn nộp: " . date("d/m/Y H:i", strtotime($due_date));
        }

        $stmt = $pdo->prepare("SELECT student_id FROM course_enrollments WHERE course_id=?");
        $stmt->execute([$course_id]);
        $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($students as $sid) {
            $pdo->prepare("INSERT INTO notifications (user_id, course_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())")
                ->execute([$sid, $course_id, $msg]);
        }

        $success = "✅ Tạo bài tập thành công và đã gửi thông báo!";
    }
}
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>➕ Tạo Bài tập</title>
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
<div class="main">
  <h2>➕ Tạo Bài tập cho khóa học: <?= htmlspecialchars($course['title']) ?></h2>
  
  <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
  <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="form">
    <label>Tiêu đề:</label>
    <input type="text" name="title" required>

    <label>Mô tả:</label>
    <textarea name="description" rows="4"></textarea>

    <label>File đính kèm (tùy chọn):</label>
    <input type="file" name="file_attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip">

    <label>Hạn nộp:</label>
    <input type="datetime-local" name="due_date"> <br>

    <br><button type="submit" class="btn">💾 Lưu</button> ||
    <a href="list.php?course_id=<?= $course_id ?>" class="btn">⬅ Quay lại</a>
  </form>
</div>
</div>
</body>
</html>
<style>
    .page-title{
      font-size: 22px;
    }
    /* Nội dung chính */
    .content {
       
        font-family: Arial, sans-serif;
    }

    /* Tiêu đề */
    .content h2 {
        margin-bottom: 15px;
        color: #333;
    }

    /* Nút quay lại */
    .btn-back {
        background: #6c757d;  /* xám trung tính */
        color: white;
        padding: 8px 15px;
        text-decoration: none;
        border-radius: 6px;
        transition: background 0.3s;
        display: inline-block;
        margin: 5px 0;
        font-size: 14px;
    }
    .btn-back:hover {
        background: #5a6268;  /* hover xám đậm hơn */
    }


    /* Form */
    .form {
        margin-left: 350px;
        max-width: 500px;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .form label {
        display: block;
        margin-top: 12px;
        font-weight: bold;
        color: #444;
    }

    .form input[type="text"],
    .form input[type="datetime-local"],
    .form input[type="file"],
    .form textarea {
        width: 100%;
        padding: 8px;
        margin-top: 6px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
        font-size: 14px;
    }

    .form textarea {
        resize: vertical;
        min-height: 80px;
    }

    /* Thông báo */
    .success {
        background: #d4edda;
        color: #155724;
        padding: 10px;
        border-left: 5px solid #28a745;
        margin-bottom: 15px;
        border-radius: 5px;
    }

    .error {
        background: #f8d7da;
        color: #721c24;
        padding: 10px;
        border-left: 5px solid #dc3545;
        margin-bottom: 15px;
        border-radius: 5px;
    }

</style>