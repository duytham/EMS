<?php
include '../config.php'; // Kết nối cơ sở dữ liệu

// Lấy giờ hiện tại
$currentTime = date("H:i");

// Truy vấn lấy danh sách nhân viên
$query = "SELECT u.Id AS UserID, u.FullName, u.Email, e.CheckInTime, e.CheckOutTime
          FROM user u
          JOIN emailconfig e ON u.Id = e.UserID
          WHERE u.RoleID = 2 AND (e.CheckInTime = :currentTime OR e.CheckOutTime = :currentTime)";
$stmt = $conn->prepare($query);
$stmt->bindParam(':currentTime', $currentTime);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'emailConfig.php'; // Tệp cấu hình email

foreach ($employees as $employee) {
    // Xác định loại email
    if ($employee['CheckInTime'] === $currentTime) {
        $subject = "Check-in Reminder";
        $body = "Dear {$employee['FullName']}, please remember to check in at the office.";
    } elseif ($employee['CheckOutTime'] === $currentTime) {
        $subject = "Check-out Reminder";
        $body = "Dear {$employee['FullName']}, please remember to check out.";
    }

    // Gửi email
    try {
        EmailConfig::sendEmail($employee['Email'], $employee['FullName'], $subject, $body);
        echo "Email sent to {$employee['Email']}<br>";
    } catch (Exception $e) {
        echo "Error sending email to {$employee['Email']}: " . $e->getMessage() . "<br>";
    }
}
?>
