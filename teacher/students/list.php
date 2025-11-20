<?php
require_once("../../config/db.php");
session_start();

if ($_SESSION['role'] !== 'teacher') { header("Location: ../../login.php"); exit; }
$teacherId = $_SESSION['user_id'];

// ✅ Lấy danh sách khóa học của giảng viên
$stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id=?");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";

$course_id = (int)($_GET['course_id'] ?? 0);
$students = [];
$certificates = [];

if ($course_id) {
    // ✅ Kiểm tra quyền
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id=? AND teacher_id=?");
    $stmt->execute([$course_id, $teacherId]);
    $course = $stmt->fetch();
    if (!$course) {
        echo "<p style='color:red; font-weight:bold'>⛔ Bạn không có quyền truy cập khóa học này.</p>";
        exit;
    }

// ✅ Lấy danh sách sinh viên + tính tiến độ trực tiếp từ cơ sở dữ liệu
$stmt = $pdo->prepare("
    SELECT 
        e.id AS enroll_id,
        u.id AS student_id,
        u.name,
        u.student_code,
        u.email,

        -- Tính % tiến độ bài học
        COALESCE((
            SELECT 
                COUNT(*) 
            FROM lesson_progress lp
            JOIN lessons l ON lp.lesson_id = l.id
            JOIN sections s ON l.section_id = s.id
            WHERE lp.student_id = u.id 
            AND lp.is_completed = 1
            AND s.course_id = e.course_id
        ) / NULLIF((
            SELECT COUNT(*) 
            FROM lessons l
            JOIN sections s ON l.section_id = s.id
            WHERE s.course_id = e.course_id
        ), 0) * 100, 0) AS progress

    FROM course_enrollments e
    JOIN users u ON e.student_id = u.id
    WHERE e.course_id=?
    ORDER BY u.name ASC
");
$stmt->execute([$course_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ✅ Lấy danh sách sinh viên đã có chứng chỉ (1 query duy nhất)
    $certStmt = $pdo->prepare("SELECT student_id FROM certificates WHERE course_id=?");
    $certStmt->execute([$course_id]);
    $certificates = $certStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>📚 Quản lý Sinh viên</title>
<link rel="stylesheet" href="../../style.css">
</head>
<body>
<?php include "../includes/sidebar_teacher2.php"; ?>
<div class="content">
<header class="header">
    <div class="page-title"><b><?= htmlspecialchars($_SESSION['name']) ?></b></div>
    <div class="user">
        <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
        <span>Giảng viên</span>
        <a href="../../logout.php">🚪 Đăng xuất</a>
    </div>
</header>

<div class="main">
    <h2>👨‍🎓 Quản lý Sinh viên</h2>
    <form method="get">
        <label>Chọn khóa học:</label>
        <select name="course_id" onchange="this.form.submit()">
            <option value="">-- Chọn --</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id']==$course_id?'selected':'' ?>>
                    <?= htmlspecialchars($c['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($course_id): ?>
        <h3>📚 Lớp: <?= htmlspecialchars($course['title']) ?></h3>
        <a href="add.php?course_id=<?= $course_id ?>" class="btn">➕ Thêm sinh viên</a> <br>

        <!-- 🔍 Ô tìm kiếm sinh viên -->
        <input type="text" id="search" placeholder="🔍 Tìm theo tên, mã hoặc email..." onkeyup="filterStudents()" style="margin:10px 0; padding:8px; width:300px; border-radius:6px; border:1px solid #ccc;">

        <form method="post" action="delete.php" onsubmit="return confirmDeleteSelected()">
        <input type="hidden" name="course_id" value="<?= $course_id ?>">

        <table class="table-student">
            <thead>
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>STT</th>
                <th>Mã SV</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Tiến độ</th>
                <th>Thao tác</th>
                <th>Chứng chỉ</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $i=>$s): ?>
            <tr>
                <td><input type="checkbox" name="ids[]" value="<?= $s['enroll_id'] ?>"></td>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($s['student_code']) ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= round($s['progress']) ?>%</td>

                <td>
                    <a href="delete.php?id=<?= $s['enroll_id'] ?>&course_id=<?= $course_id ?>" 
                    onclick="return confirm('Xóa sinh viên này khỏi lớp?')">🗑 Xóa</a>
                </td>
                <td>
                    <?php if (in_array($s['student_id'], $certificates)): ?>
                        <a href="delete_certificate.php?student_id=<?= $s['student_id'] ?>&course_id=<?= $course_id ?>"
                        class="btn-delete-cert"
                        onclick="return confirm('Bạn có chắc muốn xóa chứng chỉ của sinh viên này?')">❌ Xóa chứng chỉ</a>
                    <?php else: ?>
                        <a href="issue_certificate.php?student_id=<?= $s['student_id'] ?>&course_id=<?= $course_id ?>"
                        class="btn-issue">🎓 Cấp chứng chỉ</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn-danger" style="margin-top:10px">🗑 Xóa đã chọn</button>
        </form>
    <?php endif; ?>
</div>
</div>

<script>
// ✅ Checkbox chọn tất cả
document.getElementById('checkAll')?.addEventListener('change', function(){
    const checked = this.checked;
    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = checked);
});

// ✅ Xác nhận khi không chọn sinh viên nào
function confirmDeleteSelected() {
    const checked = document.querySelectorAll('input[name="ids[]"]:checked').length;
    if (!checked) {
        alert('⚠️ Vui lòng chọn ít nhất 1 sinh viên để xóa.');
        return false;
    }
    return confirm('Bạn có chắc muốn xóa sinh viên đã chọn?');
}

// 🔍 Lọc sinh viên theo từ khóa
function filterStudents() {
    const q = document.getElementById('search').value.toLowerCase();
    document.querySelectorAll('.table-student tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>

<style>
.page-title { font-size: 22px; }
.content h2 { color: #2c3e50; margin-bottom: 15px; }
.content h3 { margin: 15px 0; color: #16a085; }
.content form { margin-bottom: 15px; }

.table-student {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.table-student th, .table-student td {
    padding: 12px 14px;
    border-bottom: 1px solid #eee;
    text-align: left;
}
.table-student th {
    background: #2980b9;
    color: white;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 0.5px;
}
.table-student tr:nth-child(even) { background: #f9f9f9; }
.table-student tr:hover { background: #ecf7ff; transition: 0.2s; }

.table-student a {  text-decoration: none; font-weight: bold; }
.table-student a:hover { color: #2baac0ff; }

.btn-issue {
    display: inline-block;
    padding: 6px 12px;
    background: #27ae60;
    color: #fff;
    border-radius: 5px;
    text-decoration: none;
    font-size: 13px;
}
.btn-issue:hover { background: #1e8449; }

.btn-delete-cert {
    display: inline-block;
    padding: 6px 12px;
    background: #e74c3c;
    color: #fff;
    border-radius: 5px;
    text-decoration: none;
    font-size: 13px;
}
.btn-delete-cert:hover { background: #c0392b; }
</style>

</body>
</html>
