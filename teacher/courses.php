<?php
// teacher/courses.php
session_start();
require_once("../config/db.php");

// Chặn nếu không phải teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacherId = (int)$_SESSION['user_id'];

// Từ khóa tìm kiếm
$search = trim($_GET['search'] ?? '');

// Lấy danh sách khóa học
try {
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT id, title, class_name, cover_image, created_at, description
            FROM courses
            WHERE teacher_id = ? 
              AND (title LIKE ? OR class_name LIKE ?)
            ORDER BY created_at DESC
        ");
        $like = "%$search%";
        $stmt->execute([$teacherId, $like, $like]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, title, class_name, cover_image, created_at, description
            FROM courses
            WHERE teacher_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$teacherId]);
    }
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>📚 Khóa học của tôi</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
<?php include "includes/sidebar_teacher.php"; ?>
<!-- CONTENT -->
<div class="content">
  <header class="header">
    <div class="page-title"><b><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
    <div class="user">
      <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
      <span>Giảng viên</span>
      <a href="../logout.php">🚪 Đăng xuất</a>
    </div>
  </header>

  <main class="main">
    <h1 class="page-title">📚 Khóa học của tôi</h1>

    <form class="search-bar" method="get">
      <input type="text" name="search" placeholder="Tìm theo tên khóa học hoặc lớp..." value="<?= htmlspecialchars($search) ?>">
      <button type="submit">🔍 Tìm kiếm</button>
      <a class="btn" href="courses.php">🔄 Reset</a>
    </form>

    <?php if (empty($courses)): ?>
      <div class="empty">
        Chưa có khóa học nào được giao. Hãy liên hệ admin để được gán quyền phụ trách khóa học.
      </div>
    <?php else: ?>
      <div class="grid">
        <?php foreach ($courses as $c): ?>
          <div class="card">
            <div class="cover">
              <?php if (!empty($c['cover_image'])): ?>
                <img src="../<?= htmlspecialchars($c['cover_image']) ?>" alt="cover">
              <?php else: ?>
                <span>Không có ảnh bìa</span>
              <?php endif; ?>
            </div>
            <div class="body">
              <div class="title"><?= htmlspecialchars($c['title']) ?></div>
              <div class="meta">
                <span>👨‍🏫 Lớp: <b><?= htmlspecialchars($c['class_name'] ?: '—') ?></b></span>
                <span>🕒 <?= date('d/m/Y', strtotime($c['created_at'])) ?></span>
              </div>
              <?php if (!empty($c['description'])): ?>
                <div class="desc"><?= htmlspecialchars($c['description']) ?></div>
              <?php endif; ?>
              <div class="actions">
                <a href="lessons/list.php?course_id=<?= $c['id'] ?>">📖 Bài học</a>
                <a href="quiz/list.php?course_id=<?= $c['id'] ?>">❓ Quiz</a>
                <a href="assignments/list.php?course_id=<?= $c['id'] ?>">📝 Bài tập</a>
                <a href="schedule/list.php?course_id=<?= $c['id'] ?>">📅 Lịch học</a>
                <a href="notifications/list.php?course_id=<?= $c['id'] ?>">🔔 Thông báo</a>
                <a href="students/list.php?course_id=<?= $c['id'] ?>">👥 Sinh viên</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>

<style>
    main.main {
    padding: 20px;
    }
    /* ==== Page ==== */
    .page-title {
    font-size: 22px;
    }

    /* ==== Search Bar ==== */
    .search-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
    }
    .search-bar input[type=text] {
    flex: 1;
    min-width: 220px;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    }
    .search-bar button,
    .search-bar a.btn {
    padding: 10px 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    }
    .search-bar button {
    background: #007bff;
    color: #fff;
    }
    .search-bar a.btn {
    background: #6c757d;
    color: #fff;
    }

    /* ==== Course Cards ==== */
    .grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
    }
    .card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,.06);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    }
    .card .cover {
    width: 100%;
    height: 140px;
    background: #ecf0f1;
    display: flex;
    align-items: center;
    justify-content: center;
    }
    .card .cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    }
    .card .body {
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    }
    .card .title {
    font-weight: 700;
    font-size: 16px;
    line-height: 1.3;
    }
    .card .meta {
    font-size: 13px;
    color: #666;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    }
    .card .desc {
    font-size: 13px;
    color: #444;
    max-height: 3.2em;
    overflow: hidden;
    }
    .card .actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 8px;
    }
    .card .actions a {
    display: inline-block;
    text-align: center;
    padding: 8px 10px;
    background: #f5f7fb;
    border-radius: 8px;
    text-decoration: none;
    color: #2c3e50;
    font-size: 13px;
    border: 1px solid #e7ebf3;
    transition: background 0.2s, border 0.2s;
    }
    .card .actions a:hover {
    background: #eef3ff;
    border-color: #d6e1fb;
    }

    /* ==== Empty State ==== */
    .empty {
    text-align: center;
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    color: #666;
    border: 1px dashed #cfd6e4;
    }

</style>