<?php
include '../config.php';

// Kiểm tra dữ liệu từ POST
if (isset($_POST['employee_id'], $_POST['total_days'], $_POST['valid_days'], $_POST['invalid_days'], $_POST['total_salary'], $_POST['month'], $_POST['year'])) {
    // Lấy dữ liệu từ POST
    $employee_id = $_POST['employee_id'];
    $total_days = $_POST['total_days']; // Đây sẽ là tổng số ngày làm việc
    $valid_days = $_POST['valid_days'];
    $invalid_days = $_POST['invalid_days'];
    $total_salary = $_POST['total_salary'];
    $total_salary = str_replace('.', '', $_POST['total_salary']); // Xóa dấu chấm
$total_salary = str_replace(',', '.', $total_salary); // Thay thế dấu phẩy bằng dấu chấm
    $month = $_POST['month'];
    $year = $_POST['year'];

    // Kiểm tra tổng số ngày không phải 0
    if ($total_days == 0) {
        echo json_encode(['success' => false, 'message' => 'Tổng số ngày làm việc không thể bằng 0.']);
        exit;
    }

    // Kiểm tra xem người dùng đã có dữ liệu lương trong tháng này chưa
    $stmt = $conn->prepare("SELECT * FROM salary_logs WHERE employee_id = :employee_id AND month = :month AND year = :year");
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->bindParam(':month', $month);
    $stmt->bindParam(':year', $year);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Nếu đã có bản ghi, thực hiện UPDATE
        $stmt = $conn->prepare("UPDATE salary_logs SET total_days = :total_days, valid_days = :valid_days, invalid_days = :invalid_days, total_salary = :total_salary WHERE employee_id = :employee_id AND month = :month AND year = :year");
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':total_days', $total_days);
        $stmt->bindParam(':valid_days', $valid_days);
        $stmt->bindParam(':invalid_days', $invalid_days);
        $stmt->bindParam(':total_salary', $total_salary);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Salary info updated successfully.']);
    } else {
        // Nếu chưa có bản ghi, thực hiện INSERT
        $stmt = $conn->prepare("INSERT INTO salary_logs (employee_id, total_days, valid_days, invalid_days, total_salary, month, year) VALUES (:employee_id, :total_days, :valid_days, :invalid_days, :total_salary, :month, :year)");
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':total_days', $total_days);
        $stmt->bindParam(':valid_days', $valid_days);
        $stmt->bindParam(':invalid_days', $invalid_days);
        $stmt->bindParam(':total_salary', $total_salary);
        $stmt->bindParam(':month', $month);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Salary info saved successfully.']);
    }
} else {
    // Nếu thiếu dữ liệu, trả về lỗi
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
}
?>