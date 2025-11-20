<?php
/**
 * ========== 📘 log_helper.php ==========
 * Hệ thống ghi log tự động cho LMS
 * ======================================
 * Chỉ cần include file này ở đầu mỗi trang xử lý hoặc dashboard.
 */

if (!function_exists('addLog')) {
    function addLog($pdo, $userId, $actionType, $description) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action_type, description, ip_address)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $actionType, $description, $ip]);
        } catch (Exception $e) {
            // Không dừng hệ thống nếu log lỗi
        }
    }
}

/**
 * 🧠 Hàm tự động ghi log theo hành động phát hiện từ URL & method
 * Gợi ý: tự động hiểu các hành động như thêm, sửa, xóa, đăng nhập, đăng xuất, v.v.
 */
if (!function_exists('autoLogAction')) {
    function autoLogAction($pdo) {
        if (empty($_SESSION['user_id'])) return; // Chưa đăng nhập thì bỏ qua

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $actionType = null;
        $desc = null;

        // Nhận diện hành động qua URL
        if (stripos($uri, 'add') !== false && $method === 'POST') {
            $actionType = 'add';
            $desc = "Thêm dữ liệu vào " . basename($uri);
        } elseif (stripos($uri, 'edit') !== false && $method === 'POST') {
            $actionType = 'edit';
            $desc = "Cập nhật dữ liệu tại " . basename($uri);
        } elseif (stripos($uri, 'delete') !== false) {
            $actionType = 'delete';
            $desc = "Xóa dữ liệu tại " . basename($uri);
        } elseif (stripos($uri, 'login') !== false && $method === 'POST') {
            $actionType = 'login';
            $desc = "Đăng nhập hệ thống";
        } elseif (stripos($uri, 'logout') !== false) {
            $actionType = 'logout';
            $desc = "Đăng xuất hệ thống";
        } elseif (stripos($uri, 'enroll') !== false) {
            $actionType = 'enroll';
            $desc = "Đăng ký / hủy khóa học";
        }

        if ($actionType) {
            addLog($pdo, $_SESSION['user_id'], $actionType, $desc);
        }
    }
}
?>
