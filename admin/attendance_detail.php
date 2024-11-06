<?php
session_start();
include '../config.php';

// Lấy user_id từ URL
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Lấy tháng và năm hiện tại
$currentMonth = date('m');
$currentYear = date('Y');

// Kiểm tra nếu có giá trị tháng và năm từ form gửi lên (dùng để lọc)
$selectedMonth = isset($_POST['month']) ? $_POST['month'] : $currentMonth;
$selectedYear = isset($_POST['year']) ? $_POST['year'] : $currentYear;

// Tạo danh sách các ngày trong tháng
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
$attendance = [];

// Truy vấn để lấy thông tin check-in/check-out của nhân viên trong tháng đã chọn
$query = "SELECT DATE(LogDate) AS WorkDate, 
                 TIME(CheckInTime) AS CheckInTime, 
                 TIME(CheckOutTime) AS CheckOutTime,
                 TIMEDIFF(CheckOutTime, CheckInTime) AS WorkDuration
          FROM CheckInOut
          WHERE UserID = :userId AND MONTH(LogDate) = :selectedMonth AND YEAR(LogDate) = :selectedYear";
$stmt = $conn->prepare($query);
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->bindParam(':selectedMonth', $selectedMonth, PDO::PARAM_INT);
$stmt->bindParam(':selectedYear', $selectedYear, PDO::PARAM_INT);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sắp xếp dữ liệu theo ngày, đánh dấu ngày nào có/không có check-in/check-out
for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
    $attendance[$date] = [
        'CheckInTime' => null,
        'CheckOutTime' => null,
        'WorkDuration' => '-'
    ];
}

// Điền dữ liệu check-in/check-out vào các ngày tương ứng
foreach ($records as $record) {
    $workDate = $record['WorkDate'];
    $attendance[$workDate] = [
        'CheckInTime' => $record['CheckInTime'],
        'CheckOutTime' => $record['CheckOutTime'],
        'WorkDuration' => $record['WorkDuration'] ?: '-'
    ];
}
?>

<!DOCTYPE html>
<?php
include "../config.php";
?>

<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Department - EDMS - Attendance for Department</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include('../admin/sidebar.php') ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include('../templates/navbar.php') ?>

                <body>
                    <div class="container">
                        <h1 class="h3 mb-4 text-gray-800">Attendance Details</h1>
                        <form method="POST" class="mb-4">
                            <label for="month">Month:</label>
                            <input type="number" id="month" name="month" min="1" max="12" value="<?= htmlspecialchars($selectedMonth); ?>">
                            <label for="year">Year:</label>
                            <input type="number" id="year" name="year" min="2000" max="2100" value="<?= htmlspecialchars($selectedYear); ?>">
                            <button type="submit" class="btn btn-primary">Lọc</button>
                        </form>

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Check-in time</th>
                                    <th>Check-out time</th>
                                    <th>Total working time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance as $date => $details): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($date); ?></td>
                                        <td><?= htmlspecialchars($details['CheckInTime'] ?: '-'); ?></td>
                                        <td><?= htmlspecialchars($details['CheckOutTime'] ?: '-'); ?></td>
                                        <td><?= htmlspecialchars($details['WorkDuration']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </body>
                <?php include('../admin/footer.php') ?>
            </div>
            <!-- End of Content Wrapper -->

        </div>
        <!-- End of Page Wrapper -->

        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

        <!-- Logout Modal-->
        <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <a class="btn btn-primary" href="/EMS/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap core JavaScript-->
        <script src="../vendor/jquery/jquery.min.js"></script>
        <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

        <!-- Core plugin JavaScript-->
        <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

        <!-- Custom scripts for all pages-->
        <script src="../js/sb-admin-2.min.js"></script>

        <!-- Page level plugins -->
        <script src="../vendor/chart.js/Chart.min.js"></script>

        <!-- Page level custom scripts -->
        <script src="../js/demo/chart-area-demo.js"></script>
</body>

</html>

</html>