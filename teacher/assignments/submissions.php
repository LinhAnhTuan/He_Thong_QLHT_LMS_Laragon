<?php
require_once("../../config/db.php");

session_start();

if ($_SESSION['role'] !== 'teacher') { 
    header("Location: ../../login.php"); 
    exit; 
}

$teacherId = $_SESSION['user_id'];
$assignment_id = (int)($_GET['assignment_id'] ?? 0);

// Lấy thông tin bài tập
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title 
    FROM assignments a 
    JOIN courses c ON a.course_id = c.id 
    WHERE a.id=? AND c.teacher_id=?
");
$stmt->execute([$assignment_id, $teacherId]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) die("⛔ Bạn không có quyền xem bài tập này.");

// ========================
// LẤY TẤT CẢ BÀI NỘP + FILE
// ========================
$stmt = $pdo->prepare("
    SELECT s.*, u.name, u.student_code
    FROM assignment_submissions s
    JOIN users u ON s.student_id = u.id
    WHERE s.assignment_id=?
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$assignment_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy file của từng submission
$fileStmt = $pdo->prepare("
    SELECT * FROM assignment_submission_files 
    WHERE submission_id=?
");
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>📤 Bài nộp</title>
<link rel="stylesheet" href="../../style.css">
</head>
<body>

<?php include "../includes/sidebar_teacher2.php"; ?>

<div class="content">
<header class="header">
    <div class="page-title"><b><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
    <div class="user">
        <img src="<?php echo htmlspecialchars($avatar); ?>" 
             alt="avatar" style="width:40px; height:40px; border-radius:50%">
        <span>Giảng viên</span>
        <a href="../../logout.php">🚪 Đăng xuất</a>
    </div>
</header>

<div class="main">
<h2>📤 Bài nộp: <?= htmlspecialchars($assignment['title']) ?></h2>
<a href="list.php?course_id=<?= $assignment['course_id'] ?>" class="btn back-btn">⬅ Quay lại</a>

<?php if (!$submissions): ?>
    <p class="empty"><i>Chưa có sinh viên nào nộp bài.</i></p>

<?php else: ?>
    <a href="download_all.php?assignment_id=<?= $assignment['id'] ?>" class="btn">⬇️ Tải tất cả (.zip)</a>

    <table class="styled-table">
    <thead>
        <tr>
            <th>📌 Sinh viên</th>
            <th>Mã SV</th>
            <th>Tệp đã nộp</th>
            <th>Ngày nộp</th>
            <th>Trạng thái</th>
            <th>Điểm</th>
            <th>Nhận xét</th>
            <th>Chấm điểm</th>
        </tr>
    </thead>

    <tbody>
    <?php foreach ($submissions as $s): ?>

        <?php 
            // Load file cho submission
            $fileStmt->execute([$s['id']]);
            $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

            // Trạng thái nộp đúng hạn
            $deadline = strtotime($assignment['due_date']);  // <-- đổi từ deadline sang due_date
            $submitted = strtotime($s['submitted_at']);
            $status = ($submitted <= $deadline) ? "Đúng hạn" : "Trễ hạn";
            $color = ($submitted <= $deadline) ? "green" : "red";

        ?>

        <tr>
            <td><?= htmlspecialchars($s['name']) ?></td>
            <td><?= htmlspecialchars($s['student_code']) ?></td>

            <!-- HIỂN THỊ NHIỀU FILE -->
            <td style="text-align:left;">
                <?php if ($files): ?>
                    <?php foreach ($files as $f): ?>
                        <div>
                            <a href="../../<?= htmlspecialchars($f['file_path']) ?>" target="_blank">📎 <?= basename($f['file_path']) ?></a>
                            | <a href="download.php?file=<?= urlencode($f['file_path']) ?>">⬇️</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>

            <td><?= $s['submitted_at'] ?></td>

            <td><b style="color:<?= $color ?>"><?= $status ?></b></td>

            <td><?= $s['grade'] !== null ? $s['grade'] : '-' ?></td>
            <td><?= htmlspecialchars($s['feedback'] ?? '') ?></td>

            <td>
                <form method="post" action="grade.php" class="inline-form">
                    <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">
                    <input type="number" name="grade" value="<?= $s['grade'] ?>" step="0.1" min="0" max="10" required>
                    <input type="text" name="feedback" placeholder="Nhận xét" 
                           value="<?= htmlspecialchars($s['feedback'] ?? '') ?>">
                    <button type="submit" class="btn small">💾</button>
                </form>
            </td>
        </tr>

    <?php endforeach; ?>
    </tbody>
    </table>

<?php endif; ?>
</div>
</div>
</body>
</html>

<style>
.page-title{ font-size:22px; }

.styled-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.styled-table th, .styled-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}

.styled-table th {
    background: #f4f4f4;
    font-weight: bold;
}

.inline-form input[type="number"],
.inline-form input[type="text"] {
    padding: 4px;
    margin: 2px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 13px;
}

.btn.small {
    padding: 4px 8px;
    font-size: 12px;
}
</style>
