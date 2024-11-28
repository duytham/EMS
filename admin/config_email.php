<?php
$emailConfig = [
    'host' => 'smtp.gmail.com',
    'username' => 'duytham026@gmail.com', // Email gửi
    'password' => 'kvpixhjxgcryxwcx',     // Mật khẩu ứng dụng Gmail
    'from_email' => 'duytham026@gmail.com',
    'from_name' => 'EMS Notification',
    'port' => 587,
];

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body)
{
    global $emailConfig;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $emailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['username'];
        $mail->Password   = $emailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $emailConfig['port'];

        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>