<?php
session_start(); // Bắt đầu phiên
require_once '../config.php';  // Sử dụng file config.php với PDO

// Lấy danh sách cấu hình email và thông tin user, department
$query = "SELECT e.UserId, u.FullName, u.Email, 
        CONCAT(d1.DepartmentName, ' - ', IFNULL(d2.DepartmentName, 'No Parent')) AS DepartmentName,
        e.CheckInTime, e.CheckOutTime 
        FROM emailConfig e 
        JOIN user u ON e.UserId = u.Id
        LEFT JOIN department d1 ON u.DepartmentID = d1.Id
        LEFT JOIN department d2 ON d1.ParentDepartmentID = d2.Id
        WHERE u.Status = 'active' ";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kiểm tra dữ liệu trả về
    if (empty($configs)) {
        echo "Không có nhân viên nào thỏa mãn điều kiện";
    }
} catch (PDOException $e) {
    echo "Lỗi truy vấn: " . $e->getMessage();
}

// Lấy thời gian gần nhất đã config
$latestQuery = "SELECT CheckInTime, CheckOutTime FROM emailConfig LIMIT 1";
$latestStmt = $conn->prepare($latestQuery);
$latestStmt->execute();
$latestConfig = $latestStmt->fetch(PDO::FETCH_ASSOC);

// Giá trị mặc định khi chưa có cấu hình gần nhất
$defaultCheckIn = "07:30";
$defaultCheckOut = "17:30";

$lastCheckIn = $latestConfig['CheckInTime'] ?? $defaultCheckIn;
$lastCheckOut = $latestConfig['CheckOutTime'] ?? $defaultCheckOut;

// Cập nhật giờ check-in/check-out
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['bulkCheckInTime']) && isset($_POST['bulkCheckOutTime'])) {
        // Cập nhật hàng loạt
        $bulkCheckInTime = $_POST['bulkCheckInTime'];
        $bulkCheckOutTime = $_POST['bulkCheckOutTime'];

        $updateQuery = "UPDATE emailConfig 
                        SET CheckInTime = ?, 
                        CheckOutTime = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$bulkCheckInTime, $bulkCheckOutTime]);

        // Lưu thời gian gần nhất
        $lastCheckIn = $bulkCheckInTime;
        $lastCheckOut = $bulkCheckOutTime;

        // Lưu thông báo vào session
        $_SESSION['success_message'] = "Bulk hour configuration successful";
    } elseif (isset($_POST['UserID']) && isset($_POST['CheckInTime']) && isset($_POST['CheckOutTime'])) {
        // Cập nhật từng user
        $userId = $_POST['UserID'];
        $checkInTime = $_POST['CheckInTime'];
        $checkOutTime = $_POST['CheckOutTime'];

        $updateQuery = "UPDATE emailConfig SET CheckInTime = ?, CheckOutTime = ? WHERE UserId = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$checkInTime, $checkOutTime, $userId]);

        // Lưu thông báo vào session
        $_SESSION['success_message'] = "Update check-in/check-out time for employees successful.";
    }
    header("Location: manage_config_email.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Admin - EDMS - Email Configuration</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('../admin/sidebar.php') ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('../templates/navbar.php') ?>

                <div class="container-fluid">
                    <!-- Hiển thị thông báo -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); // Xóa thông báo sau khi hiển thị 
                        ?>
                    <?php endif; ?>

                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Manage Config</h1>
                    <p class="mb-4">Manage config check-in, check-out time</p>

                    <!-- Form chỉnh sửa giờ hàng loạt -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold text-primary">Bulk Time Updates</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="bulkCheckInTime">New Check-in time:</label>
                                        <input type="time" class="form-control" id="bulkCheckInTime" name="bulkCheckInTime" value="<?php echo htmlspecialchars($lastCheckIn); ?>">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="bulkCheckOutTime">New Check-out time:</label>
                                        <input type="time" class="form-control" id="bulkCheckOutTime" name="bulkCheckOutTime" value="<?php echo htmlspecialchars($lastCheckOut); ?>">
                                    </div>
                                </div>
                                <button class="btn btn-primary" type="submit">Update all</button>
                            </form>
                        </div>
                    </div>

                    <!-- Hiển thị thời gian cấu hình gần nhất -->
                    <?php if (isset($lastCheckIn) && isset($lastCheckOut)): ?>
                        <div class="alert alert-info" role="alert">
                            Latest configuration time:
                            <strong>Check-in:</strong> <?php echo htmlspecialchars($lastCheckIn); ?>,
                            <strong>Check-out:</strong> <?php echo htmlspecialchars($lastCheckOut); ?>
                        </div>
                    <?php endif; ?>
                    <hr>

                    <!-- Bảng chỉnh sửa giờ từng user -->

                    <div class="card-body">

                        <div class="table-responsive">
                            <div class="card-header">
                                <h5 class="m-0 font-weight-bold text-primary">Employee's time Configuration</h5>
                            </div>
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th colspan="7" onclick="toggleConfig()" style="cursor: pointer; background-color: #f8f9fa; text-align: center;">
                                            Config time of each employee (click to display information)
                                        </th>
                                    </tr>
                                    <tr id="config-table-header" style="display: none;">
                                        <th>ID</th>
                                        <th>Employee Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Check-in Time</th>
                                        <th>Check-out Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody id="config-table-body" style="display: none;">
                                    <?php foreach ($configs as $config): ?>
                                        <tr>
                                            <form method="POST" action="">
                                                <td><?php echo htmlspecialchars($config['UserId']); ?></td>
                                                <td><?php echo htmlspecialchars($config['FullName']); ?></td>
                                                <td><?php echo htmlspecialchars($config['Email']); ?></td>
                                                <td><?php echo htmlspecialchars($config['DepartmentName']); ?></td>
                                                <td><input type="time" name="CheckInTime" value="<?php echo htmlspecialchars($config['CheckInTime']); ?>"></td>
                                                <td><input type="time" name="CheckOutTime" value="<?php echo htmlspecialchars($config['CheckOutTime']); ?>"></td>
                                                <td>
                                                    <input type="hidden" name="UserID" value="<?php echo htmlspecialchars($config['UserId']); ?>">
                                                    <button class="btn btn-primary" type="submit">Update</button>
                                                </td>
                                            </form>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <script>
                        function toggleConfig() {
                            var header = document.getElementById('config-table-header');
                            var body = document.getElementById('config-table-body');
                            if (header.style.display === "none") {
                                header.style.display = "table-row";
                                body.style.display = "table-row-group"; // Hiển thị tbody
                            } else {
                                header.style.display = "none";
                                body.style.display = "none"; // Ẩn tbody
                            }
                        }
                    </script>
                </div>
            </div>
        </div>
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
        <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

        <!-- Page level custom scripts -->
        <script src="../js/demo/chart-area-demo.js"></script>
        <script src="../js/demo/chart-area-demo.js"></script>
        <script src="../js/demo/datatables-demo.js"></script>
        <script>
            document.getElementById('sendEmailButton').addEventListener('click', function() {
                fetch('send_email.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            send_email: true
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message || "No response message");
                    })
                    .catch(error => {
                        alert("Error sending emails: " + error.message);
                    });
            });
        </script>
    </div>
</body>

</html>