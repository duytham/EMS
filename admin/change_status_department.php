<?php
include "../config.php";

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];

    // Cập nhật trạng thái
    $sql = "UPDATE Department SET Status = :status WHERE Id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        // Chuyển hướng về trang danh sách với thông báo thành công
        header("Location: manage_department.php?success=Status changed successfully.");
    } else {
        // Chuyển hướng về trang danh sách với thông báo lỗi
        header("Location: manage_department.php?error=Failed to change status.");
    }
    exit();
}
?>