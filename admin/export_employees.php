<?php
require_once "../config.php";
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$limit = 6; // Số dòng tối đa cho mỗi file
$sql = "SELECT user.Id, user.FullName, user.Email, user.PhoneNumber, department.DepartmentName, user.Status 
        FROM user 
        LEFT JOIN department ON user.DepartmentID = department.Id
        WHERE user.RoleID = 2";

$stmt = $conn->prepare($sql);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalEmployees = count($employees);
$fileCount = ceil($totalEmployees / $limit); // Tính số file cần tạo

$tempDir = sys_get_temp_dir() . '/employee_files';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true); // Tạo thư mục tạm nếu chưa tồn tại
}

for ($i = 0; $i < $fileCount; $i++) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set header cho các cột
    $sheet->setCellValue('A1', 'STT');
    $sheet->setCellValue('B1', 'Full Name');
    $sheet->setCellValue('C1', 'Email');
    $sheet->setCellValue('D1', 'Phone Number');
    $sheet->setCellValue('E1', 'Department');
    $sheet->setCellValue('F1', 'Status');

    // Định dạng hàng tiêu đề
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['argb' => 'FFFFFFFF'],
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FF4F81BD'],
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
    ];

    // Áp dụng định dạng cho hàng tiêu đề
    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

    // Cài đặt độ rộng cho cột
    $sheet->getColumnDimension('A')->setWidth(10);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('C')->setWidth(30);
    $sheet->getColumnDimension('D')->setWidth(15);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(15);

    // Xuất dữ liệu vào Excel
    $row = 2; // Bắt đầu từ dòng 2
    for ($j = $i * $limit; $j < min(($i + 1) * $limit, $totalEmployees); $j++) {
        $employee = $employees[$j];
        $sheet->setCellValue('A' . $row, $j + 1);
        $sheet->setCellValue('B' . $row, $employee['FullName']);
        $sheet->setCellValue('C' . $row, $employee['Email']);
        $sheet->setCellValue('D' . $row, $employee['PhoneNumber']);
        $sheet->setCellValue('E' . $row, $employee['DepartmentName']);
        $sheet->setCellValue('F' . $row, $employee['Status']);
        $row++;
    }

    // Tạo file và lưu vào thư mục tạm
    $writer = new Xlsx($spreadsheet);
    $filename = 'employee_data_' . ($i + 1) . '.xlsx';
    $tempFilePath = $tempDir . '/' . $filename;
    $writer->save($tempFilePath);
}

// Nén các file Excel vào file ZIP
$zipFilePath = $tempDir . '/employee_data.zip';
$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    exit("Can't open ZIP file.");
}

// Thêm tất cả các file Excel vào file ZIP
foreach (glob($tempDir . '/*.xlsx') as $file) {
    $zip->addFile($file, basename($file));
}

$zip->close();

// Gửi file ZIP đến trình duyệt để tải xuống
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="employee_data.zip"');
header('Content-Length: ' . filesize($zipFilePath));
readfile($zipFilePath);

// Xóa thư mục tạm và file ZIP sau khi tải xuống
array_map('unlink', glob("$tempDir/*.*"));
rmdir($tempDir);
unlink($zipFilePath);
exit();
