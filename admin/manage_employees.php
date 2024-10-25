<?php
require_once '../config.php';  // Sử dụng file config.php với PDO

// Câu SQL để lấy danh sách nhân viên (trừ admin), bao gồm tên phòng ban
$sql = "
    SELECT u.Id, u.FullName, u.Email, u.PhoneNumber, d.DepartmentName 
    FROM `User` u 
    LEFT JOIN `Department` d ON u.DepartmentID = d.id 
    WHERE u.RoleID != 1 and u.RoleID != 3
";

try {
    // Chuẩn bị câu truy vấn
    $stmt = $conn->prepare($sql);

    // Thực thi truy vấn
    $stmt->execute();

    // Lấy tất cả kết quả
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC); // PDO sử dụng fetchAll()

    // Kiểm tra nếu có kết quả
    if (count($employees) > 0) {
        foreach ($employees as $employee) {
            //echo "ID: " . $employee["Id"] . " - Tên: " . htmlspecialchars($employee["FullName"]) . " - Email: " . htmlspecialchars($employee["Email"]) . "<br>";
        }
    } else {
        echo "Không có nhân viên nào.";
    }
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
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

                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Employee List</h1>
                    <?php
                    // Trước khi hiển thị danh sách nhân viên
                    if (isset($_GET['success'])) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
                    } elseif (isset($_GET['error'])) {
                        echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
                    }
                    ?>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <a href="add_employee.php" class="btn btn-primary btn-icon-split">
                                <span class="icon text-white-50">
                                </span>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEmployeeModal">
                                    Thêm Nhân Viên
                                </button>
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Full name</th>
                                            <th>Email</th>
                                            <th>Phone number</th>
                                            <th>Department</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($employees) > 0): ?>
                                            <?php foreach ($employees as $employee): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($employee['Id']); ?></td>
                                                    <td><?= htmlspecialchars($employee['FullName']); ?></td>
                                                    <td><?= htmlspecialchars($employee['Email']); ?></td>
                                                    <td><?= htmlspecialchars($employee['PhoneNumber']); ?></td>
                                                    <td><?= htmlspecialchars($employee['DepartmentName']); ?></td>
                                                    <td>
                                                        <a href="edit_employee.php?id=<?= $employee['Id']; ?>">Edit</a> |
                                                        <a href="delete_employee.php?id=<?= $employee['Id']; ?>" onclick="return confirm('Are you sure to delete this employee?');">Delete</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7">There's no data in list</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
</body>
</html>