<?php
require_once '../config.php'; // Kết nối đến file config.php

// Kiểm tra nếu có ID nhân viên và trạng thái mới được gửi từ URL
if (isset($_GET['id']) && isset($_GET['status'])) {
    $userId = $_GET['id'];
    $newStatus = $_GET['status'];

    try {
        // Cập nhật trạng thái của nhân viên
        $stmt = $conn->prepare("UPDATE `User` SET Status = :status WHERE Id = :id");
        $stmt->bindParam(':status', $newStatus);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();

        // Chuyển hướng về trang quản lý nhân viên với thông báo thành công
        header('Location: manage_employees.php?success=Employee status successfully changed!');
        exit();
    } catch (PDOException $e) {
        // Xử lý lỗi nếu có
        header('Location: manage_employees.php?error=Lỗi khi thay đổi trạng thái nhân viên: ' . $e->getMessage());
        exit();
    }
} else {
    // Nếu không có ID hoặc trạng thái mới, chuyển hướng về trang quản lý nhân viên
    header('Location: manage_employees.php');
    exit();
}
