<?php
session_start();
include '../config.php';

// Fetch employee's salary level
$employeeId = $_GET['employee_id'];
$stmt = $conn->prepare("SELECT u.FullName, sl.level, sl.monthly_salary, sl.daily_salary
                        FROM user u
                        JOIN salary_levels sl ON u.salary_level_id = sl.id
                        WHERE u.Id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch attendance data for the selected month and year
$selectedMonth = $_GET['month'] ?? date('m');
$selectedYear = $_GET['year'] ?? date('Y');
$stmt = $conn->prepare("SELECT COUNT(*) AS valid_days
                        FROM checkinout
                        WHERE UserID = ? AND MONTH(CheckInTime) = ? AND YEAR(CheckInTime) = ? AND status = 'Valid'");
$stmt->execute([$employeeId, $selectedMonth, $selectedYear]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate salary
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
$validDays = $attendance['valid_days'];
$monthlySalary = $employee['monthly_salary'];
$dailySalary = $employee['daily_salary'];

$salaryByMonth = ($validDays / $daysInMonth) * $mossnthlySalary;
$salaryByDay = $validDays * $dailySalary;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Calculate Salary</title>
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Salary Calculation for <?= htmlspecialchars($employee['FullName']) ?></h2>
        <p>Level: <?= htmlspecialchars($employee['level']) ?></p>
        <p>Monthly Salary: <?= htmlspecialchars($employee['monthly_salary']) ?></p>
        <p>Daily Salary: <?= htmlspecialchars($employee['daily_salary']) ?></p>
        <p>Valid Days: <?= htmlspecialchars($validDays) ?></p>
        <p>Days in Month: <?= htmlspecialchars($daysInMonth) ?></p>
        <h3>Salary by Month: <?= number_format($salaryByMonth, 2) ?></h3>
        <h3>Salary by Day: <?= number_format($salaryByDay, 2) ?></h3>
    </div>
</body>
</html>