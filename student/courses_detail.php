<?php
require_once("../config/db.php");
session_start();

// ✅ Kiểm tra session và role an toàn
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../login.php"); 
    exit;
}

// Lấy student ID
$studentId = $_SESSION['user_id'] ?? 0;

// Lấy course_id
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($course_id <= 0) {
    header("Location: dashboard.php"); // Redirect nếu ID không hợp lệ
    exit;
}

// ✅ Kiểm tra sinh viên có thuộc khóa học không
$stmt = $pdo->prepare("
    SELECT c.*, u.name as teacher
    FROM course_enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.teacher_id = u.id
    WHERE e.student_id=? AND c.id=?
");
$stmt->execute([$studentId, $course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header("Location: dashboard.php"); // Không thuộc khóa học
    exit;
}

// ✅ Lấy sections + lessons + trạng thái tiến độ
$stmt = $pdo->prepare("
    SELECT s.id as section_id, s.title as section_title, 
           l.id, l.title, l.content_type, l.content_link, l.order_number,
           lp.is_completed, lp.completed_at
    FROM sections s
    LEFT JOIN lessons l ON l.section_id = s.id
    LEFT JOIN lesson_progress lp 
           ON lp.lesson_id = l.id AND lp.student_id=?
    WHERE s.course_id=?
    ORDER BY s.order_number, l.order_number
");
$stmt->execute([$studentId, $course_id]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Lấy assignments
$stmt = $pdo->prepare("SELECT * FROM assignments WHERE course_id=? ORDER BY due_date");
$stmt->execute([$course_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Lấy quizzes
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE course_id=?");
$stmt->execute([$course_id]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Lấy schedules
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE course_id=? ORDER BY start_time");
$stmt->execute([$course_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Avatar
$avatar = !empty($_SESSION['avatar']) && file_exists("../" . $_SESSION['avatar']) ? "../" . $_SESSION['avatar'] : "../uploads/default.png";

// ✅ CSRF token cho button hoàn thành
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 Khóa học của tôi</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style_2.css">
</head>
<body>
<?php include "includes/sidebar.php"; ?>

<div class="content">
    <header class="header">
        <div class="page-title"><b><?= htmlspecialchars($_SESSION['name'] ?? '') ?></b></div>
        <div class="user">
            <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar" class="avatar">
            <span>Sinh viên</span>
            <a href="../logout.php">🚪 Đăng xuất</a>
        </div>
    </header>

    <div class="main">
        <h2>📚 <?= htmlspecialchars($course['title']) ?></h2>
        <p><b>Giảng viên:</b> <?= htmlspecialchars($course['teacher']) ?></p>
        <p><b>Mã lớp:</b> <?= htmlspecialchars($course['class_name']) ?></p>

        <!-- ==== Bài học ==== -->
        <div class="section-box">
            <h3>📖 Nội dung học</h3>
            <?php if (!$lessons): ?>
                <p><i>Chưa có nội dung.</i></p>
            <?php else: ?>
                <?php
                $current_section = null;
                foreach ($lessons as $l):
                    if ($l['section_title'] !== $current_section):
                        if ($current_section !== null) echo "</ul>";
                        echo "<h4>📂 ".htmlspecialchars($l['section_title'])."</h4><ul>";
                        $current_section = $l['section_title'];
                    endif;

                    if ($l['id']):
                        $icon = match ($l['content_type']) {
                            'video' => '▶️',
                            'pdf' => '📄',
                            'text' => '🔗',
                            default => '💡'
                        };

                        echo "<li>{$icon} ".htmlspecialchars($l['title'])." ";
                       
                        $contentLink = $l['content_link'];
                        $displayLink = "";

                        // Video
                        if ($l['content_type']=='video') {
                            if (!preg_match('/^https?:\/\//', $contentLink)) {
                                $contentLink = "../" . $contentLink;
                                echo"<br>";
                            }
                            echo "<div class='video-wrapper'>
                                    <video controls>
                                        <source src='".htmlspecialchars($contentLink)."' type='video/mp4'>
                                        Trình duyệt không hỗ trợ video.
                                    </video>
                                  </div>";
                        }

                        // PDF
                        elseif ($l['content_type']=='pdf') {
                            if (!preg_match('/^https?:\/\//', $contentLink)) {
                                $contentLink = "../" . $contentLink;
                            }
                            $displayLink = "<a href='".htmlspecialchars($contentLink)."' target='_blank'>📄 Xem tài liệu</a>";
                        }
                        // Text / Link
                        elseif ($l['content_type']=='text') {
                            if (!preg_match('/^https?:\/\//i', $contentLink)) {
                                $contentLink = "http://" . $contentLink;
                            }
                            $displayLink = "<a href='".htmlspecialchars($contentLink)."' target='_blank'>🔗 Truy cập Link</a>";
                        }

                        echo $displayLink;

                        // Trạng thái hoàn thành
                        if ($l['is_completed']) {
                            echo " ✅ <span class='completed'>(Hoàn thành)</span>";
                        } else {
                            if ($l['content_type'] != 'video') {
                                echo " <button class='btn-done' data-lesson='{$l['id']}' data-csrf='{$csrf_token}'>Hoàn thành</button>";
                            }
                        }
                        echo "</li>";
                    endif;
                endforeach;
                echo "</ul>";
                ?>
            <?php endif; ?>
        </div>

        <!-- ==== Bài tập ==== -->
        <div class="section-box">
            <h3>📂 Bài tập</h3>
            <?php if (!$assignments): ?>
                <p><i>Chưa có bài tập.</i></p>
            <?php else: ?>
                <ul>
                <?php foreach ($assignments as $a): ?>
                    <li>
                        <?= htmlspecialchars($a['title']) ?> - Hạn: <?= $a['due_date'] ?>
                        <a href="assignment/submit.php?id=<?= $a['id'] ?>">📤 Nộp</a>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- ==== Quiz ==== -->
        <div class="section-box">
            <h3>❓ Quiz</h3>
            <?php if (!$quizzes): ?>
                <p><i>Chưa có quiz.</i></p>
            <?php else: ?>
                <ul>
                <?php foreach ($quizzes as $q): ?>
                    <li>
                        <?= htmlspecialchars($q['title']) ?> (Thời gian: <?= $q['time_limit'] ?> phút)
                        <a href="quiz/quiz_list.php?id=<?= $q['id'] ?>">🚀 Bắt đầu</a>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- ==== Lịch học ==== -->
        <div class="section-box">
            <h3>📅 Lịch học</h3>
            <?php if (!$schedules): ?>
                <p><i>Chưa có lịch học.</i></p>
            <?php else: ?>
                <ul>
                <?php foreach ($schedules as $s): ?>
                    <li>
                        <?= htmlspecialchars($s['session_title']) ?> - 
                        <?= date("d/m/Y H:i", strtotime($s['start_time'])) ?>
                        <?php if ($s['meeting_link']): ?>
                            <a href="<?= htmlspecialchars($s['meeting_link']) ?>" target="_blank">🔗 Tham gia</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
document.querySelectorAll(".btn-done").forEach(btn=>{
    btn.addEventListener("click", ()=>{
        let lessonId = btn.getAttribute("data-lesson");
        let csrfToken = btn.getAttribute("data-csrf");

        fetch("mark_lesson.php", {
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"lesson_id="+lessonId+"&csrf_token="+csrfToken
        })
        .then(res=>res.json())
        .then(data=>{
            alert(data.message);
            if(data.status==="success") location.reload();
        });
    });
});
</script>
</body>
</html>
<Style>
 .video-wrapper {
    margin-top: 8px; 
}
.video-wrapper video {
    width: 100%;      
    max-width: 600px; 
    height: auto;
    border-radius: 5px;
}

</Style>