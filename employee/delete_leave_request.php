<?php
session_start();
include '../config.php';

if (isset($_POST['leave_id'])) {
    $leaveId = $_POST['leave_id'];

    // Truy vấn thông tin đơn nghỉ phép
    $stmt = $conn->prepare("
        SELECT UserId, LeaveDateStart, LeaveDateEnd 
        FROM LeaveRequest 
        WHERE Id = ? AND Status = 'Pending'
    ");
    $stmt->execute([$leaveId]);
    $leaveRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$leaveRequest) {
        $_SESSION['message'] = "Đơn nghỉ phép không tồn tại hoặc đã được xử lý.";
        header("Location: view_leave_requests.php");
        exit;
    }

    // Lấy thông tin cần thiết
    $userId = $leaveRequest['UserId'];
    $leaveDateStart = $leaveRequest['LeaveDateStart'];
    $leaveDateEnd = $leaveRequest['LeaveDateEnd'];

    // Tính số ngày nghỉ phép
    $leaveDays = (strtotime($leaveDateEnd) - strtotime($leaveDateStart)) / (60 * 60 * 24) + 1;

    // Khôi phục số ngày nghỉ phép
    $updateStmt = $conn->prepare("
        UPDATE LeaveConfig 
        SET UsedLeaveDays = UsedLeaveDays - ? 
        WHERE UserId = ? AND LeaveYear = YEAR(?)
    ");
    $updateStmt->execute([$leaveDays, $userId, $leaveDateStart]);

    // Xóa đơn nghỉ phép
    $deleteStmt = $conn->prepare("DELETE FROM LeaveRequest WHERE Id = ?");
    $deleteStmt->execute([$leaveId]);

    $_SESSION['message'] = "Đơn nghỉ phép đã được xóa và số ngày nghỉ đã được khôi phục.";
    header("Location: view_leave_requests.php");
    exit;
} else {
    header("Location: view_leave_requests.php");
    exit;
}
?>
