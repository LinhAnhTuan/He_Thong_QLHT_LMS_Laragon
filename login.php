<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config/db.php";  // ✅ Đường dẫn tuyệt đối
require_once __DIR__ . "/includes/log_helper.php"; // ✅ Ghi log khi cần

function getSetting($pdo, $key, $default = "") {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val ?: $default;
}

// ✅ Lấy thông tin cấu hình site
$site_name     = getSetting($pdo, 'site_name', "LMS – Học trực tuyến");
$logo          = getSetting($pdo, 'logo', "uploads/default-logo.png");
$contact_email = getSetting($pdo, 'contact_email', "support@example.com");
$contact_phone = getSetting($pdo, 'contact_phone', "0123 456 789");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, role, name, password_hash, avatar FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {

        // ✅ Lưu session đăng nhập
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']   = $user['role'];
        $_SESSION['name']   = $user['name'];
        $_SESSION['avatar'] = $user['avatar'];

        // ✅ Ghi log hành động đăng nhập
        addLog($pdo, $user['id'], 'login', 'Đăng nhập thành công');

        // ✅ Điều hướng theo vai trò
        if ($user['role'] === 'admin') {
            header("Location: admin/index.php");
        } elseif ($user['role'] === 'teacher') {
            header("Location: teacher/courses.php");
        } else {
            header("Location: student/courses.php");
        }
        exit;

    } else {
        $error = "❌ Sai email hoặc mật khẩu!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($site_name) ?> – Đăng nhập</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-card">
      <div class="login-left">
        <div class="brand">
          <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" />
          <h1><?= htmlspecialchars($site_name) ?></h1>
        </div>
        <p class="desc">
          Nền tảng học tập trực tuyến hỗ trợ khóa học, bài giảng, quiz, bài tập và chứng chỉ số.  
          Học mọi lúc – mọi nơi cùng giảng viên chất lượng.
        </p>
        <ul class="features">
          <li>📘 Quản lý khóa học & lớp học</li>
          <li>🧩 Quiz & bài tập tương tác</li>
          <li>🎓 Chứng chỉ học tập</li>
        </ul>
      </div>

      <div class="login-right">
        <h2>Đăng nhập tài khoản</h2>
        <?php if (!empty($error)): ?>
          <div class="alert"><?= $error ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="form-group">
            <label for="lemail">Email</label>
            <input id="lemail" name="email" type="email" placeholder="you@example.com" required />
          </div>
          <div class="form-group">
            <label for="lpassword">Mật khẩu</label>
            <input id="lpassword" name="password" type="password" minlength="6" placeholder="••••••••" required />
          </div>
          <button type="submit" class="btn">Đăng nhập</button>
        </form>
        <p class="contact">📧 <?= htmlspecialchars($contact_email) ?> | ☎ <?= htmlspecialchars($contact_phone) ?></p>
      </div>
    </div>
  </div>
</body>
</html>


<style>
:root {
  --primary: #0ea5e9;
  --primary-light: #dbeafe;
  --text: #1e293b;
  --muted: #64748b;
  --radius: 14px;
}

body {
  margin: 0;
  font-family: 'Inter', sans-serif;
  background: 
    url('uploads/background.jpg') center/cover no-repeat;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
  color: #1e293b; /* màu chữ tối học thuật */
  backdrop-filter: blur(2px);
}
.login-wrapper {
  width: 100%;
  max-width: 940px;
  padding: 20px;
}

.login-card {
  display: flex;
  background: #fff;
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

/* Cột trái */
.login-left {
  flex: 1;
  background: linear-gradient(135deg, #0ea5e9, #2563eb);
  color: #fff;
  padding: 48px 36px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.login-left .brand {
  text-align: center;
  margin-bottom: 24px;
}

.login-left img {
  max-height: 60px;
  margin-bottom: 12px;
}

.login-left h1 {
  font-size: 22px;
  font-weight: 600;
  margin: 0;
}

.login-left .desc {
  font-size: 15px;
  opacity: 0.9;
  text-align: center;
  margin-bottom: 24px;
  line-height: 1.6;
}

.features {
  list-style: none;
  padding: 0;
  margin: 0;
  line-height: 1.8;
  font-size: 14px;
  opacity: 0.95;
}

/* Cột phải */
.login-right {
  flex: 1;
  padding: 56px 48px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.login-right h2 {
  font-size: 24px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 28px;
  text-align: center;
}

.form-group {
  margin-bottom: 18px;
}

label {
  display: block;
  font-size: 14px;
  color: var(--muted);
  margin-bottom: 6px;
}

input {
  width: 100%;
  padding: 12px 14px;
  font-size: 15px;
  border: 1px solid #cbd5e1;
  border-radius: var(--radius);
  transition: border-color 0.2s, box-shadow 0.2s;
}

input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 2px var(--primary-light);
}

.btn {
  width: 100%;
  padding: 12px;
  background: var(--primary);
  color: #fff;
  font-weight: 600;
  border: none;
  border-radius: var(--radius);
  cursor: pointer;
  transition: background 0.2s, transform 0.1s;
  margin-top: 8px;
}

.btn:hover {
  background: #1d4ed8;
  transform: scale(1.02);
}

.alert {
  background: #fee2e2;
  color: #b91c1c;
  padding: 10px 14px;
  border-radius: var(--radius);
  font-size: 14px;
  margin-bottom: 18px;
  text-align: center;
}

.contact {
  margin-top: 24px;
  text-align: center;
  font-size: 13px;
  color: var(--muted);
}

@media (max-width: 768px) {
  .login-card {
    flex-direction: column;
  }
  .login-left, .login-right {
    padding: 32px 24px;
  }
}
</style>
