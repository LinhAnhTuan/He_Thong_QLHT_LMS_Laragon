<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-header">
    
    <img src="../uploads/sv.png" alt="Avatar" class="avatar">
  </div>

  <nav class="menu">
    <a href="courses.php" class="menu-item <?= ($currentPage == 'courses.php') ? 'active' : '' ?>">🏠 <span>Trang chính</span></a>
    <a href="register_course.php" class="menu-item <?= ($currentPage == 'register_course.php') ? 'active' : '' ?>">📚 <span>Đăng ký khóa học</span></a>
    <a href="progress.php" class="menu-item <?= ($currentPage == 'progress.php') ? 'active' : '' ?>">🎯 <span>Tiến độ học tập</span></a>
    <a href="quiz/quiz_list.php" class="menu-item <?= ($currentPage == 'quiz_list.php') ? 'active' : '' ?>">❓ <span>Làm Quiz</span></a>
    <a href="assignment/submit.php" class="menu-item <?= ($currentPage == 'submit.php') ? 'active' : '' ?>">📂 <span>Nộp bài tập</span></a>
    <a href="certificate/certificates.php" class="menu-item <?= ($currentPage == 'certificates.php') ? 'active' : '' ?>">📜 <span>Chứng chỉ</span></a>
    <a href="schedule/schedules.php" class="menu-item <?= ($currentPage == 'schedules.php') ? 'active' : '' ?>">📅 <span>Lịch học</span></a>
    <a href="notification/notifications.php" class="menu-item <?= ($currentPage == 'notifications.php') ? 'active' : '' ?>">🔔 <span>Thông báo</span></a>
    <a href="profile.php" class="menu-item <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">👤 <span>Hồ sơ cá nhân</span></a>
  </nav>
</aside>

<style>
.sidebar {
  width: 230px;
  height: 100vh;
  background: #b0c9e3ff;
  border-right: 1px solid #282a30ff;
  padding: 25px 15px;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-sizing: border-box;
  font-family: "Inter", sans-serif;
  position: fixed;
}
.sidebar a{
    color: black;
}
.sidebar a:hover{
    background-color: #89929bff;
}
/* Header */
.sidebar-header {
  text-align: center;   
  margin-bottom: 25px;
}

.sidebar-header .avatar {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: #fff;
  object-fit: cover;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.sidebar-header h2 {
  font-size: 16px;
  color: #4ebddbff;
  margin-top: 10px;
  font-weight: 600;
}
.header{
  margin-left: 230px;   
}
.main {
  margin-left: 230px; 
  padding: 30px;
  box-sizing: border-box;
  height: 100vh;
  overflow-y: auto;
}
/* Menu */
.menu {
  width: 100%;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 8px;
  color: #3d8cdaff;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
  margin-bottom: 5px;
}

.menu-item:hover {
  background: #eef6ff;
  color: #007bff;
}

.menu-item.active {
  background: #dceeff;
  color: #007bff;
  font-weight: 600;
}

.menu-item span {
  flex: 1;
}
</style>
