<?php
// teacher/lessons/add_lesson.php
require_once("../../config/db.php");


session_start();

// ✅ Kiểm tra quyền teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = (int)$_SESSION['user_id'];
$section_id = (int)($_GET['section_id'] ?? 0);

// ✅ Kiểm tra section có tồn tại và thuộc về giáo viên
$sql = "
    SELECT s.*, c.title as course_title, c.id as course_id
    FROM sections s
    JOIN courses c ON s.course_id = c.id
    WHERE s.id=? AND c.teacher_id=?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$section_id, $teacherId]);
$section = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$section) {
    die("⛔ Section không tồn tại hoặc bạn không có quyền.");
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? "");
    $content_type = $_POST['content_type'] ?? "video";
    $order_number = (int)($_POST['order_number'] ?? 0);
    $content_link = ""; // Khởi tạo ban đầu

    // ✅ Kiểm tra tiêu đề
    if ($title === "") {
        $error = "⚠️ Vui lòng nhập tiêu đề bài học.";
    } elseif (!in_array($content_type, ['video', 'pdf', 'text'])) {
        $error = "⚠️ Loại nội dung không hợp lệ.";
    } else {
        // ✅ Nếu có file upload
        if (!empty($_FILES['file_upload']['name'])) {
            $uploadDir = "../../uploads/lessons/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // Tạo thư mục nếu chưa có
            }

            // Tên file đã được làm sạch để tránh ký tự đặc biệt
            $originalFileName = pathinfo($_FILES['file_upload']['name'], PATHINFO_FILENAME);
            $extension = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
            $sanitizedFileName = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $originalFileName); // Chỉ giữ lại chữ, số, _, . -

            $fileName = time() . "_" . $sanitizedFileName . "." . $extension;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $targetPath)) {
                $content_link = "uploads/lessons/" . $fileName; // Lưu đường dẫn tương đối
            } else {
                $error = "❌ Lỗi khi upload file.";
            }
        } else {
            // ✅ Nếu không upload file, lấy link nhập tay
            $content_link = trim($_POST['content_link'] ?? "");
        }

        // 🛑 CẢI TIẾN: Bắt buộc phải có file upload HOẶC link nhập tay
        if (!$error && empty($content_link)) {
             $error = "⚠️ Vui lòng tải file nội dung HOẶC nhập đường dẫn liên kết.";
        }


        if (!$error) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO lessons (section_id, title, content_type, content_link, order_number)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$section_id, $title, $content_type, $content_link, $order_number]);

                // ✅ Quay lại danh sách
                header("Location: list.php?course_id=" . $section['course_id']);
                exit;
            } catch (PDOException $e) {
                $error = "❌ Lỗi khi thêm bài học: " . $e->getMessage();
            }
        }
    }
}
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>➕ Thêm Bài học</title>
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
    <h2>➕ Thêm Bài học vào Section: <?= htmlspecialchars($section['title']) ?> (Khóa học: <?= htmlspecialchars($section['course_title']) ?>)</h2>
   
    <?php if ($error): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" class="form" enctype="multipart/form-data">
      <label>Tiêu đề bài học:</label>
      <input type="text" name="title" required placeholder="Nhập tên bài học">

      <label>Loại nội dung:</label>
      <select name="content_type" required>
        <option value="video">🎬 Video</option>
        <option value="pdf">📑 Tài liệu</option>
        <option value="text">📝 Link</option>
      </select>

      <label>Tải file (Video/PDF) hoặc nhập link:</label>
      <input type="file" name="file_upload" accept=".mp4,.avi,.pdf">
      <p>Hoặc nhập link:</p>
      <input type="text" name="content_link" placeholder="https://...">

      <label>Thứ tự hiển thị (STT):</label>
      <input type="number" name="order_number" value="0" min="0">

      <button type="submit" class="btn">💾 Lưu Bài học</button>
       <a href="list.php?course_id=<?= $section['course_id'] ?>" class="btn">⬅ Quay lại</a>
    </form>
  </main>
</div>
</body>
</html>
<style>
  .page-title{
      font-size: 22px;
    }
    /* Khung chính */
.content {
  padding: 20px;
  background: #f9f9f9;
  min-height: 100vh;
}

/* Tiêu đề chính */
.main h2 {
  background: #4caf50;
  color: #fff;
  padding: 12px 18px;
  border-radius: 6px;
  font-size: 20px;
  margin-bottom: 20px;
}

/* Form thêm bài học */
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

/* Input, select */
.form input[type="text"],
.form input[type="number"],
.form input[type="file"],
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
  border-color: #4caf50;
  outline: none;
}

/* Ghi chú nhỏ */
.form p {
  margin: 5px 0;
  font-size: 14px;
  color: #666;
}

/* Nút bấm */
.form .btn {
  display: inline-block;
  background: #4caf50;
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
  background: #43a047;
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