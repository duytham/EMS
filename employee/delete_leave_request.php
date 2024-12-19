<?php
session_start();
include '../config.php';

if (isset($_POST['leave_id'])) {
    $leaveId = $_POST['leave_id'];

    // Xóa đơn nghỉ phép
    $stmt = $conn->prepare("DELETE FROM LeaveRequest WHERE Id = ? AND Status = 'Pending'");
    $stmt->execute([$leaveId]);

    $_SESSION['message'] = "Đơn nghỉ phép đã được xóa.";
    header("Location: view_leave_requests.php");
    exit;
} else {
    header("Location: view_leave_requests.php");
    exit;
}
?>