<?php
include_once 'emailConfig.php';

// Thông tin email thử nghiệm
$recipientEmail = 'duyproh6@gmail.com';
$recipientName = 'John Doe';
$subject = 'Test Email';
$body = 'Đây là một email thử nghiệm.';

// Gửi email
EmailConfig::sendEmail($recipientEmail, $recipientName, $subject, $body);
?>
