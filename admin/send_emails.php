<?php
require '../config.php';          // Kết nối database
require './config_email.php';     // Cấu hình email
require '../vendor/autoload.php'; // Nếu dùng Composer

date_default_timezone_set('Asia/Ho_Chi_Minh');

$now = date('H:i'); // Lấy giờ hiện tại (giờ và phút)
$logFile = 'email_log.txt';

// Lấy thông tin người dùng và thời gian check-in, check-out từ bảng EmailConfig
$stmt = $conn->prepare("
    SELECT u.Email, ec.CheckInTime, ec.CheckOutTime, u.Status
    FROM User u
    JOIN EmailConfig ec ON u.Id = ec.UserId
    WHERE u.RoleID = 2
");
$stmt->execute();

while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $message = ''; // Khởi tạo biến message để ghi log

    // Kiểm tra nếu user có status inactive và ghi vào log
    if ($user['Status'] != 'active') {
        $message = "User {$user['Email']} is INACTIVE. No email sent.";
    } else {
        // Tính thời gian gửi email trước CheckInTime và CheckOutTime 15 phút
        $checkInReminderTime = date('H:i', strtotime($user['CheckInTime']));
        $checkOutReminderTime = date('H:i', strtotime($user['CheckOutTime']));

        // Kiểm tra nếu thời gian hiện tại trùng với thời gian gửi nhắc nhở check-in
        if ($now == $checkInReminderTime) {
            $emailContent = [
                'subject' => 'Reminder: Time to Check-In',
                'body' => '
                    <div style="font-family: Arial, sans-serif; padding:20px; border: 1px solid #ddd; border-radius: 8px; max-width: 600px; margin: 0 auto;">
                        <img src="https://admin.ems.com.vn/filemedia/company/EMS_LOGO1.1721463252.png" 
                        alt="EMS Logo" style="max-width: 100px; height: auto; display:block; margin-bottom: 20px;" />
                        <p style="font-size:16px;">Dear Employee,</p>
                        <p style="font-size:16px;">It’s time to check in for your work shift. Please ensure you log in on time.</p>

                        <!-- Thêm ảnh minh họa ở đây -->
                        <img src="https://vnptmedia.vn/file/8a10a0d36ccebc89016cf4ff8bd72177/old_image/201704/original/images1929837_005.jpg" 
                            alt="Check-out Reminder" style="max-width: 100%; height: auto; margin-top: 20px;" />
                        
                        <p style="font-size:16px;">Thank you,<br>EMS Team</p>
                        <footer style="font-size:14px; color:#888; text-align:center; margin-top: 20px;">
                            <p>For any questions, please contact us at support@example.com</p>
                        </footer>
                    </div>'
            ];
            if (sendEmail($user['Email'], $emailContent['subject'], $emailContent['body'])) {
                $message = "Email sent to {$user['Email']} (Check-In Reminder)";
            } else {
                $message = "Failed to send email to {$user['Email']} (Check-In Reminder)";
            }
        }

        // Kiểm tra nếu thời gian hiện tại trùng với thời gian gửi nhắc nhở check-out
        if ($now == $checkOutReminderTime) {
            $emailContent = [
                'subject' => 'Reminder: Time to Check-Out',
                'body' => '
                    <div style="font-family: Arial, sans-serif; padding:20px; border: 1px solid #ddd; border-radius: 8px; max-width: 600px; margin: 0 auto;">
                        <img src="https://admin.ems.com.vn/filemedia/company/EMS_LOGO1.1721463252.png" 
                        alt="EMS Logo" style="max-width: 100px; height: auto; display:block; margin-bottom: 20px;" />
                        <p style="font-size:16px;">Dear Employee,</p>
                        <p style="font-size:16px;">It’s time to check out. Please remember to log your work hours.</p>

                        <!-- Thêm ảnh minh họa ở đây -->
                        <img src="https://vnptmedia.vn/file/8a10a0d36ccebc89016cf4ff8bd72177/old_image/201704/original/images1929837_005.jpg" 
                            alt="Check-out Reminder" style="max-width: 100%; height: auto; margin-top: 20px;" /> 

                        <p style="font-size:16px;">Thank you,<br>EMS Team</p>
                        <footer style="font-size:14px; color:#888; text-align:center; margin-top: 20px;">
                            <p>For any questions, please contact us at support@example.com</p>
                        </footer>
                    </div>'
            ];
            if (sendEmail($user['Email'], $emailContent['subject'], $emailContent['body'])) {
                $message = "Email sent to {$user['Email']} (Check-Out Reminder)";
            } else {
                $message = "Failed to send email to {$user['Email']} (Check-Out Reminder)";
            }
        }
    }

    // Ghi log kết quả gửi email chỉ nếu message không rỗng
    // if (!empty($message)) {
    //     file_put_contents($logFile, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
    // }
}
