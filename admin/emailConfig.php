<?php
require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailConfig {
    public static function sendEmail($recipientEmail, $recipientName, $subject, $body) {
        $mail = new PHPMailer(true);
        
        try {
            // Cấu hình SMTP cho Gmail
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'duytham026@gmail.com';
            $mail->Password = 'vmon pjmp wufy oewl';  // Mật khẩu ứng dụng của Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Người gửi và người nhận
            $mail->setFrom('duytham026@gmail.com', 'EMS - Timekeeping Reminder');
            $mail->addAddress('duyproh6@gmail.com', 'Hello');
            
            // Nội dung email
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            // Gửi email
            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
?>
