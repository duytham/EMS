<?php
session_start();
include '../config.php';

// Lấy User ID từ session
$userId = $_SESSION['user_id'];

// Truy vấn thông tin từ bảng LeaveConfig
$stmt = $conn->prepare("SELECT MaxLeaveDays, UsedLeaveDays FROM LeaveConfig WHERE UserId = ?");
$stmt->execute([$userId]);
$leaveConfig = $stmt->fetch(PDO::FETCH_ASSOC);

if ($leaveConfig) {
    $maxLeaveDays = $leaveConfig['MaxLeaveDays'];
    $usedLeaveDays = $leaveConfig['UsedLeaveDays'];
    $remainingLeaveDays = $maxLeaveDays - $usedLeaveDays;
} else {
    $maxLeaveDays = 0;
    $usedLeaveDays = 0;
    $remainingLeaveDays = 0;
}

// Hiển thị thông báo nếu có
if (isset($_SESSION['message'])) {
    $messageType = $_SESSION['message_type'] ?? 'info';
    echo "<div class='alert alert-$messageType'>{$_SESSION['message']}</div>";
    unset($_SESSION['message']);
    unset($_SESSION['message_type']); // Xóa thông báo sau khi hiển thị
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveStart = $_POST['leave_start'];
    $leaveEnd = $_POST['leave_end'];
    $reasonSelect = $_POST['reason_select'];
    $reason = $reasonSelect === 'Other' ? $_POST['reason'] : $reasonSelect;

    // Tính số ngày nghỉ
    $startDate = new DateTime($leaveStart);
    $endDate = new DateTime($leaveEnd);
    $currentDate = new DateTime();
    $interval = $startDate->diff($endDate)->days + 1;

    // Kiểm tra ngày bắt đầu và ngày kết thúc
    if ($startDate < $currentDate) {
        $_SESSION['message'] = "The start date cannot be before the current date.";
        $_SESSION['message_type'] = 'danger';
        header("Location: leave_request_form.php");
        exit;
    }

    if ($startDate > $endDate) {
        $_SESSION['message'] = "The start date must be before the end date.";
        $_SESSION['message_type'] = 'danger';
        header("Location: leave_request_form.php");
        exit;
    }

    // Kiểm tra số ngày nghỉ còn lại
    if ($interval + $usedLeaveDays > $maxLeaveDays) {
        $_SESSION['message'] = "The number of leave days exceeds the limit. Please go to work or submit an explanation.";
        $_SESSION['message_type'] = 'danger';
        header("Location: leave_request_form.php");
        exit;
    }

    // Tạo đơn nghỉ phép
    $stmt = $conn->prepare("INSERT INTO LeaveRequest (UserId, LeaveDateStart, LeaveDateEnd, Reason) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $leaveStart, $leaveEnd, $reason]);

    // Cập nhật số ngày nghỉ đã sử dụng
    $stmt = $conn->prepare("UPDATE LeaveConfig SET UsedLeaveDays = UsedLeaveDays + ? WHERE UserId = ?");
    $stmt->execute([$interval, $userId]);

    $_SESSION['message'] = "Leave request created successfully.";
    $_SESSION['message_type'] = 'success';
    header("Location: leave_request_form.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee - EDMS - Add New Leave Request</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include('../employee/sidebar.php') ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('../templates/navbar.php') ?>
                <div class="container-fluid">
                    <h2 class="my-4">Add New Leave Request</h2>

                    <!-- Leave Days Information -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Leave Days</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $maxLeaveDays ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Used Leave Days</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $usedLeaveDays ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Remaining Leave Days</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $remainingLeaveDays ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="leave_request_form.php" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="leave_start">Leave Start Date:</label>
                                    <input type="date" name="leave_start" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="leave_end">Leave End Date:</label>
                                    <input type="date" name="leave_end" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="reason_select">Reason:</label>
                            <select name="reason_select" id="reason_select" class="form-control" required onchange="toggleReasonInput(this)">
                                <option value="">Select reason</option>
                                <option value="Sick">Sick</option>
                                <option value="Vacation">Vacation</option>
                                <option value="Personal">Personal</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group" id="reasonInputGroup" style="display:none;">
                            <label for="reason">Enter other reason here:</label>
                            <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="Enter other reason"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Leave Request</button>
                    </form>
                    <a href="view_leave_requests.php" class="btn btn-secondary mt-3">View your Leave Requests</a>
                </div>
            </div>
            <?php include('../templates/footer.php') ?>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
    <script>
    function toggleReasonInput(select) {
        var reasonInputGroup = document.getElementById('reasonInputGroup');
        var reasonInput = document.getElementById('reason');
        if (select.value === 'Other') {
            reasonInputGroup.style.display = 'block';
            reasonInput.required = true;
        } else {
            reasonInputGroup.style.display = 'none';
            reasonInput.required = false;
            reasonInput.value = select.value; // Set the reason to the selected value
        }
    }
    </script>
</body>
</html>