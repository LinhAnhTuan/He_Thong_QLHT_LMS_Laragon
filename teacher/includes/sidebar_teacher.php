<?php
$currentPath = $_SERVER['PHP_SELF']; // Lấy đường dẫn tương đối
$currentPage = basename($currentPath);
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <img src="../uploads/teacher.jpg" alt="Avatar" class="avatar">
  </div>
    <nav class="menu">
        <a href="courses.php" class="menu-item <?= (strpos($currentPath, 'courses.php') !== false) ? 'active' : '' ?>">📚 Khóa học của tôi</a>
        <a href="lessons/list.php" class="menu-item <?= (strpos($currentPath, '/lessons/') !== false) ? 'active' : '' ?>">📖 Quản lý bài học</a>
        <a href="quiz/list.php" class="menu-item <?= (strpos($currentPath, '/quiz/') !== false) ? 'active' : '' ?>">❓ Quản lý Quiz</a>
        <a href="assignments/list.php" class="menu-item <?= (strpos($currentPath, '/assignments/') !== false) ? 'active' : '' ?>">📝 Bài tập & Chấm điểm</a>
        <a href="schedule/list.php" class="menu-item <?= (strpos($currentPath, '/schedule/') !== false) ? 'active' : '' ?>">📅 Lịch học</a>
        <a href="notifications/list.php" class="menu-item <?= (strpos($currentPath, '/notifications/') !== false) ? 'active' : '' ?>">🔔 Thông báo</a>
        <a href="students/list.php" class="menu-item <?= (strpos($currentPath, '/students/') !== false) ? 'active' : '' ?>">👥 Sinh viên</a>
        <a href="profile/edit.php" class="menu-item <?= (strpos($currentPath, '/profile/') !== false) ? 'active' : '' ?>">👤 Hồ sơ cá nhân</a>
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
