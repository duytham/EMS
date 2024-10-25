<?php
require_once '../config.php'; // Kết nối đến file config.php

// Kiểm tra nếu có ID nhân viên được gửi từ URL
if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    try {
        // Câu lệnh SQL để xóa nhân viên
        $stmt = $conn->prepare("DELETE FROM `User` WHERE Id = :id");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        // Chuyển hướng về trang quản lý nhân viên với thông báo thành công
        header('Location: manage_employees.php?success=Nhân viên đã được xóa thành công!');
        exit();
    } catch (PDOException $e) {
        // Xử lý lỗi nếu có
        header('Location: manage_employees.php?error=Lỗi khi xóa nhân viên: ' . $e->getMessage());
        exit();
    }
} else {
    // Nếu không có ID, chuyển hướng về trang quản lý nhân viên
    header('Location: manage_employees.php');
    exit();
}
