<?php
require_once("../includes/log_helper.php");
autoLogAction($pdo); // ✅ Tự động ghi log

$file = $_GET['file'] ?? '';
$path = "../../" . $file;

if ($file && file_exists($path)) {
    header("Content-Disposition: attachment; filename=\"" . basename($path) . "\"");
    header("Content-Type: application/octet-stream");
    readfile($path);
    exit;
} else {
    die("⛔ File không tồn tại.");
}
