<?php
require_once '../vendor/autoload.php'; // PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailConfig {
    public static function sendEmail($recipientEmail, $recipientName, $subject, $body) {
        $mail = new PHPMailer(true);
        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'duytham026@gmail.com'; // Thay bằng email của bạn
            $mail->Password = 'wgxt flvr gjbm kkxa';  // Thay bằng mật khẩu ứng dụng
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Sender and Recipient
            $mail->setFrom('duytham026@gmail.com', 'EMS - Timekeeping Reminder');
            $mail->addAddress($recipientEmail, $recipientName);

            // Email Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
        } catch (Exception $e) {
            echo "Error sending email: {$mail->ErrorInfo}";
        }
    }
}
