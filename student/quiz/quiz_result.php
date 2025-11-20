<?php
require_once("../../config/db.php");
session_start();

if ($_SESSION['role'] !== 'student') { header("Location: ../login.php"); exit; }
$studentId = $_SESSION['user_id'];

$quiz_id = (int)($_GET['id'] ?? 0);

// Lấy thông tin quiz
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id=?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();
if (!$quiz) die("⛔ Quiz không tồn tại!");

// Kiểm tra sinh viên có làm quiz chưa
$stmt = $pdo->prepare("SELECT * FROM quiz_attempts WHERE quiz_id=? AND student_id=? ORDER BY submitted_at DESC LIMIT 1");
$stmt->execute([$quiz_id, $studentId]);
$attempt = $stmt->fetch();

if (!$attempt) {
    die("⛔ Bạn chưa làm quiz này!");
}

// Lấy câu hỏi + đáp án
$stmt = $pdo->prepare("
    SELECT q.id as qid, q.question_text, q.question_type, o.id as oid, o.option_text, o.is_correct
    FROM quiz_questions q
    LEFT JOIN quiz_options o ON q.id = o.question_id
    WHERE q.quiz_id=?
    ORDER BY q.id
");
$stmt->execute([$quiz_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gom câu hỏi + options
$questions = [];
foreach ($rows as $r) {
    $qid = $r['qid'];
    if (!isset($questions[$qid])) {
        $questions[$qid] = [
            'id' => $qid,
            'text' => $r['question_text'],
            'type' => $r['question_type'],
            'options' => []
        ];
    }
    if ($r['oid']) {
        $questions[$qid]['options'][] = [
            'id' => $r['oid'],
            'text' => $r['option_text'],
            'is_correct' => $r['is_correct']
        ];
    }
}

// Lấy đáp án sinh viên đã chọn
$stmt = $pdo->prepare("SELECT * FROM quiz_attempt_answers WHERE attempt_id=?");
$stmt->execute([$attempt['id']]);
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$student_answers = [];
foreach ($answers as $a) {
    $student_answers[$a['question_id']] = $a['answer_id'];
}

// Avatar sinh viên
$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>📊 Kết quả Quiz - <?= htmlspecialchars($quiz['title']) ?></title>
  <link rel="stylesheet" href="../../style.css">
  <style>
    .quiz-box {background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
    .question {margin-bottom:20px;}
    .question h3 {margin-bottom:10px; color:#2c3e50;}
    .options {margin-left:15px;}
    .correct {color:green; font-weight:bold;}
    .wrong {color:red; font-weight:bold;}
    .result {margin-bottom:20px; padding:15px; background:#ecf7ff; border-left:5px solid #2980b9;}
    .btn-back {display:inline-block; margin-top:20px; padding:10px 14px; background:#7f8c8d; color:#fff; border-radius:6px; text-decoration:none;}
    .btn-back:hover {background:#636e72;}
  </style>
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
  <h2>📊 Kết quả Quiz: <?= htmlspecialchars($quiz['title']) ?></h2>

  <div class="result">
    <p>🏆 Điểm của bạn: <b><?= $attempt['score'] ?>/<?= $quiz['max_score'] ?></b></p>
    <p>⏱ Nộp lúc: <?= $attempt['submitted_at'] ?></p>
  </div>

  <div class="quiz-box">
    <?php foreach ($questions as $q): ?>
      <div class="question">
        <h3><?= htmlspecialchars($q['text']) ?></h3>
        <div class="options">
          <?php foreach ($q['options'] as $opt): 
            $isChosen = (isset($student_answers[$q['id']]) && $student_answers[$q['id']] == $opt['id']);
            ?>
            <p class="<?= $opt['is_correct'] ? 'correct' : ($isChosen ? 'wrong' : '') ?>">
              <?= $isChosen ? "👉 " : "" ?>
              <?= htmlspecialchars($opt['text']) ?>
              <?php if ($opt['is_correct']): ?> ✅ Đáp án đúng<?php endif; ?>
              <?php if ($isChosen && !$opt['is_correct']): ?> ❌ Bạn chọn<?php endif; ?>
            </p>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <a href="quiz_list.php?course_id=<?= $quiz['course_id'] ?>" class="btn-back">🔙 Quay lại danh sách Quiz</a>
</div>
</div>
</body>
</html>
