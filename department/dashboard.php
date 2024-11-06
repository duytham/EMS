<?php
session_start();
include "../config.php";

// Kiểm tra quyền truy cập cho department role
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: /login.php");
    exit();
}

$departmentId = $_GET['department_id'] ?? null;

// Kiểm tra nếu có ID nhân viên được gửi từ URL
if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Lấy thông tin nhân viên từ cơ sở dữ liệu
    $stmt = $conn->prepare("SELECT * FROM `User` WHERE Id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Truy vấn dữ liệu nhân viên theo phòng ban với tên phòng ban
$sql = "
    SELECT U.*, D.DepartmentName 
    FROM User U 
    LEFT JOIN Department D ON U.DepartmentID = D.Id 
    WHERE U.DepartmentID = :departmentId
";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':departmentId', $departmentId);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy tên phòng ban và tên phòng ban cha
$sqlDepartment = "
    SELECT d1.departmentname AS child_department_name, 
           d2.departmentname AS parent_department_name
    FROM Department d1
    LEFT JOIN Department d2 ON d1.parentdepartmentid = d2.id
    WHERE d1.id = :departmentId
";
$stmt = $conn->prepare($sqlDepartment);
$stmt->bindParam(':departmentId', $departmentId);
$stmt->execute();
$departmentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$childDepartmentName = $departmentInfo['child_department_name'] ?? '';
$parentDepartmentName = $departmentInfo['parent_department_name'] ?? '';

// Truy vấn dữ liệu nhân viên theo phòng ban với tên phòng ban
$sql = "
    SELECT U.*, D.DepartmentName 
    FROM User U 
    LEFT JOIN Department D ON U.DepartmentID = D.Id 
    WHERE U.DepartmentID = :departmentId
";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':departmentId', $departmentId);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy tên phòng ban và tên phòng ban cha
$sqlDepartment = "
    SELECT d1.departmentname AS child_department_name, 
           d2.departmentname AS parent_department_name
    FROM Department d1
    LEFT JOIN Department d2 ON d1.parentdepartmentid = d2.id
    WHERE d1.id = :departmentId
";
$stmt = $conn->prepare($sqlDepartment);
$stmt->bindParam(':departmentId', $departmentId);
$stmt->execute();
$departmentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$childDepartmentName = $departmentInfo['child_department_name'] ?? '';
$parentDepartmentName = $departmentInfo['parent_department_name'] ?? '';

// Fetch the department and parent department names
$departmentId = $_GET['department_id'] ?? null;
$query = "SELECT d.DepartmentName AS SubDepartment, 
                 COALESCE(pd.DepartmentName, '') AS ParentDepartment
          FROM Department d
          LEFT JOIN Department pd ON d.ParentDepartmentID = pd.Id
          WHERE d.Id = :departmentId
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bindParam(':departmentId', $departmentId, PDO::PARAM_INT);
$stmt->execute();
$department = $stmt->fetch(PDO::FETCH_ASSOC);

$subDepartmentName = $department['SubDepartment'] ?? 'Unknown';
$parentDepartmentName = $department['ParentDepartment'] ?? '';

//
$query = "SELECT u.Id, u.FullName, u.Email, u.PhoneNumber, 
                 COALESCE((SELECT ActionType FROM CheckInOut co WHERE co.UserID = u.Id ORDER BY co.LogDate DESC, co.CheckInTime DESC LIMIT 1), 'Not Checked In') AS LastAction,
                 u.Status
          FROM User u
          WHERE u.DepartmentID = :departmentId";

// Lấy danh sách nhân viên thuộc department
$query = "SELECT Id, FullName, Email, Status FROM User WHERE DepartmentID = :departmentId AND RoleID = 2";
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

    <title>Department - EDMS - Employee List</title>

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
        <?php include('../department/sidebar.php') ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include('../templates/navbar.php') ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">
                        Employee List of
                        <?= htmlspecialchars($parentDepartmentName) ?>
                        <?= (!empty($parentDepartmentName) && !empty($subDepartmentName)) ? ' - ' : '' ?>
                        <?= htmlspecialchars($subDepartmentName) ?>
                    </h1>
                    <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Full name</th>
                                            <th>Email</th>
                                            <th>Phone number</th>
                                            <th>Department</th>
                                            <th>Status</th>
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
                                                        <?php if ($employee['Status'] == 'active'): ?>
                                                            <span style="color: green;">Active</span>
                                                        <?php else: ?>
                                                            <span style="color: red;">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="edit_employee.php?id=<?= $employee['Id']; ?>&redirect=<?= urlencode($_SERVER['REQUEST_URI']); ?>">Edit</a> |
                                                        <?php if ($employee['Status'] == 'active'): ?>
                                                            <a href="#" onclick="showStatusModal(<?= $employee['Id']; ?>, 'inactive')">Inactive</a>
                                                        <?php else: ?>
                                                            <a href="#" onclick="showStatusModal(<?= $employee['Id']; ?>, 'active')">Active</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7">There's no data in the list</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
</body>

</html>