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
        sl.monthly_salary AS MonthlySalary,
        u.EmploymentType,

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

        -- Tổng số ngày trong tháng
        DAY(LAST_DAY(STR_TO_DATE(CONCAT(:year, '-', :month, '-01'), '%Y-%m-%d'))) AS TotalDaysInMonth,

        -- Lương hợp lệ
        CASE 
            WHEN u.EmploymentType = 'full-time' THEN 
                ROUND(
                    (COUNT(CASE 
                        WHEN c.status = 'Valid' THEN 1 ELSE NULL 
                    END) / DAY(LAST_DAY(STR_TO_DATE(CONCAT(:year, '-', :month, '-01'), '%Y-%m-%d')))
                    ) * sl.monthly_salary, 2
                )
            WHEN u.EmploymentType = 'part-time' THEN
                ROUND(
                    (COUNT(CASE 
                        WHEN c.status = 'Valid' THEN 1 ELSE NULL 
                    END) * sl.daily_salary) +
                    (COUNT(CASE 
                        WHEN c.status = 'Invalid' THEN 1 ELSE NULL 
                    END) * (0.5 * sl.daily_salary)), 2
                )
            ELSE 0
        END AS CalculatedSalary
    FROM `User` u
    LEFT JOIN `checkinout` c 
        ON u.Id = c.UserID 
        AND MONTH(c.LogDate) = :month
        AND YEAR(c.LogDate) = :year
    LEFT JOIN `salary_levels` sl 
        ON u.salary_level_id = sl.id
    WHERE u.Id = :employee_id
    GROUP BY u.Id, u.FullName, sl.alias, sl.daily_salary, sl.monthly_salary, u.EmploymentType
    ";
$stmt = $this->db->prepare($sql);

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
