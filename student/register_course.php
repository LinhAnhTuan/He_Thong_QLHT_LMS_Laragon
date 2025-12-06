<?php
require_once("../config/db.php");
require_once("../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

session_start();

if ($_SESSION['role'] !== 'student') { 
    header("Location: ../login.php"); 
    exit; 
}

$studentId = $_SESSION['user_id'];

// ✅ Lấy danh sách khóa học chưa đăng ký
$stmt = $pdo->prepare("
    SELECT 
        c.id, 
        c.title, 
        c.class_name, 
        c.description,
        u.name AS teacher 
    FROM courses c
    JOIN users u ON c.teacher_id = u.id
    WHERE c.id NOT IN (
        SELECT course_id FROM course_enrollments WHERE student_id=?
    )
    ORDER BY c.created_at DESC
");
$stmt->execute([$studentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Xử lý khi đăng ký
if (isset($_GET['course_id'])) {
    $course_id = (int)$_GET['course_id'];

    // Kiểm tra khóa học tồn tại
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id=?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    if ($course) {
        // Thêm vào enrollments
        $stmt = $pdo->prepare("INSERT INTO course_enrollments(course_id, student_id, progress) VALUES (?,?,0)");
        $stmt->execute([$course_id, $studentId]);

        header("Location: courses.php"); // Quay lại danh sách khóa học đã đăng ký
        exit;
    }
}

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>➕ Đăng ký khóa học</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
<?php include "includes/sidebar.php"; ?>
<div class="content">
    <header class="header">
        <div class="page-title"><b><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
        <div class="user">
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
            <span>Sinh viên</span>
            <a href="../logout.php">🚪 Đăng xuất</a>
        </div>
    </header>

<div class="main">
  <h2>➕ Đăng ký khóa học</h2>
  <?php if (!$courses): ?>
    <p><i>Bạn đã đăng ký tất cả các khóa học hiện có.</i></p>
  <?php else: ?>
    <table class="table-student">
      <tr>
        <th>Mã lớp</th>
        <th>Tên khóa học</th>
        <th>Giảng viên</th>
        <th>Mô tả</th>
        <th>Đăng ký</th>
      </tr>
      <?php foreach ($courses as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['class_name']) ?></td>
          <td><?= htmlspecialchars($c['title']) ?></td>
          <td><?= htmlspecialchars($c['teacher']) ?></td>
          <td><?= nl2br(htmlspecialchars($c['description'])) ?></td>
          <td>
            <a href="register_course.php?course_id=<?= $c['id'] ?>" 
               onclick="return confirm('Bạn có chắc muốn đăng ký khóa học này?')">➕ Đăng ký</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
</div>
</body>
</html>

<style>
.page-title{
    font-size: 22px;
}
.table-student {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.table-student th,
.table-student td:nth-child(4), 
.table-student td {
  padding: 14px 16px;
  text-align: center;
  border-bottom: 1px solid #eee;
  font-size: 14px;
}

.table-student th {
  background: #2980b9;
  color: white;
  text-transform: uppercase;
  font-size: 13px;
  letter-spacing: 0.5px;
}

.table-student tr:nth-child(even) {
  background: #f9f9f9;
}

.table-student tr:hover {
  background: #ecf7ff;
  transition: 0.25s;
}


/* Link Chi tiết */
.table-student a {
  background: #27ae60;
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 13px;
  font-weight: bold;
}

.table-student a:hover {
  background: #1e8449;
}
</style>
