<?php
// teacher/lessons/list.php
require_once("../../config/db.php");
session_start();

// Kiểm tra quyền teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = (int)$_SESSION['user_id'];

// Lấy danh sách tất cả khóa học của teacher
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id=? ORDER BY created_at DESC");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Khóa học được chọn
$course_id = (int)($_GET['course_id'] ?? 0);
$sections = [];

if ($course_id > 0) {
    // Kiểm tra quyền sở hữu khóa học
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id=? AND teacher_id=?");
    $stmt->execute([$course_id, $teacherId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        die("⛔ Bạn không có quyền quản lý khóa học này.");
    }

    // Lấy tất cả section
    $stmt = $pdo->prepare("SELECT * FROM sections WHERE course_id=? ORDER BY order_number ASC");
    $stmt->execute([$course_id]);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy bài học theo section
    foreach ($sections as $key => $s) {
    $stmt2 = $pdo->prepare("SELECT * FROM lessons WHERE section_id=? ORDER BY order_number ASC");
    $stmt2->execute([$s['id']]);
    $sections[$key]['lessons'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

}
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>📖 Quản lý bài học</title>
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
  <h2>📖 Quản lý bài học</h2>

  <!-- Chọn khóa học -->
  <div class="filter-box">
    <form method="get" action="list.php">
      <label for="course_id"><b>Chọn khóa học:</b></label>
      <select name="course_id" id="course_id" onchange="this.form.submit()">
        <option value="">-- Chọn khóa học --</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= ($c['id'] == $course_id) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <?php if ($course_id > 0): ?>
    <h3>📚 Khóa học: <?= htmlspecialchars($course['title']) ?></h3>
    <a href="add_section.php?course_id=<?= $course_id ?>" class="btn">➕ Thêm chương học</a>

    <?php if (empty($sections)): ?>
      <div class="empty">📌 Khóa học chưa có chương học nào.</div>
    <?php else: ?>

      <?php foreach ($sections as $index => $s): ?>
        <div class="section-box">
          <div class="section-header">
            <h4>📂 Chương <?= $s['order_number'] ?>: <?= htmlspecialchars($s['title']) ?></h4>
            <div class="section-actions">
              <a href="add_lesson.php?section_id=<?= $s['id'] ?>" class="btn-sm btn-green">➕ Bài học</a>
              <a href="edit_section.php?id=<?= $s['id'] ?>&course_id=<?= $course_id ?>" class="btn-sm btn-blue">✏️ Sửa</a>
              <a href="delete_section.php?id=<?= $s['id'] ?>&course_id=<?= $course_id ?>" 
                class="btn-sm btn-red"
                onclick="return confirm('Bạn có chắc muốn xóa section này? Tất cả bài học trong section cũng sẽ bị xóa!')">🗑️ Xóa</a>
            </div>
          </div>

          <?php if (empty($s['lessons'])): ?>
            <p class="empty"><i>📌 Chưa có bài học nào trong chương này.</i></p>
          <?php else: ?>
            <?php foreach ($s['lessons'] as $l): ?>
  <div class="lesson-item">
    <div class="lesson-title" onclick="toggleLesson(<?= $l['id'] ?>)">
      <?= $l['order_number'] ?>. <?= htmlspecialchars($l['title']) ?> ⬇
    </div>
    <div class="lesson-content" id="lesson-<?= $l['id'] ?>">
      <?php if (!empty($l['content_link'])): ?>
        <?php if ($l['content_type'] === 'video'): ?>
          📹 Video
          <video controls>
            <source src="../../<?= htmlspecialchars($l['content_link']) ?>" type="video/mp4">
            Trình duyệt không hỗ trợ video.
          </video>
        <?php elseif ($l['content_type'] === 'pdf'): ?>
          📄 PDF
          <iframe src="../../<?= htmlspecialchars($l['content_link']) ?>"></iframe>
          <br><a href="../../<?= htmlspecialchars($l['content_link']) ?>" target="_blank">📥 Tải PDF</a>
        <?php elseif ($l['content_type'] === 'text'): ?>
          📝 Tài liệu text
          <br><a href="<?= htmlspecialchars($l['content_link']) ?>" target="_blank">🔗 Xem nội dung</a>
        <?php endif; ?>
      <?php else: ?>
        <i>Không có nội dung</i>
      <?php endif; ?>

      <div class="lesson-actions">
        <a href="edit_lesson.php?id=<?= $l['id'] ?>" class="btn-sm btn-blue">✏️ Sửa</a>
        <a href="delete_lesson.php?id=<?= $l['id'] ?>&course_id=<?= $course_id ?>"
           class="btn-sm btn-red"
           onclick="return confirm('Xóa bài học này?')">🗑️ Xóa</a>
      </div>
    </div>
  </div>
<?php endforeach; ?>


            </table>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

    <?php endif; ?>
  <?php else: ?>
    <p>👉 Vui lòng chọn khóa học để xem danh sách bài học.</p>
  <?php endif; ?>
</div>
</div>

<script>
function toggleLesson(id) {
  let content = document.getElementById("lesson-" + id);
  if (content.style.display === "block") {
    content.style.display = "none";
  } else {

    document.querySelectorAll(".lesson-content").forEach(el => el.style.display = "none");
    content.style.display = "block";
  }
}
</script>

</body>
</html>

<style>
    .section-box {
      margin-bottom: 25px;
      padding: 15px;
      border: 1px solid #ddd;
      border-radius: 10px;
      background: #f9f9f9;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .section-header h4 {
      margin: 0;
      font-size: 18px;
      color: #2c3e50;
    }

    .section-actions a {
      margin-left: 8px;
    }

    .lesson-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      background: #fff;
    }

    .lesson-table th, .lesson-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: center;
    }

    .lesson-table th {
      background: #2c3e50;
      color: #fff;
    }

    .btn-sm {
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 13px;
      text-decoration: none;
      color: #fff;
    }

    .btn-green { background: #28a745; }
    .btn-blue { background: #007bff; }
    .btn-red { background: #dc3545; }

    .btn-green:hover { background: #218838; }
    .btn-blue:hover { background: #0056b3; }
    .btn-red:hover { background: #c82333; }

    .empty {
      font-style: italic;
      color: #666;
      padding: 5px 0;
    }
   
    .lesson-table th:nth-child(3),
    .lesson-table td:nth-child(3) {
      text-align: left;
      white-space: normal;
      word-break: break-word;
      width: 55%; 
    }

   
    .lesson-table td:nth-child(3) embed,
    .lesson-table td:nth-child(3) iframe,
    .lesson-table td:nth-child(3) video {
      width: 100%;     
      height: auto;    
      max-height: 700px;
    }

  .lesson-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 8px 0;
    overflow: hidden;
    background: #fff;
  }

  .lesson-title {
    padding: 10px;
    font-weight: bold;
    background: #f1f1f1;
    cursor: pointer;
  }

  .lesson-title:hover {
    background: #e2e6ea;
  }

  .lesson-content {
    display: none; /* Ẩn mặc định */
    padding: 15px;
  }

  .lesson-content video,
  .lesson-content iframe {
    width: 100%;
    height: 300px;
    border-radius: 5px;
  }

  .lesson-actions {
    margin-top: 10px;
  }

    .page-title{
      font-size: 22px;
    }
  </style>