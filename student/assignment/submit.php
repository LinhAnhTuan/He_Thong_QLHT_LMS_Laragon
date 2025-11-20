<?php
require_once("../../config/db.php");
require_once("../includes/log_helper.php");
autoLogAction($pdo);

session_start();

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../login.php");
    exit;
}

$studentId = $_SESSION['user_id'];

/* ===============================
   LẤY DANH SÁCH KHÓA HỌC CỦA SV
================================= */
$stmt = $pdo->prepare("
    SELECT c.id, c.title, c.class_name
    FROM course_enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ?
");
$stmt->execute([$studentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$course_id = (int)($_GET['course_id'] ?? 0);
$assignments = [];

/* ===============================
   LẤY DANH SÁCH BÀI TẬP
================================= */
if ($course_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM assignments 
        WHERE course_id=? 
        ORDER BY id ASC
    ");
    $stmt->execute([$course_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===============================
   XỬ LÝ NỘP FILE
================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {

    $assignment_id = (int)$_POST['assignment_id'];

    // Kiểm tra submission đã tồn tại chưa
    $stmt = $pdo->prepare("
        SELECT id 
        FROM assignment_submissions 
        WHERE assignment_id=? AND student_id=?
    ");
    $stmt->execute([$assignment_id, $studentId]);
    $sub = $stmt->fetch();

    if ($sub) {
        $submission_id = $sub['id'];
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO assignment_submissions (assignment_id, student_id) 
            VALUES (?, ?)
        ");
        $stmt->execute([$assignment_id, $studentId]);
        $submission_id = $pdo->lastInsertId();
    }

    /* ---------------------------
       UPLOAD MULTIPLE FILES
    ---------------------------- */

    // Tạo thư mục nếu chưa có
    $uploadDir = "../../uploads/assignments/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    foreach ($_FILES['files']['tmp_name'] as $i => $tmpName) {
        if ($tmpName) {

            $originalName = basename($_FILES['files']['name'][$i]);
            $safeName = preg_replace("/[^A-Za-z0-9_\.-]/", "_", $originalName);
            $filename = time() . "_" . uniqid() . "_" . $safeName;

            $fullPath = $uploadDir . $filename;

            move_uploaded_file($tmpName, $fullPath);

            // Lưu vào DB (KHÔNG có ../../)
            $stmt = $pdo->prepare("
                INSERT INTO assignment_submission_files (submission_id, file_path) 
                VALUES (?, ?)
            ");
            $stmt->execute([$submission_id, "uploads/assignments/" . $filename]);
        }
    }

    header("Location: submit.php?course_id=$course_id");
    exit;
}

$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>📂 Nộp bài tập</title>
<link rel="stylesheet" href="../../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>

<?php include "../includes/sidebar2.php"; ?>

<div class="content">
<header class="header">
    <div class="page-title"><b><?php echo htmlspecialchars($_SESSION['name']); ?></b></div>
    <div class="user">
    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
    <span>Sinh viên</span>
    <a href="../../logout.php">🚪 Đăng xuất</a>
    </div>
</header>

<div class="main">
<h2>📂 Nộp bài tập</h2>

<!-- ============================
     CHỌN KHÓA HỌC
============================ -->
<form method="get">
    <label>Chọn khóa học:</label>
    <select name="course_id" onchange="this.form.submit()">
        <option value="">-- Chọn --</option>
        <?php foreach($courses as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($c['id']==$course_id)?'selected':'' ?>>
                <?= htmlspecialchars($c['class_name'] . ' - ' . $c['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($course_id): ?>

    <?php if (!$assignments): ?>
        <p><i>Chưa có bài tập nào.</i></p>

    <?php else: ?>

        <?php 
        $can_submit = true; // Cho phép nộp bài đầu tiên
        foreach($assignments as $a): ?>
        
            <div class="assignment-card">
                <h3><?= htmlspecialchars($a['title']) ?></h3>
                <p><b>📅 Hạn nộp:</b> <?= $a['due_date'] ?></p>

                <?php if(!empty($a['description'])): ?>
                    <p><b>📖 Yêu cầu:</b> <?= nl2br(htmlspecialchars($a['description'])) ?></p>
                <?php endif; ?>

                <?php if(!empty($a['file_attachment'])): ?>
                    <p><b>📎 File từ giảng viên:</b>
                        <a href="../../<?= htmlspecialchars($a['file_attachment']) ?>" target="_blank">
                            <i class="fa-solid fa-eye"></i> Xem
                        </a>
                    </p>
                <?php endif; ?>

                <?php
                // Lấy bài nộp
                $stmt = $pdo->prepare("
                    SELECT * FROM assignment_submissions 
                    WHERE student_id=? AND assignment_id=?
                ");
                $stmt->execute([$studentId, $a['id']]);
                $sub = $stmt->fetch();

                if ($sub): 
                    // Đã nộp → lấy file
                    $stmt = $pdo->prepare("
                        SELECT * FROM assignment_submission_files 
                        WHERE submission_id=?
                    ");
                    $stmt->execute([$sub['id']]);
                    $files = $stmt->fetchAll();

                    echo "<p>✅ Bạn đã nộp:</p><div class='submitted-files'>";
                    foreach($files as $f){
                        echo "<div class='file-item'>";
                        echo "<a href='../../{$f['file_path']}' target='_blank'>".basename($f['file_path'])."</a>";
                        echo "<a href='delete_file.php?id={$f['id']}&course_id={$course_id}' class='delete' onclick=\"return confirm('Xóa file này?')\">🗑</a>";
                        echo "</div>";
                    }
                    echo "</div>";

                    echo "<a href='delete_submission.php?id={$sub['id']}&course_id={$course_id}' class='delete-submission' onclick=\"return confirm('Xóa toàn bộ bài nộp?')\">🗑 Xóa toàn bộ</a>";

                    // Cho phép nộp thêm file
                    ?>
                    <form method="post" enctype="multipart/form-data" style="margin-top:10px;">
                        <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                        <input type="file" name="files[]" multiple>
                        <button type="submit" class="btn">➕ Nộp thêm</button>
                    </form>
                    <?php

                    $can_submit = true; // Mở khóa bài tiếp theo

                else:
                    // Chưa nộp
                    if ($can_submit): ?>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                            <input type="file" name="files[]" multiple required>
                            <button type="submit" class="btn">📤 Nộp bài</button>
                        </form>
                        <?php 
                        $can_submit = false; // Khóa bài tiếp theo
                    else: ?>
                        <p style="color:red;"><i>⚠ Bạn cần nộp bài trước mới được nộp bài này.</i></p>
                    <?php endif;
                endif;
                ?>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

<?php endif; ?>
</div>
</div>
</body>
</html>

<style>
.page-title{
    font-size: 22px;
}

.assignment-card {
    background: #fff;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.submitted-files {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 6px;
}

.file-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px dashed #ccc;
}

.file-item:last-child {
    border-bottom: none;
}

.file-item .delete {
    color: #e74c3c;
    margin-left: 10px;
}

.delete-submission {
    display: inline-block;
    margin-top: 8px;
    color: #e67e22;
    font-weight: bold;
}
</style>
