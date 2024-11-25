<?php
ob_start(); // Bắt đầu buffer để ngăn output ngoài JSON
require '../config.php';

header('Content-Type: application/json');
$response = ["success" => false, "message" => ""];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Kiểm tra dữ liệu gửi đi từ form
        $employee_id = $_POST['employee_id'] ?? null;
        $valid_days = $_POST['valid_days'] ?? 0;
        $invalid_days = $_POST['invalid_days'] ?? 0;
        $salary = $_POST['salary'] ?? 0;

        // Kiểm tra dữ liệu hợp lệ
        if (!$employee_id) {
            $response['message'] = "Dữ liệu không hợp lệ!";
            echo json_encode($response); // Sử dụng json_encode() để trả về JSON hợp lệ
            exit;
        }

        // Kiểm tra dữ liệu đã tồn tại
        $currentMonth = date('n'); // Tháng hiện tại
        $currentYear = date('Y');
        $processedBy = 9; // ID người xử lý mặc định (admin)

        $stmt = $conn->prepare("
            SELECT id FROM salary_logs 
            WHERE employee_id = :employee_id 
            AND month = :month 
            AND year = :year
        ");
        $stmt->execute([
            ':employee_id' => $employee_id,
            ':month' => $currentMonth,
            ':year' => $currentYear
        ]);

        if ($stmt->rowCount() > 0) {
            // Nếu tồn tại, cập nhật
            $stmt = $conn->prepare("
                UPDATE salary_logs
                SET valid_days = :valid_days, 
                    invalid_days = :invalid_days,
                    salary = :salary,
                    updated_at = NOW()
                WHERE employee_id = :employee_id 
                AND month = :month 
                AND year = :year
            ");
            $stmt->execute([
                ':valid_days' => $valid_days,
                ':invalid_days' => $invalid_days,
                ':salary' => $salary,
                ':employee_id' => $employee_id,
                ':month' => $currentMonth,
                ':year' => $currentYear
            ]);

            $response['success'] = true;
            $response['message'] = "Cập nhật thông tin lương thành công!";
        } else {
            // Nếu không tồn tại, thêm mới
            $stmt = $conn->prepare("
                INSERT INTO salary_logs (employee_id, valid_days, invalid_days, month, year, salary, processed_by, processed_at)
                VALUES (:employee_id, :valid_days, :invalid_days, :month, :year, :salary, :processed_by, NOW())
            ");
            $stmt->execute([
                ':employee_id' => $employee_id,
                ':valid_days' => $valid_days,
                ':invalid_days' => $invalid_days,
                ':month' => $currentMonth,
                ':year' => $currentYear,
                ':salary' => $salary,
                ':processed_by' => $processedBy
            ]);

            $response['success'] = true;
            $response['message'] = "Lưu thông tin lương mới thành công!";
        }
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
