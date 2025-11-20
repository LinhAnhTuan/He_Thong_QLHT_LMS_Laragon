<?php
require_once("../../config/db.php");
session_start();

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../login.php");
    exit;
}

$studentId = $_SESSION['user_id'];

// Lấy danh sách chứng chỉ của sinh viên
$stmt = $pdo->prepare("
    SELECT c.id, c.course_id, co.title, co.class_name, c.score, c.issued_at, c.qr_code
    FROM certificates c
    JOIN courses co ON c.course_id = co.id
    WHERE c.student_id = ?
");

$stmt->execute([$studentId]);
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>🎓 Chứng chỉ của bạn</title>
<link rel="stylesheet" href="../../style.css">
</head>
<body>
<?php include "../includes/sidebar2.php"; ?>

<div class="content">
    <header class="header">
        <div class="page-title"><b><?= htmlspecialchars($_SESSION['name']); ?></b></div>
        <div class="user">
            <img src="<?= htmlspecialchars($avatar); ?>" style="width:40px; height:40px; border-radius:50%">
            <span>Sinh viên</span>
            <a href="../../logout.php">🚪 Đăng xuất</a>
        </div>
    </header>

    <div class="main">
        <h2>🎓 Chứng chỉ của bạn</h2>

        <?php if(!$certificates): ?>
            <p><i>Bạn chưa có chứng chỉ nào.</i></p>
        <?php else: ?>
            <div class="cert-container">
                <?php foreach($certificates as $cert): ?>
                    <?php
                        // Lấy tổng số bài của khóa học
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE course_id=?");
                        $stmt->execute([$cert['course_id']]);
                        $totalAssignments = (int)$stmt->fetchColumn();

                        // Lấy số bài đã nộp của sinh viên
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id=?) AND student_id=?");
                        $stmt->execute([$cert['course_id'], $studentId]);
                        $submittedCount = (int)$stmt->fetchColumn();

                        // Tính tiến độ %
                        $progress = $totalAssignments ? round($submittedCount / $totalAssignments * 100, 1) : 0;

                        // Xác định class màu
                        $progressClass = $progress >= 70 ? 'green' : 'red';
                    ?>
                    <div class="cert-card">
                        <h3><?= htmlspecialchars($cert['class_name']." - ".$cert['title']) ?></h3>
                        <small>Ngày cấp: <?= date('d/m/Y', strtotime($cert['issued_at'])) ?></small>
                        
                        <div class="progress <?= $progressClass ?>">
                            Tiến độ: <?= $progress ?>% (<?= $submittedCount ?>/<?= $totalAssignments ?> bài)
                        </div>

                        <?php if($cert['qr_code']): ?>
                            <img src="../../<?= htmlspecialchars($cert['qr_code']) ?>" alt="QR" width="150"><br>
                        <?php endif; ?>
                        <a class="btn-download" href="certificate_view.php?cert_id=<?= $cert['id'] ?>">🔍 Xem chi tiết</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
<style>
.page-title {
    font-size: 22px;
}

.main h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-weight: 600;
}

.cert-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.cert-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
    padding: 18px;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    position: relative;
}

.cert-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.cert-card h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 18px;
}

.cert-card small {
    display: block;
    color: #7f8c8d;
    margin-bottom: 8px;
}

.cert-card img {
    border-radius: 6px;
    margin-top: 6px;
    cursor: pointer;
    transition: transform 0.3s ease;
}
.btn-download {
    display: inline-block;
    margin-top: 10px;
    padding: 8px 14px;
    background: #3498db;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.3s ease;
}

.btn-download:hover {
    background: #1c5980;
}

.score {
    font-weight: 600;
}

.score.green { color: #27ae60; }
.score.red { color: #e74c3c; }
.score.gray { color: #7f8c8d; }

.header {
    box-shadow: 0 1px 6px rgba(0,0,0,0.08);
    padding: 12px 20px;
    border-radius: 0 0 10px 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>