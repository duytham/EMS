<?php
include 'emailConfig.php'; // Đảm bảo đường dẫn đúng với file emailConfig.php

// Địa chỉ email và thông tin thử nghiệm
$recipientEmail = 'mnanhh0126@gmail.com'; // Thay bằng địa chỉ email của bạn
$recipientName = 'John Doe';            // Tên người nhận
$subject = 'EMS - Timekeeping Reminder';
$body = 'This is a test email sent from the EMS system. This is remind that you have not checked in today. Please check in as soon as possible.';

// Gửi email thử nghiệm
try {
    EmailConfig::sendEmail($recipientEmail, $recipientName, $subject, $body);
    echo "Test email sent successfully to {$recipientEmail}.";
} catch (Exception $e) {
    echo "Error: Email could not be sent. Details: " . $e->getMessage();
}
?>
