<?php
session_start();
require_once "../config/db.php";


// Kiểm tra đăng nhập & quyền
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Lấy thống kê từ CSDL
try {
    // Tổng số người dùng
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Số lượng theo vai trò
    $totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();

    // Tổng số khóa học
    $totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();

    // Top 5 khóa học có nhiều sinh viên
    $sql = "
        SELECT c.id, c.title, u.name AS teacher_name, COUNT(e.student_id) AS total_students
        FROM courses c
        LEFT JOIN users u ON c.teacher_id = u.id
        LEFT JOIN course_enrollments e ON c.id = e.course_id
        GROUP BY c.id, c.title, u.name
        ORDER BY total_students DESC
        LIMIT 5
    ";
    $topCourses = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

// Avatar mặc định
$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <!-- SIDEBAR -->
  <?php include "includes/sidebar1.php"; ?>

  <!-- MAIN -->
  <div class="content">
    <header class="header">
      <div><b style="font-size: 25px;"><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
      <div class="user">
        <img src="<?php echo htmlspecialchars($avatar); ?>" 
             alt="avatar" style="width:40px; height:40px; border-radius:50%">
        <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        <a href="../logout.php">🚪 Đăng xuất</a>
      </div>
    </header>

    <main class="main">
      <h1>📊 Thống kê hệ thống LMS</h1>

      <!-- Cards -->
      <div class="cards">
        <div class="card">
          <p>Tổng người dùng</p>
          <h2><?php echo $totalUsers; ?></h2>
        </div>
        <div class="card">
          <p>Giảng viên</p>
          <h2><?php echo $totalTeachers; ?></h2>
        </div>
        <div class="card">
          <p>Học viên</p>
          <h2><?php echo $totalStudents; ?></h2>
        </div>
        <div class="card">
          <p>Khóa học</p>
          <h2><?php echo $totalCourses; ?></h2>
        </div>
      </div>

      <!-- Top Courses -->
      <h2>🏆 Top 5 khóa học nhiều sinh viên nhất</h2>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Tên khóa học</th>
            <th>Giảng viên</th>
            <th>Số sinh viên</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($topCourses)): ?>
            <?php foreach ($topCourses as $i => $course): ?>
              <tr>
                <td><?php echo $i+1; ?></td>
                <td><?php echo htmlspecialchars($course['title']); ?></td>
                <td><?php echo htmlspecialchars($course['teacher_name']); ?></td>
                <td><?php echo $course['total_students']; ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4">Chưa có dữ liệu</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </main>
  </div>
</body>
</html>
  <style>
    .cards {
      display: grid; 
      grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); 
      gap:20px; margin-bottom:30px;
    }
    .card {
      background:#f4f6f9; 
      padding:20px; 
      border-radius:10px; 
      text-align:center; 
      box-shadow:0 2px 5px rgba(0,0,0,0.1);
    }
    .card h2 {
      margin:10px 0; 
      font-size:28px; 
      color:#2c3e50;
    }
    table {
      width:100%; 
      border-collapse:collapse; 
      background:#fff;
    }
    table th, table td {
      border:1px solid #ddd; 
      padding:10px; 
      text-align:left;
    }
    table th {
      background:#2c3e50; 
      color:#fff;
    }
  </style>