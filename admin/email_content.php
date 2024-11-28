<?php

function getEmailContent($type)
{
    // Cấu trúc chung của email
    $header = '<div style="background-color:#4CAF50; color:white; padding:20px; text-align:center; font-size:24px; font-family: Arial, sans-serif;">
                    <h1>EMS Notification</h1>
                </div>';

    $footer = '<div style="background-color:#f1f1f1; text-align:center; padding:10px; font-size:14px;">
                    <p>Thank you for using EMS. If you have any questions, feel free to contact us.</p>
                    <p><small>&copy; 2024 EMS Team. All rights reserved.</small></p>
                </div>';

    if ($type === 'checkin') {
        $body = '
            <div style="font-family: Arial, sans-serif; padding:20px; border: 1px solid #ddd; border-radius: 8px; max-width: 600px; margin: 0 auto;">
                <img src="https://www.google.com/url?sa=i&url=https%3A%2F%2Fems.com.vn%2F&psig=AOvVaw1gIXwYKiBWEPHvOI52AtdS&ust=1732877063509000&source=images&cd=vfe&opi=89978449&ved=0CBQQjRxqFwoTCOj2oKHs_okDFQAAAAAdAAAAABAE" 
                alt="EMS Logo" style="max-width: 100px; height: auto; display:block; margin-bottom: 20px;" />
                <p style="font-size:16px;">Dear Employee,</p>
                <p style="font-size:16px;">This is a reminder to log your check-in for today.</p>
                <p style="font-size:16px;">Please make sure to log your time promptly to avoid any discrepancies.</p>
                <p style="font-size:16px;">Thank you,<br>EMS Team</p>
                <footer style="font-size:14px; color:#888; text-align:center; margin-top: 20px;">
                    <p>For any questions, please contact us at support@example.com</p>
                </footer>
            </div>';
    } elseif ($type === 'checkout') {
        $body = '
            <div style="font-family: Arial, sans-serif; padding:20px; border: 1px solid #ddd; border-radius: 8px; max-width: 600px; margin: 0 auto;">
                <img src="https://www.google.com/url?sa=i&url=https%3A%2F%2Fems.com.vn%2F&psig=AOvVaw1gIXwYKiBWEPHvOI52AtdS&ust=1732877063509000&source=images&cd=vfe&opi=89978449&ved=0CBQQjRxqFwoTCOj2oKHs_okDFQAAAAAdAAAAABAE" 
                alt="EMS Logo" style="max-width: 100px; height: auto; display:block; margin-bottom: 20px;" />
                <p style="font-size:16px;">Dear Employee,</p>
                <p style="font-size:16px;">This is a reminder to log your check-out for today.</p>
                <p style="font-size:16px;">Please make sure to log your time promptly to avoid any discrepancies.</p>
                <p style="font-size:16px;">Thank you,<br>EMS Team</p>
                <footer style="font-size:14px; color:#888; text-align:center; margin-top: 20px;">
                    <p>For any questions, please contact us at support@example.com</p>
                </footer>
            </div>';
    } else {
        $body = '';
    }

    return [
        'header' => $header,
        'body' => $body,
        'footer' => $footer
    ];
}
