<?php
require_once("../../config/db.php");

session_start();

if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = $_SESSION['user_id'];
$course_id = (int)($_GET['course_id'] ?? 0);

// ✅ Kiểm tra quyền giảng viên
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id=? AND teacher_id=?");
$stmt->execute([$course_id, $teacherId]);
$course = $stmt->fetch();
if (!$course) die("⛔ Bạn không có quyền truy cập khóa học này.");

// ✅ Lấy danh sách sinh viên chưa tham gia khóa học
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.student_code, u.email
    FROM users u
    WHERE u.role='student'
    AND u.id NOT IN (
        SELECT student_id FROM course_enrollments WHERE course_id=?
    )
    ORDER BY u.name
");
$stmt->execute([$course_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Xử lý thêm sinh viên
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['student_ids'])) {
        foreach ($_POST['student_ids'] as $sid) {
            // ⛔ Tránh thêm trùng
            $check = $pdo->prepare("SELECT id FROM course_enrollments WHERE course_id=? AND student_id=?");
            $check->execute([$course_id, $sid]);
            if (!$check->fetch()) {
                $insert = $pdo->prepare("INSERT INTO course_enrollments(course_id,student_id) VALUES (?,?)");
                $insert->execute([$course_id, (int)$sid]);
            }
        }
    }
    header("Location: list.php?course_id=" . $course_id);
    exit;
}

$avatar = !empty($_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>➕ Thêm sinh viên vào lớp</title>
<link rel="stylesheet" href="../../style.css">
<style>
    body { font-family: "Segoe UI", sans-serif; background: #f6f9fc; margin: 0; }
    .main { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    h2 { color: #2c3e50; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; border-radius: 10px; overflow: hidden; }
    th, td { padding: 10px 12px; text-align: left; }
    th { background: #2980b9; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    tr:hover { background: #ecf7ff; transition: 0.2s; }
    .btn { display: inline-block; padding: 8px 14px; background: #3498db; color: #fff; border: none; border-radius: 6px; text-decoration: none; cursor: pointer; }
    .btn:hover { background: #2980b9; }
    #search { margin: 10px 0; padding: 8px; width: 300px; border-radius: 6px; border: 1px solid #ccc; }
    .page-title { font-size: 22px; }
</style>
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
            <h2>➕ Thêm sinh viên vào lớp: <?= htmlspecialchars($course['title'] ?? 'Không xác định') ?></h2>
            <a href="list.php?course_id=<?= $course_id ?>" class="btn">⬅ Quay lại</a>

            <?php if (!$students): ?>
                <p><i>✅ Tất cả sinh viên đã được thêm vào lớp.</i></p>
            <?php else: ?>
                <input type="text" id="search" placeholder="🔍 Tìm sinh viên..." onkeyup="filterStudents()">
                <form method="post" class="form">
                    <table>
                        <tr>
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>Mã SV</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                        </tr>
                        <?php foreach ($students as $s): ?>
                        <tr>
                            <td><input type="checkbox" name="student_ids[]" value="<?= $s['id'] ?>"></td>
                            <td><?= htmlspecialchars($s['student_code']) ?></td>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <button type="submit" class="btn">➕ Thêm các sinh viên đã chọn</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

<script>
// ✅ Check/uncheck tất cả
document.getElementById("checkAll")?.addEventListener("change", function() {
    let checkboxes = document.querySelectorAll("input[name='student_ids[]']");
    for (let cb of checkboxes) cb.checked = this.checked;
});

// ✅ Lọc danh sách sinh viên
function filterStudents() {
    const q = document.getElementById('search').value.toLowerCase();
    document.querySelectorAll('table tr').forEach((tr, i) => {
        if (i === 0) return;
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// ✅ Cảnh báo nếu chưa chọn sinh viên
document.querySelector('form')?.addEventListener('submit', function(e) {
    if (!document.querySelectorAll("input[name='student_ids[]']:checked").length) {
        alert("⚠️ Bạn chưa chọn sinh viên nào!");
        e.preventDefault();
    }
});
</script>
</body>
</html>
