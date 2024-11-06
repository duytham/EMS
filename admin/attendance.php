<?php
session_start();
include '../config.php';

// Lấy department_id từ URL
$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

// Lấy danh sách nhân viên trong phòng ban
$query = "SELECT u.Id, u.FullName, u.Email, 
                 (SELECT ActionType FROM CheckInOut co WHERE co.UserID = u.Id ORDER BY co.LogDate DESC, co.CheckInTime DESC LIMIT 1) AS LastAction,
                 SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, co.CheckInTime, co.CheckOutTime))) AS TotalWorkingTime
          FROM User u
          LEFT JOIN CheckInOut co ON u.Id = co.UserID
          WHERE u.DepartmentID = :departmentId
          GROUP BY u.Id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':departmentId', $departmentId, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <style>
        /* Styles for the table */
        .table-container {
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #4e73df;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .status-active {
            color: green;
        }

        .status-inactive {
            color: red;
        }
    </style>

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

                <!-- Page Content -->
                <div class="container-fluid table-container">
                    <h1 class="h3 mb-4 text-gray-800">View attendance</h1>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee Name</th>
                                    <th>Email</th>
                                    <th>Total Working Time</th>
                                    <th>Status</th>
                                    <th>Actions</th> <!-- Cột mới cho nút Detail -->

                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($employees) > 0): ?>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($employee['Id']); ?></td> <!-- Hiển thị ID -->
                                            <td><?= htmlspecialchars($employee['FullName']); ?></td>
                                            <td><?= htmlspecialchars($employee['Email']); ?></td>
                                            <td><?= htmlspecialchars($employee['TotalWorkingTime'] ?: '0:00:00'); ?></td>
                                            <td>
                                                <?php if ($employee['LastAction'] === 'checkin'): ?>
                                                    <span style="color: green;">Check-in</span>
                                                <?php elseif ($employee['LastAction'] === 'checkout'): ?>
                                                    <span style="color: red;">Check-out</span>
                                                <?php else: ?>
                                                    <span>Not Checked In</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- Nút Detail dẫn đến trang attendance_detail.php với ID nhân viên -->
                                                <a href="attendance_detail.php?user_id=<?= htmlspecialchars($employee['Id']); ?>" class="btn btn-info btn-sm">Detail</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No employees found in this department.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

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