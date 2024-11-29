<?php
// Kết nối cơ sở dữ liệu
include '../config.php';

// Hàm lấy dữ liệu của một nhân viên trong bảng salary_logs
function getSalaryLog($employeeId, $month, $year)
{
    global $conn;
    $sql = "SELECT * FROM salary_logs WHERE employee_id = :employee_id AND month = :month AND year = :year";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':employee_id' => $employeeId,
        ':month' => $month,
        ':year' => $year
    ]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Hàm lấy mức lương từ bảng SalaryLevels theo mức lương
function getSalaryLevelDetails($salaryLevel)
{
    global $conn;
    $sql = "SELECT * FROM SalaryLevels WHERE Id = :salary_level";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':salary_level' => $salaryLevel]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Hàm cập nhật thông tin lương vào bảng salary_logs
function updateSalaryLog($employeeId, $salaryLevel, $totalDays, $validDays, $invalidDays, $totalSalary, $month, $year)
{
    global $conn;
    $sql = "UPDATE salary_logs SET salary_level = :salary_level, total_days = :total_days, valid_days = :valid_days, invalid_days = :invalid_days, total_salary = :total_salary WHERE employee_id = :employee_id AND month = :month AND year = :year";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':salary_level' => $salaryLevel,
        ':total_days' => $totalDays,
        ':valid_days' => $validDays,
        ':invalid_days' => $invalidDays,
        ':total_salary' => $totalSalary,
        ':employee_id' => $employeeId,
        ':month' => $month,
        ':year' => $year
    ]);
}

// Hàm thêm mới bản ghi lương vào bảng salary_logs
function insertSalaryLog($employeeId, $salaryLevel, $totalDays, $validDays, $invalidDays, $totalSalary, $month, $year)
{
    global $conn;
    $sql = "INSERT INTO salary_logs (employee_id, salary_level, total_days, valid_days, invalid_days, total_salary, month, year) VALUES (:employee_id, :salary_level, :total_days, :valid_days, :invalid_days, :total_salary, :month, :year)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':employee_id' => $employeeId,
        ':salary_level' => $salaryLevel,
        ':total_days' => $totalDays,
        ':valid_days' => $validDays,
        ':invalid_days' => $invalidDays,
        ':total_salary' => $totalSalary,
        ':month' => $month,
        ':year' => $year
    ]);
}
