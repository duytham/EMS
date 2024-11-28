<?php
require 'config_email.php';

$email = 'duynkhe163378@fpt.edu.vn'; // Email nhận thử
$subject = 'Test Email';
$body = '<p>This is a test email from EMS project.</p>';

if (sendEmail($email, $subject, $body)) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email.";
}
?>
