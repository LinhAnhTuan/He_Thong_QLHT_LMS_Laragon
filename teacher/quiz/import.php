<?php
require_once("../../config/db.php");
require '../../vendor/autoload.php'; 

session_start();

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

// ✅ Kiểm tra quyền teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacherId = (int)$_SESSION['user_id'];
$quiz_id   = (int)($_GET['quiz_id'] ?? 0);

// ✅ Kiểm tra quiz có thuộc giáo viên không
$stmt = $pdo->prepare("
    SELECT q.*, c.title as course_title 
    FROM quizzes q
    JOIN courses c ON q.course_id = c.id
    WHERE q.id=? AND c.teacher_id=?");
$stmt->execute([$quiz_id, $teacherId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die("⛔ Quiz không tồn tại hoặc bạn không có quyền.");
}

$error = "";
$success = "";

// ✅ Hàm thêm câu hỏi vào DB
function insertQuestion($pdo, $quiz_id, $questionText, $questionType, $options = [], $correctAnswers = []) {
    $stmt = $pdo->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type, correct_answer) VALUES (?, ?, ?, ?)");
    $correctAnswerText = ($questionType === 'short_answer') ? ($correctAnswers[0] ?? null) : null;
    $stmt->execute([$quiz_id, $questionText, $questionType, $correctAnswerText]);
    $question_id = $pdo->lastInsertId();

    if ($questionType === 'multiple_choice') {
        foreach ($options as $i => $opt) {
            $isCorrect = in_array($i, $correctAnswers) ? 1 : 0;
            $stmtOpt = $pdo->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
            $stmtOpt->execute([$question_id, $opt, $isCorrect]);
        }
    }
}

// ✅ Xử lý upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
        $error = "⚠️ Vui lòng chọn file hợp lệ.";
    } else {
        $fileTmp  = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // === Thư mục lưu file import ===
        $uploadDir = "../../uploads/quiz_imports/";
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileSaveName = time() . "_" . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $fileName);
        $targetFile = $uploadDir . $fileSaveName;

        // === Lưu file gốc ===
        if (!move_uploaded_file($fileTmp, $targetFile)) {
            $error = "❌ Không thể lưu file import.";
        } else {
            $pdo->beginTransaction();
            try {
                // ===== CSV =====
                if ($fileType === 'csv') {
                    if (($handle = fopen($targetFile, "r")) !== false) {
                        $headers = fgetcsv($handle, 1000, ",");
                        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                            $row = array_combine($headers, $data);
                            if (!$row) continue;

                            if ($row['question_type'] === 'multiple_choice') {
                                $options = [];
                                $corrects = [];
                                for ($i = 1; $i <= 10; $i++) {
                                    if (!empty($row["option_$i"])) {
                                        $options[] = $row["option_$i"];
                                        if (!empty($row["is_correct_$i"]) && $row["is_correct_$i"] == 1) {
                                            $corrects[] = count($options)-1;
                                        }
                                    }
                                }
                                insertQuestion($pdo, $quiz_id, $row['question_text'], 'multiple_choice', $options, $corrects);
                            } else {
                                insertQuestion($pdo, $quiz_id, $row['question_text'], 'short_answer', [], [$row['option_1']]);
                            }
                        }
                        fclose($handle);
                    }

                // ===== Excel =====
                } elseif (in_array($fileType, ['xls','xlsx'])) {
                    $spreadsheet = IOFactory::load($targetFile);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray(null, true, true, true);

                    $headers = array_map('strtolower', $rows[1]);
                    for ($r = 2; $r <= count($rows); $r++) {
                        $row = [];
                        foreach ($headers as $col => $key) {
                            $row[$key] = $rows[$r][$col] ?? null;
                        }
                        if (!$row['question_text']) continue;

                        if ($row['question_type'] === 'multiple_choice') {
                            $options = [];
                            $corrects = [];
                            for ($i = 1; $i <= 10; $i++) {
                                if (!empty($row["option_$i"])) {
                                    $options[] = $row["option_$i"];
                                    if (!empty($row["is_correct_$i"]) && $row["is_correct_$i"] == 1) {
                                        $corrects[] = count($options)-1;
                                    }
                                }
                            }
                            insertQuestion($pdo, $quiz_id, $row['question_text'], 'multiple_choice', $options, $corrects);
                        } else {
                            insertQuestion($pdo, $quiz_id, $row['question_text'], 'short_answer', [], [$row['option_1']]);
                        }
                    }

                // ===== Word =====
                } elseif ($fileType === 'docx') {
                    $phpWord = WordIOFactory::load($targetFile, 'Word2007');
                    $questionText = "";
                    $options = [];
                    $corrects = [];

                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            $text = "";

                            if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                                $text = trim($element->getText());
                            } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                                foreach ($element->getElements() as $child) {
                                    if ($child instanceof \PhpOffice\PhpWord\Element\Text) {
                                        $text .= $child->getText();
                                    }
                                }
                                $text = trim($text);
                            }

                            if (!$text) continue;

                            if (preg_match('/^Câu\s*\d+[:\-]/ui', $text)) {
                                if ($questionText) {
                                    insertQuestion($pdo, $quiz_id, $questionText, 'multiple_choice', $options, $corrects);
                                    $options = [];
                                    $corrects = [];
                                }
                                $questionText = preg_replace('/^Câu\s*\d+[:\-]\s*/ui', '', $text);
                            } elseif (preg_match('/^[A-D]\./ui', $text)) {
                                $options[] = substr($text, 2);
                            } elseif (stripos($text, 'Đáp án') === 0) {
                                if (preg_match('/Đáp án\s*[:\-]?\s*([A-D])/ui', $text, $m)) {
                                    $ansIndex = ord(strtoupper($m[1])) - ord('A');
                                    $corrects[] = $ansIndex;
                                }
                            }
                        }
                    }

                    if ($questionText) {
                        insertQuestion($pdo, $quiz_id, $questionText, 'multiple_choice', $options, $corrects);
                    }
                } else {
                    throw new Exception("Định dạng file không được hỗ trợ.");
                }

                // ✅ Lưu đường dẫn file vào DB
                $relativePath = "uploads/quiz_imports/" . $fileSaveName;
                $stmt = $pdo->prepare("UPDATE quizzes SET import_file=? WHERE id=?");
                $stmt->execute([$relativePath, $quiz_id]);

                $pdo->commit();
                $success = "✅ Import thành công và file đã được lưu!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "❌ Lỗi khi import: " . $e->getMessage();
            }
        }
    }
}

$avatar = !empty($_SESSION['avatar']) ? "../../" . $_SESSION['avatar'] : "../../uploads/default.png";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>📥 Import Quiz</title>
  <link rel="stylesheet" href="../../style.css">
</head>
<body>
<?php include "../includes/sidebar_teacher2.php"; ?>

<div class="content">
  <header class="header">
    <div class="page-title"><b><?= htmlspecialchars($_SESSION['name']); ?></b></div>
    <div class="user">
      <img src="<?= htmlspecialchars($avatar); ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%">
      <span>Giảng viên</span>
      <a href="../../logout.php">🚪 Đăng xuất</a>
    </div>
  </header>

  <div class="main">
    <h2>📥 Import câu hỏi cho quiz: <?= htmlspecialchars($quiz['title']) ?></h2>
    <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form">
      <label>Chọn file (CSV, Excel, Word):</label>
      <input type="file" name="file" accept=".csv,.xls,.xlsx,.docx" required>
      <button type="submit" class="btn">📤 Import</button>
      <a href="list.php?course_id=<?= $quiz['course_id'] ?>" class="btn" style="background:#2980b9;">⬅ Quay lại</a>
    </form>

    <?php if (!empty($quiz['import_file'])): ?>
      <p>📂 Quiz đã thêm: 
        <a href="../../<?= htmlspecialchars($quiz['import_file']) ?>" download>
          <?= basename($quiz['import_file']) ?>
        </a>
      </p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
<style>
.page-title{ font-size:22px; }
.success{ background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin:10px 0; }
.error{ background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin:10px 0; }
</style>
