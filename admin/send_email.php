<?php
include '../config.php'; // Kết nối cơ sở dữ liệu

require_once 'emailConfig.php'; // Đảm bảo đúng đường dẫn
include 'email_content.php'; // Import nội dung email

require '../vendor/autoload.php'; // Nếu dùng PHPMailer, tải qua Composer

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $successCount = 0;
    $failCount = 0;

    // Lấy danh sách email từ bảng emailConfig
    $query = "SELECT u.Email, u.FullName, e.CheckInTime, e.CheckOutTime 
              FROM emailConfig e 
              JOIN user u ON e.UserID = u.Id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($emails)) {
        echo json_encode(["message" => "No email configurations found."]);
        exit;
    }

    $currentTime = new DateTime();

    foreach ($emails as $email) {
        $checkInTime = new DateTime($email['CheckInTime']);
        $checkOutTime = new DateTime($email['CheckOutTime']);
        $employeeName = $email['FullName'];

        // Khoảng thời gian ±5 phút
        $interval = new DateInterval('PT5M');
        $checkInWindowStart = (clone $checkInTime)->sub($interval);
        $checkInWindowEnd = (clone $checkInTime)->add($interval);
        $checkOutWindowStart = (clone $checkOutTime)->sub($interval);
        $checkOutWindowEnd = (clone $checkOutTime)->add($interval);

        $emailContent = null;

        if ($currentTime >= $checkInWindowStart && $currentTime <= $checkInWindowEnd) {
            $emailContent = EmailContent::getCheckInEmailContent($employeeName);
        } elseif ($currentTime >= $checkOutWindowStart && $currentTime <= $checkOutWindowEnd) {
            $emailContent = EmailContent::getCheckOutEmailContent($employeeName);
        }

        if ($emailContent) {
            try {
                // Debugging: Print email details before sending
                error_log("Sending email to: {$email['Email']}, Subject: {$emailContent['subject']}");

                // Send the email using the EmailConfig::sendEmail function
                EmailConfig::sendEmail(
                    $email['Email'],
                    $employeeName,
                    $emailContent['subject'],
                    $emailContent['body']
                );
                $successCount++;
            } catch (Exception $e) {
                $failCount++;

                // Enhanced error logging
                error_log("Email failed to send to {$email['Email']}: " . $e->getMessage());
                // Log more details about the error if available
                error_log("Stack trace: " . $e->getTraceAsString());
            }
        }
    }

    // Trả về kết quả
    echo json_encode([
        "message" => "Emails sent successfully: $successCount. Failed: $failCount."
    ]);
    exit;
}
