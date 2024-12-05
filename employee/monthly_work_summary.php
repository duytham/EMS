<?php
session_start();
include '../config.php'; // Kết nối database

// Kiểm tra nếu người dùng đã đăng nhập và có `user_id`
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Lấy user_id từ session
$userId = $_SESSION['user_id'];

// Lấy tháng và năm hiện tại
$currentMonth = date('m');
$currentYear = date('Y');

// Luôn lấy tháng và năm hiện tại
$selectedMonth = $currentMonth;
$selectedYear = $currentYear;

// Kiểm tra nếu có giá trị tháng và năm từ form gửi lên
if (isset($_POST['month']) && isset($_POST['year'])) {
    $selectedMonth = $_POST['month'];
    $selectedYear = $_POST['year'];
}

// Câu truy vấn để lấy tổng thời gian làm việc từng ngày trong tháng đã chọn cho user hiện tại
$query = "SELECT 
            DATE(LogDate) AS WorkDate, 
            TIME(CheckInTime) AS CheckInTime, 
            TIME(CheckOutTime) AS CheckOutTime,
            TIMEDIFF(CheckOutTime, CheckInTime) AS WorkDuration
          FROM CheckInOut
          WHERE UserID = :userId AND MONTH(LogDate) = :selectedMonth AND YEAR(LogDate) = :selectedYear
          ORDER BY WorkDate, CheckInTime";

$stmt = $conn->prepare($query);
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->bindParam(':selectedMonth', $selectedMonth, PDO::PARAM_INT);
$stmt->bindParam(':selectedYear', $selectedYear, PDO::PARAM_INT);
$stmt->execute();
$workDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Câu truy vấn để lấy danh sách ngày đã điền giải trình
$queryJustifications = "SELECT 
                            LogDate, 
                            ActionType, 
                            Reason, 
                            Status 
                        FROM CheckInOut
                        WHERE UserID = :userId 
                          AND MONTH(LogDate) = :selectedMonth 
                          AND YEAR(LogDate) = :selectedYear 
                          AND Reason IS NOT NULL 
                        ORDER BY LogDate DESC";

$stmt = $conn->prepare($queryJustifications);
$stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
$stmt->bindParam(':selectedMonth', $selectedMonth, PDO::PARAM_INT);
$stmt->bindParam(':selectedYear', $selectedYear, PDO::PARAM_INT);
$stmt->execute();
$justifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <title>Employee - EDMS - Dashboard</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f4f9;
            font-family: 'Nunito', sans-serif;
        }

        .container-fluid {
            padding-top: 2rem;
        }

        h1 {
            color: #4e73df;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead {
            background-color: #4e73df;
            color: white;
            font-weight: bold;
            text-align: center;
        }

        .table-hover tbody tr:hover {
            background-color: #f0f8ff;
        }

        .total-duration {
            background-color: #eaf2fb;
            font-weight: bold;
            color: #2e59d9;
        }

        .motivational-message {
            margin-top: 20px;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1.1rem;
            text-align: center;
            color: white;
        }

        .motivational-message.good-job {
            background-color: #28a745;
        }

        .motivational-message.needs-improvement {
            background-color: #dc3545;
        }

        .motivational-message .icon {
            font-size: 1.5rem;
            margin-right: 5px;
        }

        .badge-checkin {
            background-color: #007bff;
            /* Màu xanh dương */
            color: #fff;
        }

        .badge-checkout {
            background-color: #6c757d;
            /* Màu xám */
            color: #fff;
        }

        .badge {
            font-weight: bold;
            /* Làm cho chữ đậm hơn */
            padding: 0.5em 1em;
            /* Thêm khoảng cách xung quanh chữ */
            border-radius: 0.5em;
            /* Bo tròn các góc */
        }

        .badge:hover {
            transform: scale(1.1);
            /* Phóng to khi hover */
        }
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include('../employee/sidebar.php') ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include('../templates/navbar.php') ?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="text-center">Monthly Work Summary</h1>

                    <!-- Form để chọn tháng và năm -->
                    <form method="POST" class="mb-4">
                        <label for="month">Select Month: </label>
                        <select name="month" id="month">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($m == $currentMonth) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>

                        <label for="year">Year: </label>
                        <select name="year" id="year">
                            <?php for ($y = 2020; $y <= date('Y'); $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($y == $currentYear) ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>

                        <input type="submit" class="btn btn-primary" value="Filter">
                    </form>
                    <!-- Tháng và Năm hiện tại đang hiển thị -->
                    <p class="text-center">Displaying data for month <?= $selectedMonth ?>, year <?= $selectedYear ?>
                    <p></p>

                    <!-- Bảng tóm tắt công việc -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Check-in Time</th>
                                    <th>Check-out Time</th>
                                    <th>Total Work Duration (HH:MM:SS)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $currentDate = '';
                                $dailyTotalDuration = 0;
                                $monthlyTotalDuration = 0;
                                $workDays = 0;

                                if (!empty($workDetails)):
                                    foreach ($workDetails as $row):
                                        if ($currentDate !== $row['WorkDate']) {
                                            if ($currentDate !== '') {
                                                $monthlyTotalDuration += $dailyTotalDuration;
                                                $workDays++;
                                            }
                                            $currentDate = $row['WorkDate'];
                                            $dailyTotalDuration = 0;
                                        }
                                        $dailyTotalDuration += strtotime($row['WorkDuration']) - strtotime("00:00:00");
                                ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($row['WorkDate'])) ?></td>
                                            <td><?= $row['CheckInTime'] ?: 'N/A' ?></td>
                                            <td><?= $row['CheckOutTime'] ?: 'N/A' ?></td>
                                            <td><?= $row['WorkDuration'] ?: 'N/A' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3" class="text-end total-duration">Monthly Total</td>
                                        <td class="total-duration"><?= gmdate("H:i:s", $monthlyTotalDuration) ?></td>
                                    </tr>
                                    <?php
                                    $monthlyTotalDuration += $dailyTotalDuration;
                                    $averageWorkDuration = $monthlyTotalDuration / max($workDays, 1); // avoid division by zero
                                    ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No records found for this month.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bảng danh sách giải trình -->
                    <h1 class="text-center">Justification Requests</h1>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($justifications)): ?>
                                    <?php foreach ($justifications as $row): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($row['LogDate'])) ?></td>
                                            <td>
                                                <?php
                                                $actionType = $row['ActionType'];
                                                $formattedAction = $actionType === 'checkin' ? 'Check-in' : 'Check-out';

                                                // Áp dụng màu sắc dựa trên loại hành động
                                                if ($actionType === 'checkin') {
                                                    echo "<span class='text-primary'>$formattedAction</span>";
                                                } elseif ($actionType === 'checkout') {
                                                    echo "<span class='text-secondary'>$formattedAction</span>";
                                                }
                                                ?>
                                            </td>
                                            <td><?= $row['Reason'] ?></td>
                                            <td>
                                                <?php
                                                switch ($row['Status']) {
                                                    case 'Valid':
                                                        echo "<span class='badge bg-success text-white'>Valid</span>"; // Màu xanh cho Valid
                                                        break;
                                                    case 'Invalid':
                                                        echo "<span class='badge bg-danger text-white'>Invalid</span>"; // Màu đỏ cho Invalid
                                                        break;
                                                    default:
                                                        echo "<span class='badge bg-warning text-dark'>Pending</span>"; // Màu vàng cho Pending
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No justifications found for this month.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <?php include('../employee/footer.php') ?>
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
        <script src="../js/demo/chart-area-demo.js"></script>
</body>

</html>