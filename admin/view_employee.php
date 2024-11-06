<?php
// Kết nối tới database
session_start(); // Khởi động phiên để sử dụng biến session
include "../config.php";
$errorMessage = ''; // Khởi tạo biến thông báo lỗi
$successMessage = ''; // Khởi tạo biến thông báo thành công

// Lấy department_id từ URL
$departmentId = $_GET['department_id'] ?? null;

// Kiểm tra xem có thông báo thành công trong session không
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage'];
    unset($_SESSION['successMessage']); // Xóa thông báo sau khi đã hiển thị
}

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
?>

<!DOCTYPE html>


<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Admin - EDMS - View Employees</title>

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
                    <h1 class="h3 mb-2 text-gray-800">
                        Employee List of <?= htmlspecialchars($parentDepartmentName) . ($parentDepartmentName ? ' - ' : '') . htmlspecialchars($childDepartmentName) . ' Department'; ?>
                    </h1>
                    <?php
                    // if (isset($_GET['success'])) {
                    //     echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
                    // } elseif (isset($_GET['error'])) {
                    //     echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
                    // }
                    if ($successMessage): ?>
                        <div class="alert alert-success"><?= $successMessage; ?></div>
                    <?php endif;
                    ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <a href="add_employee.php" class="btn btn-primary btn-icon-split">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEmployeeModal">
                                    Add new employee
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
                    </div>
                </div>
                <?php include('../templates/footer.php') ?>
            </div>
        </div>



        <div class="modal fade" id="statusConfirmModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="statusModalLabel">Confirm Status Change</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">Are you sure you want to change the status of this employee?</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <a class="btn btn-primary" id="confirmStatusBtn" href="#">Change Status</a>
                    </div>
                </div>
            </div>
        </div>

        <script src="../vendor/jquery/jquery.min.js"></script>
        <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
        <script src="../js/sb-admin-2.min.js"></script>

        <script>
            function showStatusModal(employeeId, newStatus) {
                const confirmStatusBtn = document.getElementById('confirmStatusBtn');
                confirmStatusBtn.href = 'change_status_employee.php?id=' + employeeId + '&status=' + newStatus;
                $('#statusConfirmModal').modal('show');
            }
        </script>
    </div>
</body>

</html>