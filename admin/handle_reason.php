<?php
// Kết nối cơ sở dữ liệu
include('config.php');

// Kiểm tra nếu có lý do được gửi đến
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reason'])) {
    // Lấy dữ liệu từ form
    $reason = trim($_POST['reason']);
    $attendanceId = $_GET['attendance_id']; // ID của check-in/check-out, lấy từ URL hoặc session

    // Kiểm tra nếu lý do không rỗng
    if (!empty($reason)) {
        // Cập nhật lý do vào cơ sở dữ liệu
        try {
            $sql = "UPDATE checkinout SET reason = :reason, status = 'Pending' WHERE Id = :attendance_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':reason', $reason);
            $stmt->bindParam(':attendance_id', $attendanceId);
            $stmt->execute();

            // Chuyển hướng về trang kiểm tra sau khi cập nhật
            header('Location: attendance_detail.php?attendance_id=' . $attendanceId);
            exit;
        } catch (PDOException $e) {
            echo "Lỗi: " . $e->getMessage();
        }
    } else {
        echo "Lý do không được để trống.";
    }
}
?>
