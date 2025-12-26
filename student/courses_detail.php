<?php
require_once("../config/db.php");
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../../login.php");
    exit;
}

$studentId = $_SESSION['user_id'] ?? 0;
$course_id = (int)($_GET['id'] ?? 0);
if ($course_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.*, u.name AS teacher
    FROM course_enrollments e
    JOIN courses c ON c.id = e.course_id
    JOIN users u ON u.id = c.teacher_id
    WHERE e.student_id=? AND c.id=?
");
$stmt->execute([$studentId, $course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$course) {
    header("Location: dashboard.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.title AS section_title,
           l.id, l.title, l.content_type, l.content_link,
           lp.is_completed
    FROM sections s
    LEFT JOIN lessons l ON l.section_id = s.id
    LEFT JOIN lesson_progress lp 
           ON lp.lesson_id=l.id AND lp.student_id=?
    WHERE s.course_id=?
    ORDER BY s.order_number, l.order_number
");
$stmt->execute([$studentId, $course_id]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);


$assignments = $pdo->query("SELECT * FROM assignments WHERE course_id=$course_id")->fetchAll();
$quizzes     = $pdo->query("SELECT * FROM quizzes WHERE course_id=$course_id")->fetchAll();
$schedules   = $pdo->query("SELECT * FROM schedules WHERE course_id=$course_id")->fetchAll();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>📚 Khóa học</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="style_2.css">
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="content">
    <div class="main">

        <h2>📚 <?= htmlspecialchars($course['title']) ?></h2>
        <p><b>Giảng viên:</b> <?= htmlspecialchars($course['teacher']) ?></p>

        <div class="section-box">
        <h3>📖 Nội dung học</h3>

        <?php
        $current = null;
        foreach ($lessons as $l):

            if ($l['section_title'] !== $current):
                if ($current !== null) echo "</ul>";
                echo "<h4>📂 ".htmlspecialchars($l['section_title'])."</h4><ul>";
                $current = $l['section_title'];
            endif;

            if (!$l['id']) continue;
            ?>

            <li class="lesson-item">

                <details class="lesson-box">
                    <summary class="lesson-summary">
                        <?= $l['content_type']==='video'?'▶️':'📄' ?>
                        <?= htmlspecialchars($l['title']) ?>
                    </summary>

                    <div class="lesson-content">
                        <?php
                        $link = $l['content_link'];
                        if (!preg_match('/^https?:\/\//',$link)) $link="../".$link;

                        if ($l['content_type']==='video'): ?>
                        <div class="video-wrapper">
                        <video class="lesson-video"
                            data-lesson="<?= $l['id'] ?>"
                            controls
                            controlsList="nodownload">
                            <source src="<?= htmlspecialchars($link) ?>" type="video/mp4">
                        </video>
                        </div>

                        <?php else: ?>
                        <a href="<?= htmlspecialchars($link) ?>" target="_blank">🔗 Truy cập</a>
                        <?php endif; ?>
                    </div>
                </details>

                <div class="lesson-status">
                    <?php if ($l['is_completed']): ?>
                    ✅ Hoàn thành
                    <?php elseif ($l['content_type']!=='video'): ?>
                    <button class="btn-done"
                            data-lesson="<?= $l['id'] ?>"
                            data-csrf="<?= $csrf_token ?>">Hoàn thành</button>
                    <?php else: ?>
                    ⏳ Xem ≥ 90% video
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
        </div>

        <div class="section-box">
            <h3>📂 Bài tập</h3>

            <?php if (!$assignments): ?>
                <p><i>Chưa có bài tập.</i></p>
            <?php else: ?>
            <ul>
            <?php foreach ($assignments as $a): ?>
            <li>
                <?= htmlspecialchars($a['title']) ?>
                <span> (Hạn: <?= htmlspecialchars($a['due_date']) ?>)</span>
                <a href="assignment/submit.php?id=<?= $a['id'] ?>">📤 Nộp</a>
            </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    
        <div class="section-box">
            <h3>❓ Quiz</h3>

            <?php if (!$quizzes): ?>
                <p><i>Chưa có quiz.</i></p>
            <?php else: ?>
            <ul>
            <?php foreach ($quizzes as $q): ?>
            <li>
                <?= htmlspecialchars($q['title']) ?>
                (<?= (int)$q['time_limit'] ?> phút)
                <a href="quiz/quiz_list.php?id=<?= $q['id'] ?>">🚀 Bắt đầu</a>
            </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>

        </div>

        <div class="section-box">
            <h3>📅 Lịch học</h3>

            <?php if (!$schedules): ?>
                <p><i>Chưa có lịch học.</i></p>
            <?php else: ?>
            <ul>
            <?php foreach ($schedules as $s): ?>
            <li>
                <?= htmlspecialchars($s['session_title'] ?? 'Buổi học') ?>
                – <?= date("d/m/Y H:i", strtotime($s['start_time'])) ?>

                <?php if (!empty($s['meeting_link'])): ?>
                    <a href="<?= htmlspecialchars($s['meeting_link']) ?>" target="_blank">
                        🔗 Tham gia
                    </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- POPUP CHECKPOINT -->
<div id="checkpointModal" class="checkpoint-modal hidden">
    <div class="checkpoint-box">
        <h3 id="cp-question"></h3>
        <div id="cp-options"></div>
        <button id="cp-continue" disabled>Tiếp tục</button>
    </div>
</div>

<script>
/*  Hoàn thành thủ công */
document.querySelectorAll(".btn-done").forEach(btn=>{
    btn.onclick=()=>{
        fetch("mark_lesson.php",{
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"lesson_id="+btn.dataset.lesson+"&csrf_token="+btn.dataset.csrf
        }).then(()=>location.reload());
    };
});

/* Video + Checkpoint */
document.querySelectorAll(".lesson-video").forEach(video=>{

const checkpoints=[
    {p:20,q:"Bạn đã hiểu phần này chưa?",o:["Rồi","Chưa rõ"]},
    {p:40,q:"Tốc độ giảng có phù hợp?",o:["Phù hợp","Hơi nhanh"]},
    {p:60,q:"Bạn có nghiêm túc xem bài giảng?",o:["Có","Không"]},
    {p:80,q:"Bạn đã nắm nội dung chưa?",o:["Có","Chưa"]}
];

let last=0,done=new Set(),finished=false,showing=false;

video.addEventListener("seeking",()=>{
    if(video.currentTime>last+2) video.currentTime=last;
});

video.addEventListener("timeupdate",()=>{
    if(video.currentTime>last) last=video.currentTime;
    if(!video.duration) return;

    let percent=video.currentTime/video.duration*100;

    checkpoints.forEach((c,i)=>{
        if(percent>=c.p && !done.has(i) && !showing){
            done.add(i);
            showing=true;
            showCheckpoint(video,c,()=>showing=false);
        }
    });

    if(percent>=90 && !finished){
        finished=true;
        fetch("mark_lesson.php",{
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"lesson_id="+video.dataset.lesson+"&auto=1"
        }).then(()=>location.reload());
    }
    });
});

function showCheckpoint(video,c,cb){
    video.pause();
    const m=document.getElementById("checkpointModal");
    const q=document.getElementById("cp-question");
    const o=document.getElementById("cp-options");
    const b=document.getElementById("cp-continue");

    q.textContent=c.q;
    o.innerHTML="";
    b.disabled=true;

    c.o.forEach(t=>{
    let lb=document.createElement("label");
    lb.innerHTML=`<input type="radio" name="cp"> ${t}`;
    lb.querySelector("input").onchange=()=>b.disabled=false;
    o.appendChild(lb);
    });

    m.classList.remove("hidden");
    b.onclick=()=>{m.classList.add("hidden");video.play();cb&&cb();};
}
</script>

</body>
</html>
<style>
.section-box {
    background: #fff;
    border-radius: 12px;
    padding: 18px 22px;
    margin-bottom: 25px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.06);
}

.section-box h3 {
    margin-bottom: 12px;
    font-size: 20px;
    border-left: 4px solid #4f46e5;
    padding-left: 10px;
}

.lesson-item {
    padding: 12px 0;
    border-bottom: 1px dashed #ddd;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.lesson-box {
    width: 100%;
}

.lesson-summary {
    cursor: pointer;
    font-weight: 600;
    list-style: none;
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 6px 0;
    transition: color .2s;
}

.lesson-summary:hover {
    color: #4f46e5;
}


.lesson-summary::-webkit-details-marker {
    display: none;
}

.lesson-summary::after {
    margin-left: auto;
    font-size: 13px;
    opacity: .6;
}

.lesson-box[open] .lesson-summary::after {
    content: "⬆";
}

.lesson-content {
    margin-top: 10px;
    margin-left: 20px;
}

.video-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 12px;
}

.video-wrapper video {
    width: 85%;
    max-width: 900px;
    height: 480px;
    border-radius: 12px;
    background: #000;
    box-shadow: 0 8px 20px rgba(0,0,0,0.25);
}

.lesson-status {
    font-size: 14px;
    margin-left: 20px;
    color: #555;
}

.lesson-status .completed {
    color: #16a34a;
    font-weight: 600;
}

.btn-done {
    padding: 6px 14px;
    border-radius: 20px;
    border: none;
    background: #4f46e5;
    color: #fff;
    cursor: pointer;
    font-size: 13px;
    transition: all .2s;
}

.btn-done:hover {
    background: #4338ca;
    transform: translateY(-1px);
}

.section-box ul {
    list-style: none;
    padding-left: 0;
}

.section-box li {
    padding: 10px 0;
    border-bottom: 1px dashed #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.section-box li a {
    text-decoration: none;
    font-size: 13px;
    color: #2563eb;
    font-weight: 500;
}

.section-box li a:hover {
    text-decoration: underline;
}

.checkpoint-modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.checkpoint-modal.hidden {
    display: none;
}

.checkpoint-box {
    background: #fff;
    padding: 22px 26px;
    width: 420px;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,.3);
    animation: pop .25s ease;
}

@keyframes pop {
    from {transform: scale(.9); opacity: 0;}
    to   {transform: scale(1); opacity: 1;}
}

#cp-options label {
    display: block;
    padding: 6px 0;
    cursor: pointer;
}

#cp-continue {
    margin-top: 12px;
    padding: 8px 18px;
    border-radius: 20px;
    border: none;
    background: #16a34a;
    color: #fff;
    cursor: pointer;
    transition: .2s;
}

#cp-continue:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

#cp-continue:hover:not(:disabled) {
    background: #15803d;
}
</style>
