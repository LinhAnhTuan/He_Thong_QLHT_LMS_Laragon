<?php
require_once("../../config/db.php");
require_once("../../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

session_start();

// ✅ Kiểm tra quyền
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') { 
    header("Location: ../../login.php"); 
    exit; 
}

$studentId = (int)$_SESSION['user_id'];
$quiz_id = (int)($_GET['id'] ?? 0);

// ✅ Lấy thông tin quiz
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id=?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) die("⛔ Quiz không tồn tại!");

// ✅ Lấy danh sách câu hỏi và các lựa chọn
$stmt = $pdo->prepare("
    SELECT q.id AS qid, q.question_text, q.question_type,
           o.id AS oid, o.option_text
    FROM quiz_questions q
    LEFT JOIN quiz_options o ON q.id = o.question_id
    WHERE q.quiz_id=?
    ORDER BY q.id
");
$stmt->execute([$quiz_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Gom nhóm câu hỏi và các option
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
            'text' => $r['option_text']
        ];
    }
}

$score = null;

// ✅ Khi sinh viên nộp bài
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correct = 0;
    $total = count($questions);

    // ✅ Chuẩn bị statement kiểm tra đáp án 1 lần (tối ưu)
    $stmtCheck = $pdo->prepare("SELECT is_correct FROM quiz_options WHERE id=?");

    foreach ($questions as $q) {
        $answer = $_POST['answer_'.$q['id']] ?? null;
        if ($answer) {
            $stmtCheck->execute([$answer]);
            $opt = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($opt && $opt['is_correct']) {
                $correct++;
            }
        }
    }

    // ✅ Tính điểm
    $score = round(($correct / max($total, 1)) * $quiz['max_score'], 2);

    // ✅ Lưu kết quả (bọc try/catch để xử lý lỗi)
    try {
        $stmt = $pdo->prepare("INSERT INTO quiz_attempts (quiz_id, student_id, score) VALUES (?,?,?)");
        $stmt->execute([$quiz_id, $studentId, $score]);
    } catch (PDOException $e) {
        die("❌ Lỗi khi lưu kết quả quiz: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>❓ Làm Quiz - <?= htmlspecialchars($quiz['title']) ?></title>
  <link rel="stylesheet" href="../../style.css">
  <style>
    .quiz-box {background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
    .question {margin-bottom:20px;}
    .question h3 {margin-bottom:10px; color:#2c3e50;}
    .options label {display:block; margin:6px 0; cursor:pointer;}
    .btn {background:#27ae60; color:#fff; padding:10px 16px; border:none; border-radius:6px; cursor:pointer;}
    .btn:hover {background:#1e8449;}
    .result {margin-top:20px; padding:15px; background:#ecf7ff; border-left:5px solid #2980b9;}
    .btn-back {
        display:inline-block;
        margin:10px 0 20px;
        background:#7f8c8d;
        color:#fff;
        padding:8px 14px;
        border-radius:6px;
        text-decoration:none;
        font-size:14px;
    }
    .btn-back:hover {background:#636e72;}
  </style>
</head>
<body>
<?php include "../includes/sidebar2.php"; ?>
<div class="main">
  <h2>❓ Làm Quiz: <?= htmlspecialchars($quiz['title']) ?></h2>

  <?php if ($score !== null): ?>
    <div class="result">
      <b>Kết quả:</b> Bạn đạt <?= $score ?>/<?= $quiz['max_score'] ?> điểm 🎉
    </div>
  <?php else: ?>
    <form method="post" class="quiz-box">
      <?php foreach ($questions as $q): ?>
        <div class="question">
          <h3><?= htmlspecialchars($q['text']) ?></h3>
          <div class="options">
            <?php foreach ($q['options'] as $opt): ?>
              <label>
                <input type="radio" name="answer_<?= $q['id'] ?>" value="<?= $opt['id'] ?>">
                <?= htmlspecialchars($opt['text']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <button type="submit" class="btn">📤 Nộp bài</button>
      <a href="quiz_list.php" class="btn-back">⬅ Quay lại</a>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
