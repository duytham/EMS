<?php
include '../config.php';

if (isset($_POST['employee_id'])) {
    $employee_id = $_POST['employee_id'];

    // Câu lệnh SQL lấy thông tin lương
    $sql = "SELECT u.EmploymentType, s.level AS salary_level, s.monthly_salary, s.daily_salary, s.alias, COUNT(c.Id) AS total_days, 
SUM(CASE WHEN c.Status = 'valid' THEN 1 ELSE 0 END) AS valid_days,
SUM(CASE WHEN c.Status = 'invalid' THEN 1 ELSE 0 END) AS invalid_days,
SUM(CASE WHEN c.Status = 'valid' THEN s.monthly_salary ELSE 0 END) AS total_salary
FROM user u
LEFT JOIN checkinout c ON u.Id = c.UserID
LEFT JOIN salary_levels s ON u.salary_level_id = s.id
WHERE u.Id = :employee_id
GROUP BY u.Id, s.level, s.monthly_salary, s.daily_salary, s.alias";

    // Thực hiện truy vấn để lấy thông tin lương
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kiểm tra nếu có dữ liệu trả về
    if ($data) {
        // Lưu thông tin lương vào biến $level
        $level = $data;

        // Truy vấn lấy số ngày làm việc hợp lệ và không hợp lệ
        $stmt = $conn->prepare("SELECT COUNT(*) as total_days, 
SUM(CASE WHEN Status = 'valid' THEN 1 ELSE 0 END) as valid_days,
SUM(CASE WHEN Status = 'invalid' THEN 1 ELSE 0 END) as invalid_days
FROM checkinout 
WHERE UserID = ? AND LogDate BETWEEN ? AND ?");
        $stmt->execute([$employee_id, date('Y-m-01'), date('Y-m-t')]); // Lọc theo tháng hiện tại
        $days = $stmt->fetch(PDO::FETCH_ASSOC);

        // Lấy tổng số ngày trong tháng
        $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));

        // Tính tổng lương dựa trên EmploymentType
        if ($level['EmploymentType'] === 'full-time') {
            $total_salary = ($days['valid_days'] / $totalDaysInMonth) * $level['monthly_salary'];
        } else { // part-time
            $total_salary = ($days['valid_days'] * $level['daily_salary']) +
                ($days['invalid_days'] * $level['daily_salary'] * 0.5);
        }

        // Trả về dữ liệu cho AJAX, bao gồm EmploymentType
        echo json_encode([
            'salary_level' => isset($level['alias']) ? $level['alias'] . " - Monthly: " . (isset($level['monthly_salary']) ? $level['monthly_salary'] : 0) . " đ - Daily: " . (isset($level['daily_salary']) ? $level['daily_salary'] : 0) . " đ)" : 'Không có thông tin',
            'employment_type' => $level['EmploymentType'] ?? null, // Thêm thông tin EmploymentType
            'total_days' => $days['total_days'] ?? 0,
            'valid_days' => $days['valid_days'] ?? 0, // Số ngày hợp lệ
            'invalid_days' => $days['invalid_days'] ?? 0, // Số ngày không hợp lệ
            'total_salary' => $total_salary ?? 0 // Lương tổng cộng
        ]);
    } else {
        // Trường hợp không có dữ liệu
        echo json_encode([
            'salary_level' => 'Không có thông tin',
            'employment_type' => null,
            'total_days' => 0,
            'valid_days' => 0,
            'invalid_days' => 0,
            'total_salary' => 0
        ]);
    }
}
