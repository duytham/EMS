<?php
require_once '../config.php';

if (isset($_GET['employee_id']) && isset($_GET['month']) && isset($_GET['year'])) {
    $employeeId = $_GET['employee_id'];
    $month = $_GET['month'];
    $year = $_GET['year'];

    $sql = "
    SELECT 
        u.Id AS EmployeeID, 
        u.FullName, 
        sl.alias AS SalaryAlias, 
        sl.daily_salary AS DailySalary,

        -- Ngày công hợp lệ (status = 'Valid')
        COUNT(CASE 
            WHEN c.status = 'Valid' 
            THEN 1 
            ELSE NULL 
        END) AS ValidDays,

        -- Ngày công không hợp lệ (status = 'Invalid')
        COUNT(CASE 
            WHEN c.status = 'Invalid' 
            THEN 1 
            ELSE NULL 
        END) AS InvalidDays,

        -- Tổng số ngày làm việc (số lần checkin của nhân viên)
        COUNT(c.Id) AS TotalDaysWorked,

        -- Tính tổng lương theo ngày công hợp lệ (lương ngày hợp lệ = daily_salary)
        COUNT(CASE 
            WHEN c.status = 'Valid' 
            THEN 1 
            ELSE NULL 
        END) * sl.daily_salary AS TotalValidDaysSalary,

        -- Tính tổng lương ngày công không hợp lệ (50% lương ngày)
        COUNT(CASE 
            WHEN c.status = 'Invalid' 
            THEN 1 
            ELSE NULL 
        END) * (0.5 * sl.daily_salary) AS TotalInvalidDaysSalary,

        -- Tính tổng lương nhận được
        ROUND(
            (COUNT(CASE 
                WHEN c.status = 'Valid' 
                THEN 1 
                ELSE NULL 
            END) * sl.daily_salary) + 
            
            (COUNT(CASE 
                WHEN c.status = 'Invalid' 
                THEN 1 
                ELSE NULL 
            END) * (0.5 * sl.daily_salary)),
            2
        ) AS CalculatedSalary

    FROM `User` u
    LEFT JOIN `checkinout` c 
        ON u.Id = c.UserID 
        AND MONTH(c.LogDate) = :month
        AND YEAR(c.LogDate) = :year
    LEFT JOIN `salary_levels` sl 
        ON u.salary_level_id = sl.id
    WHERE u.Id = :employee_id
    GROUP BY u.Id, u.FullName, sl.alias, sl.daily_salary
    ";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindParam(':month', $month, PDO::PARAM_INT);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy dữ liệu'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi SQL: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ'
    ]);
}
