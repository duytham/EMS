<?php
include_once '../config.php';
include_once './emailConfig.php';

// Lấy danh sách user có role employee
$query = "SELECT u.Id as UserID, u.FullName, u.Email, c.checkInTime, c.checkOutTime, c.email_sent
          FROM user u
          JOIN checkinout c ON u.Id = c.UserID
          WHERE u.RoleID = 2"; // Chỉ lấy user có role là employee

$stmt = $conn->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentTime = date("H:i"); // Giờ hiện tại (giờ và phút)

foreach ($employees as $employee) {
    // Kiểm tra thời gian gửi check-in reminder
    if ($employee['checkInTime'] === $currentTime && $employee['email_sent'] != 'checkin') {
        // Gửi email nhắc nhở check-in
        EmailConfig::sendEmail(
            $employee['Email'],
            $employee['FullName'],
            "Check-in Reminder",
            "Dear {$employee['FullName']}, please check in at the office."
        );

        // Cập nhật trạng thái đã gửi email check-in
        $updateQuery = "UPDATE checkinout SET email_sent = 'checkin' WHERE UserID = ? AND ActionType = 'checkin'";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$employee['UserID']]);
    }

    // Kiểm tra thời gian gửi check-out reminder
    if ($employee['checkOutTime'] === $currentTime && $employee['email_sent'] != 'checkout') {
        // Gửi email nhắc nhở check-out
        EmailConfig::sendEmail(
            $employee['Email'],
            $employee['FullName'],
            "Check-out Reminder",
            "Dear {$employee['FullName']}, please remember to check out."
        );

        // Cập nhật trạng thái đã gửi email check-out
        $updateQuery = "UPDATE checkinout SET email_sent = 'checkout' WHERE UserID = ? AND ActionType = 'checkout'";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$employee['UserID']]);
    }
}
?>
