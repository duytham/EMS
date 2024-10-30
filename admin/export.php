<?php
require 'vendor/autoload.php'; // Include PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'STT');
$sheet->setCellValue('B1', 'Email');
$sheet->setCellValue('C1', 'Full Name');
$sheet->setCellValue('D1', 'Department');

// Fetch employees from the database
$stmt = $conn->prepare("SELECT Email, FullName, DepartmentID FROM user WHERE RoleID = 2"); // Fetch employees only
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rowNum = 2; // Start from the second row
foreach ($employees as $index => $employee) {
    $sheet->setCellValue('A' . $rowNum, $index + 1);
    $sheet->setCellValue('B' . $rowNum, $employee['Email']);
    $sheet->setCellValue('C' . $rowNum, $employee['FullName']);
    // Fetch department name based on DepartmentID
    $deptStmt = $conn->prepare("SELECT departmentname FROM department WHERE id = :id");
    $deptStmt->bindParam(':id', $employee['DepartmentID']);
    $deptStmt->execute();
    $department = $deptStmt->fetchColumn();
    $sheet->setCellValue('D' . $rowNum, $department);

    $rowNum++;
}

$writer = new Xlsx($spreadsheet);
$filename = 'employees_list.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$writer->save('php://output');
exit();
?>