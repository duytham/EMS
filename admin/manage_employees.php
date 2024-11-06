<?php
session_start(); // Bắt đầu phiên
require_once '../config.php';  // Sử dụng file config.php với PDO
require '../vendor/autoload.php';


// Câu SQL để lấy danh sách nhân viên (trừ admin), bao gồm tên phòng ban
$sql = "
    SELECT u.Id, u.FullName, u.Email, u.PhoneNumber, d.DepartmentName, u.Status 
    FROM `User` u 
    LEFT JOIN `Department` d ON u.DepartmentID = d.id 
    WHERE u.RoleID != 1 and u.RoleID != 3
";

// Lấy thông tin chi tiết về user và phòng ban (only role employee)
$sql = "
    SELECT u.Id, u.FullName, u.Email, u.PhoneNumber, 
           d.DepartmentName AS ChildDepartment, 
           pd.DepartmentName AS ParentDepartment, 
           u.Status 
    FROM `User` u 
    LEFT JOIN `Department` d ON u.DepartmentID = d.id 
    LEFT JOIN `Department` pd ON d.ParentDepartmentID = pd.id 
    WHERE u.RoleID != 1 and u.RoleID != 3
";

// Kiểm tra thông báo thành công và lưu nó vào biến
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage'];
    unset($_SESSION['successMessage']); // Xóa thông báo sau khi đã hiển thị
}

// Cũng có thể kiểm tra thông báo lỗi tương tự
if (isset($_SESSION['errorMessage'])) {
    $errorMessage = $_SESSION['errorMessage'];
    unset($_SESSION['errorMessage']); // Xóa thông báo sau khi đã hiển thị
}

// // Kiểm tra xem người dùng đã tải file lên chưa
// if (isset($_POST['import']) && isset($_FILES['file'])) {
//     unset($_SESSION['errorMessage']); // Xóa thông báo lỗi trước

//     $file = $_FILES['file']['tmp_name'];

//     if (!file_exists($file)) {
//         $_SESSION['errorMessage'] = "File does not exist.";
//         header("Location: manage_employees.php");
//         exit();
//     }

//     try {
//         // Đọc file Excel
//         $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
//         $worksheet = $spreadsheet->getActiveSheet();
//         $rows = $worksheet->toArray();

//         // Bỏ qua dòng đầu tiên nếu là tiêu đề
//         $duplicateEmails = []; // Mảng để lưu trữ email trùng lặp
//         $existingEmails = []; // Mảng chứa email đã có trong cơ sở dữ liệu
//         $invalidDepartment = []; // Mảng chứa phòng ban không hợp lệ

//         // Lấy tất cả email đã có trong cơ sở dữ liệu
//         $stmt = $conn->prepare("SELECT Email FROM User");
//         $stmt->execute();
//         $existingEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);

//         // Thực hiện import
//         foreach ($rows as $index => $row) {
//             if ($index == 0) {
//                 continue; // Bỏ qua dòng tiêu đề
//             }

//             $stt = $row[0];
//             $email = $row[1];
//             $fullName = $row[2];
//             $phoneNumber = !empty($row[3]) ? $row[3] : NULL; // Có thể là NULL nếu trống
//             $department = !empty($row[4]) ? $row[4] : NULL; // Có thể là NULL nếu trống

//             // Kiểm tra xem email đã tồn tại chưa
//             if (in_array($email, $existingEmails)) {
//                 $duplicateEmails[] = $email;
//                 $_SESSION['errorMessage'] = "Duplicate email: " . htmlspecialchars($email);
//                 header("Location: manage_employees.php");
//                 exit();
//             }

//             // Tìm ID phòng ban từ tên phòng ban, chỉ thực hiện nếu department không phải NULL
//             if ($department) {
//                 $stmt = $conn->prepare("SELECT id FROM Department WHERE DepartmentName = :department");
//                 $stmt->bindParam(':department', $department);
//                 $stmt->execute();
//                 $departmentID = $stmt->fetchColumn();

//                 if (!$departmentID) {
//                     $_SESSION['errorMessage'] = "Department does not exist: " . htmlspecialchars($department);
//                     header("Location: manage_employees.php");
//                     exit();
//                 }
//             } else {
//                 $departmentID = NULL; // Nếu không có phòng ban, gán NULL
//             }

//             // Hash mật khẩu mặc định
//             $password = password_hash("123456", PASSWORD_DEFAULT);

//             // Thêm nhân viên vào database
//             $stmt = $conn->prepare("INSERT INTO User (Email, FullName, Password, DepartmentID, RoleID, Status, PhoneNumber) VALUES (:email, :fullname, :password, :departmentID, 2, 'active', :phoneNumber)");
//             $stmt->bindParam(':email', $email);
//             $stmt->bindParam(':fullname', $fullName);
//             $stmt->bindParam(':password', $password);
//             $stmt->bindParam(':departmentID', $departmentID); // Có thể là NULL
//             $stmt->bindParam(':phoneNumber', $phoneNumber); // Có thể là NULL
//             $stmt->execute();
//         }

//         // Thông báo sau khi import
//         $_SESSION['successMessage'] = "Import successful!";
//         header("Location: manage_employees.php");
//         exit();
//     } catch (Exception $e) {
//         $_SESSION['errorMessage'] = "File format does not match template: file does not meet requirements.";
//         header("Location: manage_employees.php");
//         exit();
//     }
//  }


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
<?php include "../config.php"; ?>

<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Admin - EDMS - Manage Employees</title>

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
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex align-items-center">
                            <div style="margin-bottom: 15px; flex-grow: 1;"> <!-- Thêm khoảng cách dưới đây -->
                                <a href="add_employee.php" class="btn btn-primary btn-icon-split">
                                    <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                                    <span class="text">Add new employee</span>
                                </a>
                            </div>

                            <form action="import_employees.php" method="post" enctype="multipart/form-data" class="form-inline mr-3">
                                <label for="file" class="mr-2">Import: </label>
                                <input type="file" name="employee_file" id="file" accept=".xlsx" class="form-control mr-2">
                                <button type="submit" name="import" class="btn btn-success">Import</button>
                            </form>

                            <div class="d-flex align-items-center">
                                <form action="download_template.php" method="post" class="d-inline">
                                    <button type="submit" class="btn btn-secondary mx-1">
                                        <span class="icon text-white-50"><i class="fas fa-file-download"></i></span>
                                        Download Template
                                    </button>
                                </form>
                                <form action="export_employees.php" method="post" class="d-inline">
                                    <button type="submit" class="btn btn-info mx-1">
                                        <span class="icon text-white-50"><i class="fas fa-file-export"></i></span>
                                        Export Employees
                                    </button>
                                </form>
                            </div>
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
                                            <th>Status</th> <!-- Thêm cột Status -->
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
                                                    <td>
                                                        <?php
                                                        if (!empty($employee['ParentDepartment'])) {
                                                            echo htmlspecialchars($employee['ParentDepartment']) . ' - ' . htmlspecialchars($employee['ChildDepartment']);
                                                        } else {
                                                            echo htmlspecialchars($employee['ChildDepartment']); // If no parent department, just show the child
                                                        }
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <?php if ($employee['Status'] == 'active'): ?>
                                                            <span style="color: green;">Active</span>
                                                        <?php else: ?>
                                                            <span style="color: red;">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="edit_employee.php?id=<?= $employee['Id']; ?>">Edit</a> |
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
                    <div class="modal-body">Are you sure you want to inactive this employee?</div>
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
                    <div class="modal-body">Are you sure you want to change the status of this employee?</div>
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
                confirmStatusBtn.href = 'change_status_employee.php?id=' + departmentId + '&status=' + newStatus;

                // Show the status confirmation modal
                $('#statusConfirmModal').modal('show');
            }
        </script>
    </div>
</body>

</html>