<?php
require_once("../../config/db.php");

session_start();

if ($_SESSION['role'] !== 'teacher') { 
    header("Location: ../../login.php"); 
    exit; 
}
$teacherId = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

// Kiểm tra giảng viên có đúng quyền với khóa học không
if ($course_id) {
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE id=? AND teacher_id=?");
    $stmt->execute([$course_id, $teacherId]);
    if (!$stmt->fetch()) {
        die("⛔ Bạn không có quyền gửi thông báo cho khóa học này!");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8');
    $target = $_POST['target']; 
    $student_id = $_POST['student_id'] ?? null;

    if ($message && $course_id) {
        if ($target === "all") {
            // Thông báo chung cho cả lớp
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, course_id, message) VALUES (NULL, ?, ?)");
            $stmt->execute([$course_id, $message]);
        } elseif ($target === "student" && $student_id) {
            // Thông báo riêng cho sinh viên
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, course_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $course_id, $message]);
        }
        header("Location: list.php?course_id=".$course_id);
        exit;
    }
}

// Lấy danh sách sinh viên trong khóa học
$students = [];
if ($course_id) {
    $stmt = $pdo->prepare("SELECT u.id, u.name 
                           FROM course_enrollments ce 
                           JOIN users u ON ce.student_id=u.id 
                           WHERE ce.course_id=?");
    $stmt->execute([$course_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>🔔 Gửi Thông báo</title>
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
    <h2>Gửi thông báo</h2>

    <?php if ($course_id): ?>
    <form method="post">
        <label>Nội dung:</label><br>
        <textarea name="message" required></textarea><br><br>

        <label>Đối tượng:</label>
        <select name="target" onchange="document.getElementById('studentBox').style.display=this.value=='student'?'block':'none'">
            <option value="all">📢 Toàn khóa học</option>
            <option value="student">👨‍🎓 Sinh viên cụ thể</option>
        </select><br><br>

        <div id="studentBox" style="display:none;">
            <label>Chọn sinh viên:</label>
            <select name="student_id">
                <?php foreach($students as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select><br><br>
        </div>
        <button type="submit" class="btn">📢 Gửi</button>
    </form>
    <?php else: ?>
        <p style="color:red;">⚠️ Vui lòng chọn khóa học từ trang quản lý thông báo.</p>
    <?php endif; ?>
    </div>
</div>
</body>
</html>

<style>
    .page-title{
        font-size: 22px;
    }
  textarea {
    width: 100%;
    min-height: 100px;
    padding: 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
    }
    button {
        padding: 8px 15px;
        border: none;
        border-radius: 6px;
        background: #3498db;
        color: #fff;
        cursor: pointer;
    }
    button:hover { 
        background: #2980b9; 
    }
</style>
