<?php
session_start();
require '../config.php'; // Kết nối database

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: ../login.php");
    exit();
}

/**
 * Kiểm tra trạng thái check-in/check-out của nhân viên trong ngày
 */
// Set the correct time zone
date_default_timezone_set('Asia/Ho_Chi_Minh');

$userId = $_SESSION['user_id'] ?? null; // ID của user đang đăng nhập
$roleId = $_SESSION['role_id'] ?? null; // Role ID của user đang đăng nhập

if (!isset($userId) || $roleId != 2) {
    header("Location: ../login.php");
    exit();
}

// Kiểm tra trạng thái check-in/check-out của nhân viên trong ngày
$logDate = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM CheckInOut WHERE UserID = :userId AND LogDate = :logDate");
$stmt->execute(['userId' => $userId, 'logDate' => $logDate]);
$attendanceLog = $stmt->fetch(PDO::FETCH_ASSOC);

// Tính tổng thời gian đã làm việc
$totalWorkingTime = "0:00"; // Thời gian làm việc tổng cộng
if ($attendanceLog) {
    $checkInTime = new DateTime($attendanceLog['CheckInTime']);
    $checkOutTime = new DateTime($attendanceLog['CheckOutTime'] ?? 'now'); // Sử dụng 'now' nếu chưa check-out
    $interval = $checkInTime->diff($checkOutTime);
    $totalWorkingTime = sprintf('%01d:%02d', $interval->h, $interval->i); // Định dạng giờ và phút
}

// Xử lý khi nhấn nút Check-in
if (isset($_POST['checkin'])) {
    if (!$attendanceLog) {
        $checkInTime = date('Y-m-d H:i:s');
        $checkInHour = (int)date('H', strtotime($checkInTime));
        $status = ($checkInHour < 8) ? "Valid" : "Invalid";

        // Thêm bản ghi check-in
        $stmt = $conn->prepare("INSERT INTO CheckInOut (UserID, CheckInTime, LogDate, status) VALUES (:userId, :checkInTime, :logDate, :status)");
        $stmt->execute([
            'userId' => $userId,
            'checkInTime' => $checkInTime,
            'logDate' => $logDate,
            'status' => $status
        ]);

        // Store reason if check-in is invalid
        if ($status == "Invalid") {
            $_SESSION['errorMessage'] = "Invalid check-in. Please provide a reason.";
            $_SESSION['attendance_id'] = $conn->lastInsertId(); // Store ID for reason form
            $showReasonForm = true; // Set a flag to show reason form
            $actionType = 'checkin'; // Set action type to checkin
        } else {
            $_SESSION['successMessage'] = "Check-in successfully!";
            header("Location: dashboard.php");
            exit();
        }
    } else {
        $_SESSION['errorMessage'] = "You're already checked-in today";
    }
}

// Xử lý khi nhấn nút Check-out
if (isset($_POST['checkout'])) {
    if ($attendanceLog && !$attendanceLog['CheckOutTime']) {
        // Lấy thời gian check-out hiện tại
        $checkOutTime = date('Y-m-d H:i:s');
        $checkOutHour = (int)date('H', strtotime($checkOutTime));

        // Cập nhật thời gian check-out
        $stmt = $conn->prepare("UPDATE CheckInOut SET CheckOutTime = :checkOutTime, ActionType = 'checkout' WHERE Id = :id");
        $stmt->execute(['checkOutTime' => $checkOutTime, 'id' => $attendanceLog['Id']]);

        // Kiểm tra điều kiện thời gian check-in và check-out
        $checkInHour = (int)date('H', strtotime($attendanceLog['CheckInTime']));
        $status = ($checkInHour < 8 && $checkOutHour > 17) ? "Valid" : "Invalid";

        // Cập nhật trạng thái
        $stmt = $conn->prepare("UPDATE CheckInOut SET status = :status WHERE Id = :id");
        $stmt->execute(['status' => $status, 'id' => $attendanceLog['Id']]);

        // Store reason if check-out is invalid
        if ($status == "Invalid") {
            $_SESSION['errorMessage'] = "Invalid check-out. Please provide a reason.";
            $showReasonForm = true; // Set a flag to show reason form
            $actionType = 'checkout'; // Set action type to checkout
        } else {
            $_SESSION['successMessage'] = "Check-out successfully!";
            header("Location: dashboard.php");
            exit();
        }
    } else {
        $_SESSION['errorMessage'] = $attendanceLog ? "You're already checked-out today" : "You need to check-in first";
    }
}

// Xử lý khi người dùng gửi lý do
if (isset($_POST['submit_reason'])) {
    $reason = $_POST['reason'] ?? '';
    $attendanceId = $_POST['attendance_id'] ?? null;

    if ($attendanceId && !empty($reason)) {
        try {
            // Cập nhật lý do vào bảng CheckInOut
            $stmt = $conn->prepare("UPDATE CheckInOut SET Reason = :reason, status = 'Pending' WHERE Id = :id");
            $stmt->execute(['reason' => $reason, 'id' => $attendanceId]);

            // Trả về thông báo thành công
            echo "<br><div class='alert alert-success'>Reason has been successfully sent. Please wait for admin approval!</div>";
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Error occurred: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Please provide a complete reason.</div>";
    }
    exit(); // Ngừng việc tải lại trang
}

// Thêm mã xử lý để lấy dữ liệu check-in/check-out cho các ngày trong tháng
$currentMonth = date('m');
$currentYear = date('Y');
if (isset($_POST['month']) && isset($_POST['year'])) {
    $currentMonth = $_POST['month'];
    $currentYear = $_POST['year'];
}

// Tính tổng số ngày trong tháng
$totalDays = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

// Lấy các ngày check-in của nhân viên trong tháng này
$stmt = $conn->prepare("SELECT LogDate FROM CheckInOut WHERE UserID = :userId AND MONTH(LogDate) = :month AND YEAR(LogDate) = :year");
$stmt->execute(['userId' => $userId, 'month' => $currentMonth, 'year' => $currentYear]);
$checkedInDays = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Lấy ngày nghỉ (giả định bạn có bảng chứa thông tin ngày nghỉ)
$holidays = []; // Danh sách ngày nghỉ có thể được lấy từ bảng khác nếu cần
// Ví dụ: $holidays = ['2024-10-01', '2024-10-02']; // Các ngày nghỉ trong tháng này
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
            background-color: #e9ecef;
            /* Màu nền nhẹ */
        }

        .container {
            display: flex;
            justify-content: space-between;
            padding: 20px;
        }

        .info-card,
        .attendance-card {
            flex: 1;
            background-color: #ffffff;
            /* Màu nền trắng */
            padding: 20px;
            border-radius: 10px;
            margin: 0 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            /* Độ bóng */
            transition: transform 0.2s;
            /* Hiệu ứng khi hover */
        }

        .info-card:hover,
        .attendance-card:hover {
            transform: translateY(-5px);
            /* Di chuyển lên một chút khi hover */
        }

        h4 {
            margin-bottom: 15px;
            color: #343a40;
            /* Màu chữ tiêu đề */
        }

        .total-time {
            font-size: 36px;
            /* Tăng kích thước chữ cho thời gian */
            font-weight: bold;
            color: #28a745;
            /* Màu xanh cho thời gian */
        }

        .btn {
            width: 100%;
            padding: 15px;
            margin-top: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s;
            /* Hiệu ứng chuyển màu */
        }

        .btn-checkin {
            background-color: #28a745;
            color: white;
        }

        .btn-checkin:hover {
            background-color: #218838;
            /* Màu khi hover */
        }

        .btn-checkout {
            background-color: #dc3545;
            color: white;
        }

        .btn-checkout:hover {
            background-color: #c82333;
            /* Màu khi hover */
        }

        .checkin-details {
            margin-top: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            /* Màu nền nhạt cho chi tiết check-in */
            border-radius: 5px;
            border-left: 4px solid #007bff;
            /* Viền bên trái */
        }

        .alert {
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .calendar {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .calendar-header {
            font-weight: bold;
            font-size: 1.5rem;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .day {
            position: relative;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .checked-in {
            background-color: #28a745;
            /* Màu xanh cho ngày chấm công */
            color: white;
        }

        .holiday {
            background-color: #dc3545;
            /* Màu đỏ cho ngày nghỉ */
            color: white;
        }

        .not-reached {
            background-color: #6c757d;
            /* Màu xám cho ngày chưa tới */
            color: white;
        }

        .empty {
            background-color: transparent;
            /* Ô trống không có màu nền */
        }

        .day-header {
            font-weight: bold;
            color: #343a40;
            text-align: center;
            background-color: #e9ecef;
            border-radius: 5px;
            padding: 10px 0;
        }

        .day-number {
            font-size: 1.2rem;
        }

        .day.checked-in {
            background-color: #28a745;
            /* Chấm xanh */
            color: white;
        }

        .day.holiday {
            background-color: #dc3545;
            /* Chấm đỏ */
            color: white;
        }

        .day.not-reached {
            background-color: #6c757d;
            /* Chấm xám */
            color: white;
        }

        .day.empty {
            background-color: transparent;
            /* Ô trống không có màu nền */
        }

        .day:hover:not(.empty) {
            background-color: rgba(0, 0, 0, 0.1);
            /* Màu hover */
        }

        .dot {
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .checked-in-dot {
            background-color: #28a745;
            /* Chấm xanh */
        }

        .holiday-dot {
            background-color: #dc3545;
            /* Chấm đỏ */
        }

        .invalid-reason {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }

        .invalid-reason h5 {
            color: #dc3545;
            margin-bottom: 10px;
        }

        .invalid-reason textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .invalid-reason .btn-warning {
            background-color: #ffc107;
            border: none;
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

                <h2 class="text-center" style="margin-top: 20px; color: #343a40;">Timekeeping</h2>

                <div class="message">
                    <?php if (isset($_SESSION['successMessage'])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION['successMessage'];
                            unset($_SESSION['successMessage']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['errorMessage'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $_SESSION['errorMessage'];
                            unset($_SESSION['errorMessage']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="container">
                    <div class="info-card">
                        <h4>Total working time today:</h4>
                        <h2 class="total-time"><?php echo $totalWorkingTime; ?></h2>

                        <div class="today-date">
                            <p><?php echo date('d-m-Y'); ?></p>
                        </div>
                    </div>

                    <div class="attendance-card">
                        <h4>Action:</h4>
                        <form method="POST">
                            <button type="submit" name="checkin" class="btn btn-checkin"
                                <?php echo ($attendanceLog && $attendanceLog['CheckInTime']) ? 'disabled' : ''; ?>>
                                Check-in
                            </button>
                            <button type="submit" name="checkout" class="btn btn-checkout"
                                <?php echo ($attendanceLog && !$attendanceLog['CheckOutTime']) ? '' : 'disabled'; ?>>
                                Check-out
                            </button>
                        </form>

                        <?php if ($attendanceLog): ?>
                            <div class="checkin-details">
                                <p><strong>Check-in Time:</strong> <?php echo date('d-m-Y H:i:s', strtotime($attendanceLog['CheckInTime'])); ?></p>
                                <p><strong>Check-out Time:</strong> <?php echo $attendanceLog['CheckOutTime'] ? date('d-m-Y H:i:s', strtotime($attendanceLog['CheckOutTime'])) : " Not checked out"; ?></p>
                            </div>

                            <?php if ($attendanceLog['status'] == 'Invalid' || isset($showReasonForm)): ?>
                                <div class="invalid-reason">
                                    <h5>Reason for Invalid <?php echo isset($actionType) && $actionType == 'checkin' ? 'Check-in' : 'Check-out'; ?>:</h5>
                                    <form id="reasonForm">
                                        <select id="reasonSelect" class="form-control" required>
                                            <option value="">Select a reason</option>
                                            <option value="Reason 1">Heavy traffic: Due to traffic jams or poor traffic conditions.</option>
                                            <option value="Reason 2">Personal problems: Having an urgent matter to attend to (e.g. family issues).</option>
                                            <option value="Reason 3">Illness: Due to poor health or illness.</option>
                                            <option value="Reason 4">Doctor's appointment: Having an appointment that cannot be changed.</option>
                                            <option value="Reason 5">Bad weather: Due to unfavorable weather (rainstorms, snow, etc.).</option>
                                            <option value="Reason 6">Change in work schedule: There is an unexpected change in work schedule.</option>
                                            <option value="Reason 7">Vehicle problems: Vehicle breaks down or is not usable.</option>
                                            <option value="other">Other reason</option>
                                        </select>

                                        <textarea name="reason" id="reason" class="form-control mt-3" rows="3" placeholder="Please provide the reason for invalid check-in/out" style="display:none;"></textarea>
                                        <input type="hidden" name="attendance_id" id="attendance_id" value="<?php echo $attendanceLog['Id']; ?>">
                                        <button type="submit" class="btn btn-warning mt-3">Submit Reason</button>
                                    </form>
                                    <div id="responseMessage"></div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="container mt-4 text-center">
                    <a href="monthly_work_summary.php">Go to Monthly Work Summary</a>
                    <a href="leave_request_form.php">Create a Leave Request</a>

                </div>

                <div class="container mt-4">
                    <form method="POST" class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <select name="month" class="form-select" onchange="this.form.submit()">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo ($m == $currentMonth) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <select name="year" class="form-select" onchange="this.form.submit()">
                                <?php for ($y = 2020; $y <= date('Y'); $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($y == $currentYear) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </form>

                    <div class="calendar">
                        <div class="calendar-header text-center mb-4">
                            <h4><?php echo date('F Y', strtotime("$currentYear-$currentMonth-01")); ?></h4>
                        </div>
                        <div class="calendar-days">
                            <?php
                            // Lấy tên các ngày trong tuần
                            $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

                            // In các tiêu đề ngày trong tuần
                            foreach ($daysOfWeek as $day): ?>
                                <div class="day-header"><?php echo $day; ?></div>
                            <?php endforeach; ?>

                            <?php
                            // Tìm ngày đầu tiên của tháng
                            $firstDayOfMonth = date('w', strtotime("$currentYear-$currentMonth-01"));
                            // In ô trống cho các ngày trước tháng này
                            for ($i = 0; $i < $firstDayOfMonth; $i++): ?>
                                <div class="day empty"></div>
                            <?php endfor; ?>

                            <?php
                            // In các ngày trong tháng
                            for ($day = 1; $day <= $totalDays; $day++):
                                $dateString = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $day);
                                $class = 'day';

                                if (in_array($dateString, $checkedInDays)) {
                                    $class .= ' checked-in'; // Chấm xanh
                                } elseif (in_array($dateString, $holidays)) {
                                    $class .= ' holiday'; // Chấm đỏ
                                } elseif (strtotime($dateString) > time()) {
                                    $class .= ' not-reached'; // Chấm xám
                                }
                            ?>
                                <div class="<?php echo $class; ?>">
                                    <span class="day-number"><?php echo $day; ?></span>
                                    <?php if ($class == 'day checked-in'): ?>
                                        <span class="dot checked-in-dot"></span>
                                    <?php elseif ($class == 'day holiday'): ?>
                                        <span class="dot holiday-dot"></span>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('../templates/footer.php') ?>
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
        <script>
            document.getElementById('reasonForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Ngăn chặn việc gửi form mặc định

                let reasonSelect = document.getElementById('reasonSelect');
                let reasonTextarea = document.getElementById('reason');
                let attendanceId = document.getElementById('attendance_id').value;

                // Kiểm tra xem lý do đã chọn có phải là 'other' hay không
                let reason;
                if (reasonSelect.value === 'other') {
                    reason = reasonTextarea.value; // Lấy giá trị từ textarea nếu chọn "Other"
                } else {
                    reason = reasonSelect.options[reasonSelect.selectedIndex].text; // Lấy nội dung mô tả từ combobox
                }

                if (attendanceId && (reasonSelect.value !== '' && (reasonSelect.value !== 'other' || reason))) {
                    let formData = new FormData();
                    formData.append('reason', reason);
                    formData.append('attendance_id', attendanceId);
                    formData.append('submit_reason', true); // Đánh dấu yêu cầu là một lần gửi form

                    // Gửi dữ liệu bằng AJAX
                    fetch('dashboard.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            // Hiển thị thông báo phản hồi
                            document.getElementById('responseMessage').innerHTML = data;
                        })
                        .catch(error => console.error('Lỗi:', error));
                } else {
                    alert('Vui lòng chọn một lý do.');
                }
            });
        </script>

        <script>
            document.getElementById('reasonSelect').addEventListener('change', function() {
                var selectedValue = this.value;
                var reasonTextarea = document.getElementById('reason');
                if (selectedValue === 'other') {
                    reasonTextarea.style.display = 'block';
                    reasonTextarea.required = true; // Make it required if 'other' is selected
                } else {
                    reasonTextarea.style.display = 'none';
                    reasonTextarea.required = false; // Not required for other options
                    reasonTextarea.value = ''; // Clear the textarea if another option is selected
                }
            });

            window.onload = function() {
                // Kiểm tra nếu form giải trình check-in hiển thị
                if (document.getElementById("checkinReasonForm")) {
                    document.getElementById("checkinButton").disabled = true; // Disable nút checkin
                    document.getElementById("checkoutButton").disabled = true; // Disable nút checkout
                }

                // Kiểm tra nếu form giải trình check-out hiển thị
                if (document.getElementById("checkoutReasonForm")) {
                    document.getElementById("checkinButton").disabled = true; // Disable nút checkin
                    document.getElementById("checkoutButton").disabled = true; // Disable nút checkout
                }

                // Hiển thị textarea khi chọn 'Other reason'
                document.getElementById('reasonSelect').addEventListener('change', function() {
                    var reasonSelect = document.getElementById('reasonSelect');
                    var reasonTextarea = document.getElementById('reason');

                    if (reasonSelect.value === 'other') {
                        reasonTextarea.style.display = 'block';
                    } else {
                        reasonTextarea.style.display = 'none';
                    }
                });
            };
        </script>
</body>

</html>
