<?php
session_start();
include '../config.php';
include 'config_email.php'; // Gọi file cấu hình email

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveId = $_POST['leave_id'];
    $action = $_POST['action']; // Accept hoặc Reject
    $adminId = $_SESSION['user_id']; // Admin ID

    // Truy vấn thông tin đơn nghỉ phép và email nhân viên
    $stmt = $conn->prepare("
        SELECT lr.UserId, lr.LeaveDateStart, lr.LeaveDateEnd, lr.Reason, u.Email, u.FullName 
        FROM LeaveRequest lr
        JOIN User u ON lr.UserId = u.Id
        WHERE lr.Id = ?
    ");
    $stmt->execute([$leaveId]);
    $leaveRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$leaveRequest) {
        $_SESSION['message'] = "Đơn nghỉ phép không tồn tại.";
        header("Location: view_leave_request.php");
        exit;
    }

    // Lấy thông tin cần thiết
    $employeeEmail = $leaveRequest['Email'];
    $employeeName = $leaveRequest['FullName'];
    $leaveDateStart = $leaveRequest['LeaveDateStart'];
    $leaveDateEnd = $leaveRequest['LeaveDateEnd'];
    $reason = $leaveRequest['Reason'];

    // Cập nhật trạng thái đơn nghỉ phép
    if ($action === 'Accept') {
        $updateStmt = $conn->prepare("UPDATE LeaveRequest SET Status = 'Approved', ApprovedBy = ?, ApprovedAt = NOW() WHERE Id = ?");
        $updateStmt->execute([$adminId, $leaveId]);
        $statusMessage = "The leave request from $leaveDateStart to $leaveDateEnd is approved.";
    } elseif ($action === 'Reject') {
        $rejectStmt = $conn->prepare("UPDATE LeaveRequest SET Status = 'Rejected', ApprovedBy = ?, ApprovedAt = NOW() WHERE Id = ?");
        $rejectStmt->execute([$adminId, $leaveId]);

        // Khôi phục số ngày nghỉ phép
        $leaveDays = (strtotime($leaveDateEnd) - strtotime($leaveDateStart)) / (60 * 60 * 24) + 1;
        $restoreLeaveStmt = $conn->prepare("UPDATE LeaveConfig SET UsedLeaveDays = UsedLeaveDays - ? WHERE UserId = ? AND LeaveYear = YEAR(?)");
        $restoreLeaveStmt->execute([$leaveDays, $leaveRequest['UserId'], $leaveDateStart]);

        $statusMessage = "The leave request from $leaveDateStart to $leaveDateEnd is rejected.";
    }

    // Gửi email thông báo
    $subject = "Leave Request Status Update";
    $body = "
        <html>
        <head>
            <style>
                .email-container {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .email-header {
                    background-color: #f8f9fa;
                    padding: 10px;
                    border-bottom: 1px solid #ddd;
                }
                .email-body {
                    padding: 20px;
                }
                .email-footer {
                    background-color: #f8f9fa;
                    padding: 10px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    font-size: 12px;
                    color: #777;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='email-header'>
                    <h2>Leave Request Status Update</h2>
                </div>
                <div class='email-body'>
                    <p>Dear $employeeName,</p>
                    <p>$statusMessage</p>
                    <p>Reason for leave: $reason</p>
                    <p>Best regards,<br>EMS Team</p>
                </div>
                <div class='email-footer'>
                    <p>This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    if (sendEmail($employeeEmail, $subject, $body)) {
        echo "Đã cập nhật trạng thái và gửi email thông báo thành công.";
    } else {
        echo "Cập nhật trạng thái thành công nhưng gửi email thất bại.";
    }

    // Lưu thông báo vào session và chuyển hướng
    $_SESSION['message'] = $statusMessage;
    header("Location: view_leave_request.php");
    exit;
}
?>
