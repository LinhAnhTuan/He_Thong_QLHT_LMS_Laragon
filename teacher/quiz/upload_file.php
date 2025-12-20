<?php
require_once("../../config/db.php");

$targetDir = "../../uploads/files/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if (isset($_FILES["file"]) && $_FILES["file"]["error"] == 0) {
    $fileName = basename($_FILES["file"]["name"]);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedTypes = ["doc", "docx", "xls", "xlsx", "pdf"];

    // Kiểm tra extension
    if (in_array($fileType, $allowedTypes)) {
        // Kiểm tra dung lượng
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($_FILES["file"]["size"] > $maxSize) {
            die("⚠️ File quá lớn (tối đa 5MB).");
        }

        // Chuẩn hóa tên file
        $safeName = preg_replace("/[^A-Za-z0-9_\-\.]/", "_", $fileName);
        $newFileName = time() . "_" . $safeName;
        $targetFile = $targetDir . $newFileName;

        // Upload
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
            echo "✅ Upload thành công: <a href='$targetFile'>$newFileName</a>";

            // 👉 Lưu DB (ví dụ cho lesson)
            /*
            $stmt = $pdo->prepare("INSERT INTO lessons (section_id, title, content_type, content_link) VALUES (?, ?, ?, ?)");
            $stmt->execute([$section_id, $title, 'file', $targetFile]);
            */
        } else {
            echo "❌ Lỗi khi lưu file.";
        }
    } else {
        echo "⚠️ Chỉ cho phép upload file Word, Excel hoặc PDF.";
    }
} else {
    echo "⚠️ Không có file nào được chọn.";
}
