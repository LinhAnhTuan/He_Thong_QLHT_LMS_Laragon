<?php
$currentPath = $_SERVER['PHP_SELF'];      // Lấy đường dẫn tương đối đầy đủ
$currentPage = basename($currentPath);    // Tên file hiện tại

// Danh sách các trang con thuộc "Quản lý người dùng"
$userPages = ['admins.php', 'teachers.php', 'students.php'];
$isUserPage = in_array($currentPage, $userPages);
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <img src="../../uploads/admin.png" alt="Avatar" class="avatar">
  </div>
    <nav class="menu">
        <a href="../index.php" class="menu-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">📊 Thống kê</a>

        <!-- Quản lý người dùng -->
        <div class="submenu">
            <a href="#" class="menu-item has-submenu <?= $isUserPage ? 'active' : '' ?>">👥 Quản lý người dùng</a>
            <div class="submenu-items" style="<?= $isUserPage ? 'display:block;' : 'display:none;' ?>">
                <a href="../users/admin/admins.php" class="menu-item <?= ($currentPage == 'admins.php') ? 'active' : '' ?>">👑 Quản trị viên</a>
                <a href="../users/teacher/teachers.php" class="menu-item <?= ($currentPage == 'teachers.php') ? 'active' : '' ?>">👨‍🏫 Giảng viên</a>
                <a href="../users/student/students.php" class="menu-item <?= ($currentPage == 'students.php') ? 'active' : '' ?>">🎓 Sinh viên</a>
            </div>
        </div>
        <a href="../courses/list.php" class="menu-item <?= (strpos($currentPath, '/courses/') !== false) ? 'active' : '' ?>">📚 Quản lý khóa học</a>
        <a href="../quiz_teamplate/list.php" class="menu-item <?= (strpos($currentPath, '/quiz_teamplate/') !== false) ? 'active' : '' ?>">📄 Mẫu Quiz </a>
        <a href="../schedule/list.php" class="menu-item <?= (strpos($currentPath, '/schedule/') !== false)? 'active' : '' ?>">🗓️ Lịch học</a>
        <a href="../settings/general.php" class="menu-item <?= (strpos($currentPath, '/settings/') !== false) ? 'active' : '' ?>">⚙️ Cấu hình hệ thống</a>
        <a href="../log.php" class="menu-item <?= ($currentPage == 'log.php') ? 'active' : '' ?>">🧾 Nhật ký hệ thống</a>
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
  overflow: auto;
}
.sidebar a{
  color: black;
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
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* Menu item base */
.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 8px;
  color: #0b2340; /* dark text */
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.15s ease;
  margin-bottom: 0;
}

/* hover & active (these now win because .sidebar a:hover was removed) */
.menu-item:hover {
  background: #eef6ff;
  color: #007bff;
}

.menu-item.active {
  background: #dceeff;
  color: #007bff;
  font-weight: 600;
}

/* submenu styles */
.submenu-items {
  margin-left: 6px;
  margin-top: 6px;
  display: none;
  flex-direction: column;
}

/* make submenu-items visible when parent has .active (optional) */
.submenu .has-submenu.active + .submenu-items {
  display: flex;
}
</style>

<script>
// Toggle submenu bằng JS
document.addEventListener("DOMContentLoaded", function() {
    const submenuToggle = document.querySelector(".submenu .has-submenu");
    const submenuItems = document.querySelector(".submenu .submenu-items");

    if (submenuToggle) {
        submenuToggle.addEventListener("click", function(e) {
            e.preventDefault();
            this.classList.toggle("active");
            if (submenuItems.style.display === "block" || submenuItems.style.display === "flex") {
                submenuItems.style.display = "none";
            } else {
                submenuItems.style.display = "flex";
            }
        });

        // Nếu PHP đã set mở submenu (trang con), giữ nguyên
        <?php if ($isUserPage): ?>
            submenuToggle.classList.add("active");
            if (submenuItems) submenuItems.style.display = "flex";
        <?php endif; ?>
    }
});
</script>
