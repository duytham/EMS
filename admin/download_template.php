<?php
require_once "../config.php";
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Tạo mới một spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Đặt tiêu đề cho các cột
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Email');
$sheet->setCellValue('C1', 'Full Name');
$sheet->setCellValue('D1', 'Phone Number');
$sheet->setCellValue('E1', 'Department');

// Đặt định dạng cho hàng tiêu đề
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
$sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

// Cài đặt độ rộng cho cột
$sheet->getColumnDimension('A')->setWidth(10);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(30);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);

// Tạo file và xuất file Excel
$writer = new Xlsx($spreadsheet);
$filename = 'employee_template.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit();
?>
