<?php
ob_start(); // Bắt đầu buffer để ngăn output ngoài JSON
require '../config.php';

header('Content-Type: application/json');
$response = ["success" => false, "message" => ""];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $employees = $_POST['employee'] ?? [];
        $logData = [];

        foreach ($employees as $employeeId) {
            // Lấy dữ liệu từ bảng liên quan
            $query = "
                SELECT 
                    u.FullName, 
                    s.Alias, 
                    s.DailySalary, 
                    COUNT(c.id) AS total_work_days,
                    SUM(c.valid = 1) AS valid_days,
                    SUM(c.valid = 0) AS invalid_days
                FROM users u
                JOIN salaryLevels s ON u.SalaryLevelID = s.Id
                LEFT JOIN checkinout c ON u.Id = c.UserID
                WHERE u.Id = ?
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$employeeId]);
            $employeeData = $stmt->fetch(PDO::FETCH_ASSOC);

            // Tính lương
            $receivedSalary = $employeeData['valid_days'] * $employeeData['DailySalary'];

            // Lưu vào salary_logs
            $logQuery = "INSERT INTO salary_logs (UserId, TotalWorkDays, ValidDays, InvalidDays, TotalSalary, CreatedAt)
                         VALUES (?, ?, ?, ?, ?, NOW())";
            $logStmt = $conn->prepare($logQuery);
            $logStmt->execute([
                $employeeId,
                $employeeData['total_work_days'],
                $employeeData['valid_days'],
                $employeeData['invalid_days'],
                $receivedSalary
            ]);

            $logData[] = [
                'FullName' => $employeeData['FullName'],
                'TotalSalary' => $receivedSalary
            ];
        }

        // Hiển thị thông báo thành công
        echo "Lưu trữ thành công!";
    } else {
        $response['message'] = "Phương thức không hợp lệ!";
    }
} catch (PDOException $e) {
    $response['message'] = "Lỗi hệ thống: " . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = "Lỗi: " . $e->getMessage();
}

ob_end_clean(); // Xóa toàn bộ output đang có trong buffer
echo json_encode($response); // Trả kết quả dưới dạng JSON
