<?php
session_start();
// Include file cấu hình kết nối
require_once '../config.php'; // Đường dẫn đến file config của bạn
// Truy vấn lấy dữ liệu nhân viên cùng với thông tin phòng ban, mức lương, và nhật ký lương
$sql = "
    SELECT 
    u.Id AS employee_id,
    u.FullName,
    CONCAT(d.DepartmentName, ' - ', IFNULL(d2.DepartmentName, 'No Parent')) AS Department,
    COALESCE(sl.alias, 'No Level') AS SalaryLevel, -- Hiển thị alias thay vì level
    COALESCE(SUM(sl_logs.valid_days + sl_logs.invalid_days), 0) AS total_work_days,
    COALESCE(SUM(sl_logs.valid_days), 0) AS valid_days,
    COALESCE(SUM(sl_logs.invalid_days), 0) AS invalid_days,
    COALESCE(SUM(sl_logs.valid_days * sl.daily_salary), 0) AS total_daily_salary
FROM user u
LEFT JOIN department d ON u.DepartmentID = d.Id
LEFT JOIN department d2 ON d.ParentDepartmentID = d2.Id
LEFT JOIN salary_levels sl ON u.salary_level_id = sl.id
LEFT JOIN salary_logs sl_logs ON u.Id = sl_logs.employee_id
    AND sl_logs.month = MONTH(CURDATE())
    AND sl_logs.year = YEAR(CURDATE())
WHERE u.RoleID = 2 -- Chỉ hiển thị nhân viên
GROUP BY u.Id, sl.alias
ORDER BY u.FullName;

";

$stmt = $conn->prepare($sql);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Admin - EDMS - Employee's Salary</title>

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

                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
                    <?php endif; ?>

                    <?php
                    if (isset($_SESSION['successMessage'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif; ?>

                    <?php
                    if (isset($_GET['success'])): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif;

                    if (isset($errorMessage) && !empty($errorMessage)) {
                        echo "<div class='alert alert-danger'>$errorMessage</div>";
                    }
                    ?>

                    <body>
                        <h1 class="h3 mb-2 text-gray-800">Employee Salary Calculation</h1>
                        <p class="mb-4">Displays the list of employees of departments, along with each employee's salary</p>

                        <?php if (isset($statusMessage)): ?>
                            <p><?= $statusMessage ?></p>
                        <?php endif; ?>


                        <form method="POST" action="">
                            <button type="submit" name="calculate_salary" class="btn btn-warning mr-2">
                                <i class="fas fa-sync-alt"></i> Recalculate
                            </button>
                            <button type="submit" name="save_salary" class="btn btn-success">
                                <i class="fas fa-save"></i> Save
                            </button> <!-- Nút lưu trữ tính lương -->
                        </form>

                        <!-- <form method="POST" action="">
                        </form> -->

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Employee Salary Calculation</h6>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Full Name</th>
                                                    <th>Department</th>
                                                    <th>Salary Level</th>
                                                    <th>Total Work Days</th> <!-- Thêm cột mới -->
                                                    <th>Valid Days</th>
                                                    <th>Invaild Days</th>
                                                    <th>Salary Received</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <?php foreach ($employees as $employee): ?>
                                                    <tr>
                                                        <td><?= $employee['employee_id'] ?></td>
                                                        <td><?= $employee['FullName'] ?></td>
                                                        <td><?= $employee['Department'] ?></td>
                                                        <td><?= $employee['SalaryLevel'] ?></td>
                                                        <td><?= $employee['total_work_days'] ?></td>
                                                        <td><?= $employee['valid_days'] ?></td>
                                                        <td><?= $employee['invalid_days'] ?></td>
                                                        <td><?= number_format($employee['total_daily_salary'], 0, ',', '.') . ' đ' ?></td> <!-- Định dạng lương ngày -->
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </body>
                </div>
                <?php include('../templates/footer.php') ?>
            </div>
        </div>
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
        <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

        <!-- Page level custom scripts -->
        <script src="../js/demo/chart-area-demo.js"></script>
        <script src="../js/demo/chart-area-demo.js"></script>
        <script src="../js/demo/datatables-demo.js"></script>

        <script>
            function showStatusModal(departmentId, newStatus) {
                // Set the href attribute for confirmStatusBtn with the department ID and new status
                const confirmStatusBtn = document.getElementById('confirmStatusBtn');
                confirmStatusBtn.href = 'change_status_employee.php?id=' + departmentId + '&status=' + newStatus;

                // Show the status confirmation modal
                $('#statusConfirmModal').modal('show');
            }
        </script>
    </div>
</body>

</html>