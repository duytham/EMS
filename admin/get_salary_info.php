<?php
include '../config.php';
// include 'salary_functions.php'; // Bao gồm file chứa các hàm SQL

if (isset($_POST['employee_id'])) {
    $employee_id = $_POST['employee_id'];

    // Lấy thông tin bậc lương
    $stmt = $conn->prepare("SELECT alias, daily_salary FROM salary_levels WHERE id = (SELECT salary_level_id FROM user WHERE Id = ?)");
    $stmt->execute([$employee_id]);
    $level = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tính toán số ngày làm việc từ bảng checkinout
    $stmt = $conn->prepare("SELECT 
                                COUNT(DISTINCT DATE(CheckInTime)) AS total_days,
                                SUM(CASE WHEN status = 'Valid' THEN 1 ELSE 0 END) AS valid_days,
                                SUM(CASE WHEN status = 'Invalid' THEN 1 ELSE 0 END) AS invalid_days
                            FROM checkinout 
                            WHERE UserID = ?");
    $stmt->execute([$employee_id]);
    $days = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tính tổng lương
    $total_salary = ($days['valid_days'] * $level['daily_salary']) + 
                    ($days['invalid_days'] * $level['daily_salary'] * 0.5);

    // Trả về dữ liệu cho AJAX
    echo json_encode([
        'salary_level' => $level['alias'] . " - " . $level['daily_salary'] . " đ",
        'total_days' => $days['total_days'],
        'valid_days' => $days['valid_days'],
        'invalid_days' => $days['invalid_days'],
        'total_salary' => $total_salary
    ]);
}
