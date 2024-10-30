<?php
// Kết nối tới database
include "../config.php";

// Truy vấn dữ liệu phòng ban
$sql = "SELECT d1.Id, d1.DepartmentName, 
               d2.DepartmentName AS ParentDepartmentName, 
               COUNT(u.Id) AS EmployeeCount, 
               d1.Status
        FROM Department d1
        LEFT JOIN Department d2 ON d1.ParentDepartmentID = d2.Id
        LEFT JOIN `User` u ON u.DepartmentID = d1.Id
        GROUP BY d1.Id";
$result = $conn->query($sql);
$departments = $result->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT d1.Id, d1.DepartmentName, 
               d2.DepartmentName AS ParentDepartmentName, 
               COUNT(u.Id) AS EmployeeCount, 
               d1.Status,
               GROUP_CONCAT(u.Id) AS EmployeeIds -- Thêm dòng này
        FROM Department d1
        LEFT JOIN Department d2 ON d1.ParentDepartmentID = d2.Id
        LEFT JOIN `User` u ON u.DepartmentID = d1.Id
        GROUP BY d1.Id";
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

    <title>Admin - EDMS - Dashboard</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

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

                <!-- <?php include('../') ?> -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- DataTales Example -->
                    <div class="container-fluid">
                        <h1 class="h3 mb-2 text-gray-800">Department List</h1>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']); ?></div>
                        <?php elseif (isset($_GET['error'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']); ?></div>
                        <?php endif; ?>
                        <?php
                        // Kiểm tra tham số status để hiển thị thông báo thành công
                        if (isset($_GET['status']) && $_GET['status'] == 'success') {
                            echo '<div class="alert alert-success">Department added successfully!</div>';
                        }
                        ?>


                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <a href="add_department.php" class="btn btn-primary btn-icon-split">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addDepartmentModal">
                                        Add New Department
                                    </button>
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Department Name</th>
                                                <th>Parent Department</th>
                                                <th>Employee Count</th>
                                                <th>Status</th>
                                                <th>Employees</th> <!-- Cột mới -->
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($departments) > 0): ?>
                                                <?php foreach ($departments as $department): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($department['Id']); ?></td>
                                                        <td><?= htmlspecialchars($department['DepartmentName']); ?></td>
                                                        <td><?= htmlspecialchars($department['ParentDepartmentName'] ?? 'None'); ?></td>
                                                        <td><?= htmlspecialchars($department['EmployeeCount']); ?></td>
                                                        <td>
                                                            <?php if ($department['Status'] == 'active'): ?>
                                                                <span style="color: green;">Active</span>
                                                            <?php else: ?>
                                                                <span style="color: red;">Inactive</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="view_employee.php?department_id=<?= $department['Id']; ?>">
                                                                View Employees
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <a href="edit_department.php?id=<?= $department['Id']; ?>">Edit</a> |
                                                            <?php if ($department['Status'] == 'active'): ?>
                                                                <a href="#" onclick="showStatusModal(<?= $department['Id']; ?>, 'inactive')">Inactive</a>
                                                            <?php else: ?>
                                                                <a href="#" onclick="showStatusModal(<?= $department['Id']; ?>, 'active')">Active</a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7">No departments available.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Content Wrapper -->

                <?php include('../templates/footer.php') ?>
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

        <!-- Confirm Delete Modal -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Inactivation</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">Are you sure you want to inactive this department?</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <a class="btn btn-danger" id="confirmDeleteBtn" href="#">Inactive</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Status Change Modal -->
        <div class="modal fade" id="statusConfirmModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="statusModalLabel">Confirm Status Change</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">Are you sure you want to change the status of this department?</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <a class="btn btn-primary" id="confirmStatusBtn" href="#">Change Status</a>
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
            function showDeleteModal(employeeId) {
                // Set the href attribute for confirmDeleteBtn with the employee ID
                const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
                confirmDeleteBtn.href = 'delete_employee.php?id=' + employeeId;

                // Show the delete confirmation modal
                $('#deleteConfirmModal').modal('show');
            }
        </script>

        <script>
            function showStatusModal(departmentId, newStatus) {
                // Set the href attribute for confirmStatusBtn with the department ID and new status
                const confirmStatusBtn = document.getElementById('confirmStatusBtn');
                confirmStatusBtn.href = 'change_status_department.php?id=' + departmentId + '&status=' + newStatus;

                // Show the status confirmation modal
                $('#statusConfirmModal').modal('show');
            }
        </script>

    </div>
</body>

</html>