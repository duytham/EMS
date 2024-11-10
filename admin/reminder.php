<?php
// Đảm bảo bạn đã định nghĩa hàm sendReminder() trong file này
function sendReminder($email, $name, $type, $time, $userId, $conn) {
    // Kiểm tra nếu email đã được gửi trong ngày hiện tại cho loại check-in/check-out tương ứng
    $today = date('Y-m-d');
    $query = "SELECT * FROM checkinout WHERE UserID = :userId AND ActionType = :type AND DATE(LogDate) = :today AND email_sent = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([':userId' => $userId, ':type' => $type, ':today' => $today]);
    
    if ($stmt->rowCount() > 0) {
        echo "Email đã được gửi cho nhân viên $name trong ngày hôm nay cho loại $type.";
        return; // Dừng gửi nếu đã có email gửi trước đó
    }

    // Tạo nội dung email
    $subject = ucfirst($type) . " Reminder";
    $message = "Dear $name,\n\nThis is a reminder for your $type time at $time.\n\nThank you.";

    // Gửi email và kiểm tra trạng thái
    if (EmailConfig::sendEmail($email, $name, $subject, $message)) {
        // Nếu gửi thành công, cập nhật trường email_sent
        $updateQuery = "UPDATE checkinout SET email_sent = 1 WHERE UserID = :userId AND ActionType = :type AND DATE(LogDate) = :today";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([':userId' => $userId, ':type' => $type, ':today' => $today]);
        
        echo "Email $type reminder đã được gửi thành công cho $name.";
    } else {
        echo "Lỗi khi gửi email.";
    }
}

?>
