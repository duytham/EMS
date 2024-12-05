<?php
// Kết nối cơ sở dữ liệu
include '../config.php';

// Lấy dữ liệu từ request
$employeeId = $_POST['employee_id'];
$salaryLevel = $_POST['salary_level'];
$totalDays = $_POST['total_days'];
$validDays = $_POST['valid_days'];
$invalidDays = $_POST['invalid_days'];
$totalSalary = $_POST['total_salary']; // '96850000 đ'
$month = $_POST['month'];
$year = $_POST['year'];

// Loại bỏ ký tự ' đ' trong giá trị total_salary và chuyển thành số
$totalSalary = str_replace(' đ', '', $totalSalary);
$totalSalary = (float) $totalSalary; // Chuyển thành kiểu số để lưu vào cơ sở dữ liệu

// Kiểm tra giá trị total_days, valid_days và invalid_days để đảm bảo chúng là số nguyên
$totalDays = (int) $totalDays;
$validDays = (int) $validDays;
$invalidDays = (int) $invalidDays;

// Kiểm tra xem nhân viên đã có trong bảng salary_logs chưa
$sql = "SELECT * FROM salary_logs WHERE employee_id = :employee_id AND month = :month AND year = :year";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':employee_id' => $employeeId,
    ':month' => $month,
    ':year' => $year
]);

$existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingRecord) {
    // Nếu đã có, thực hiện UPDATE
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
    echo json_encode(['success' => true]);
} else {
    // Nếu chưa có, thực hiện INSERT
    $sql = "SELECT EmploymentType FROM user WHERE Id = :employee_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':employee_id' => $employeeId]);
    $employmentType = $stmt->fetchColumn();

    if ($employmentType === 'full-time') {
        $salaryFormula = "((:valid_days / :total_days) * :monthly_salary)";
    } else { // part-time
        $salaryFormula = "(:valid_days * :daily_salary) + (:invalid_days * :daily_salary * 0.5)";
    }

    $sql = "INSERT INTO salary_logs (employee_id, salary_level, total_days, valid_days, invalid_days, total_salary, month, year) 
    VALUES (:employee_id, :salary_level, :total_days, :valid_days, :invalid_days, $salaryFormula, :month, :year)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':employee_id' => $employeeId,
        ':salary_level' => $salaryLevel,
        ':total_days' => $totalDays,
        ':valid_days' => $validDays,
        ':invalid_days' => $invalidDays,
        ':daily_salary' => $dailySalary,
        ':monthly_salary' => $monthlySalary,
        ':month' => $month,
        ':year' => $year
    ]);
    echo json_encode(['success' => true]);
}
