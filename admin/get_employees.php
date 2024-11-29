<?php
// Kết nối cơ sở dữ liệu
include '../config.php';

if (isset($_POST['department_id'])) {
    $department_id = $_POST['department_id'];
    
    // Truy vấn lấy danh sách nhân viên của phòng ban
    $stmt = $conn->prepare("SELECT Id, FullName FROM user WHERE DepartmentID = ?");
    $stmt->execute([$department_id]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($employees);
}
