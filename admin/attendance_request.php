<?php
session_start();

// Kết nối cơ sở dữ liệu
include_once('../config.php');

// Lấy ngày từ GET
$date = $_GET['date'] ?? date('Y-m-d');

// Kết nối cơ sở dữ liệu và lấy danh sách yêu cầu từ bảng CheckInOut cho ngày đã chọn
$stmt = $conn->prepare("
    SELECT c.Id, u.FullName AS user, u.Email, u.PhoneNumber, d.DepartmentName AS department, 
           c.reason AS reason, c.status AS status, c.ActionType AS action_type
    FROM CheckInOut c
    JOIN User u ON c.UserID = u.Id
    JOIN Department d ON u.DepartmentID = d.Id
    WHERE DATE(c.LogDate) = :date
");
$stmt->execute(['date' => $date]);
$requests = $stmt->fetchAll();

// // Xử lý yêu cầu từ admin - chỉ cho phép thay đổi 1 lần từ 'Pending' -> 'Valid' hoặc 'Invalid'
// if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//     $attendanceId = $_POST['attendance_id'] ?? null;

//     if ($attendanceId) {
//         if (isset($_POST['accept'])) {
//             // Admin chọn Accept -> cập nhật trạng thái thành 'Valid'
//             $stmt = $conn->prepare("UPDATE CheckInOut SET status = 'Valid' WHERE Id = :id AND status = 'Pending'");
//             if ($stmt->execute(['id' => $attendanceId])) {
//                 $_SESSION['successMessage'] = "Request has been accepted and marked as Valid.";
//             } else {
//                 $_SESSION['errorMessage'] = "Failed to accept the request. Please try again.";
//             }
//         } elseif (isset($_POST['reject'])) {
//             // Admin chọn Reject -> cập nhật trạng thái thành 'Invalid'
//             $stmt = $conn->prepare("UPDATE CheckInOut SET status = 'Invalid' WHERE Id = :id AND status = 'Pending'");
//             if ($stmt->execute(['id' => $attendanceId])) {
//                 $_SESSION['successMessage'] = "Request has been rejected and marked as Invalid.";
//             } else {
//                 $_SESSION['errorMessage'] = "Failed to reject the request. Please try again.";
//             }
//         }

        //Xử lý yêu cầu từ admin - cho phép thay đổi nhiều lần 
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $attendanceId = $_POST['attendance_id'] ?? null;

            if ($attendanceId) {
                if (isset($_POST['accept'])) {
                    // Admin chọn Accept -> Cập nhật trạng thái thành 'Valid'
                    $stmt = $conn->prepare("UPDATE CheckInOut SET status = 'Valid' WHERE Id = :id");
                    if ($stmt->execute(['id' => $attendanceId])) {
                        $_SESSION['successMessage'] = "Request has been accepted and marked as Valid.";
                    } else {
                        $_SESSION['errorMessage'] = "Failed to accept the request. Please try again.";
                    }
                } elseif (isset($_POST['reject'])) {
                    // Admin chọn Reject -> Cập nhật trạng thái thành 'Invalid'
                    $stmt = $conn->prepare("UPDATE CheckInOut SET status = 'Invalid' WHERE Id = :id");
                    if ($stmt->execute(['id' => $attendanceId])) {
                        $_SESSION['successMessage'] = "Request has been rejected and marked as Invalid.";
                    } else {
                        $_SESSION['errorMessage'] = "Failed to reject the request. Please try again.";
                    }
                }
                // Reload the page to show the updated list
                header("Location: attendance_request.php"); // Đổi 'admin_page.php' thành URL trang admin của bạn
                exit();
    }
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

    <title>Admin - EDMS - Manage Attendance Requests</title>

    <style>
        .action-checkin {
            color: blue;
            /* Màu cho Check-in */
        }

        .action-checkout {
            color: grey;
            /* Màu cho Check-out */
        }

        .status-valid {
            color: green;
            /* Màu cho trạng thái 'Valid' */
        }

        .status-invalid {
            color: red;
            /* Màu cho trạng thái 'Invalid' */
        }

        .status-pending {
            color: orange;
            /* Màu cho trạng thái 'Pending' */
        }
    </style>
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
                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- DataTales Example -->
                    <div class="container-fluid">
                        <h1 class="h3 mb-2 text-gray-800">Manage Attendance Requests</h1>
                        <p class="mb-4">Review and manage user attendance requests, including approval or rejection of explanations.</p>

                        <!-- Hiển thị thông báo thành công hoặc lỗi -->
                        <?php if (isset($_SESSION['successMessage'])) {
                            echo '<div class="alert alert-success">' . $_SESSION['successMessage'] . '</div>';
                            unset($_SESSION['successMessage']); // Xóa thông báo sau khi hiển thị
                        }

                        if (isset($_SESSION['errorMessage'])) {
                            echo '<div class="alert alert-danger">' . $_SESSION['errorMessage'] . '</div>';
                            unset($_SESSION['errorMessage']); // Xóa thông báo sau khi hiển thị
                        } ?>

                        <?php
                        // Kiểm tra tham số status để hiển thị thông báo thành công
                        if (isset($_GET['status']) && $_GET['status'] == 'success') {
                            echo '<div class="alert alert-success">Department added successfully!</div>';
                        }
                        ?>
                        <form method="GET" action="">
                            <label for="date">Select date:</label>
                            <input type="date" name="date" value="<?php echo isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'); ?>">
                            <button type="submit" class="btn btn-primary">Lọc</button>
                        </form>

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">List of requests for date <?= date('d-m-Y', strtotime($date)) ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>STT</th>
                                                <th>User</th>
                                                <th>Email</th>
                                                <!-- <th>Phone Number</th>
                                                <th>Department</th> -->
                                                <th>Reason</th>
                                                <th>Status</th>
                                                <th>Action Type</th> <!-- Cột mới cho loại hành động -->
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($requests as $index => $request): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= htmlspecialchars($request['user']) ?></td>
                                                    <td><?= htmlspecialchars($request['Email']) ?></td>
                                                    <!-- <td><?= htmlspecialchars($request['PhoneNumber']) ?></td>
                                                    <td><?= htmlspecialchars($request['department']) ?></td> -->
                                                    <td><?= htmlspecialchars($request['reason']) ?></td>
                                                    <td class="<?= htmlspecialchars($request['status'] == 'Valid' ? 'status-valid' : ($request['status'] == 'Invalid' ? 'status-invalid' : 'status-pending')) ?>">
                                                        <?= htmlspecialchars($request['status']) ?>
                                                    </td>
                                                    <!-- Action Type Column -->
                                                    <td class="<?= $request['action_type'] == 'checkin' ? 'action-checkin' : 'action-checkout' ?>">
                                                        <?= $request['action_type'] == 'checkin' ? 'Check-in' : 'Check-out' ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-success" onclick="confirmAction(<?= $request['Id'] ?>, 'accept', '<?= $request['status'] ?>')">Accept</button>
                                                        <button type="button" class="btn btn-danger" onclick="confirmAction(<?= $request['Id'] ?>, 'reject', '<?= $request['status'] ?>')">Reject</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Content Wrapper -->
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

        <!-- Confirm Action Modal -->
        <div class="modal fade" id="confirmActionModal" tabindex="-1" role="dialog" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmActionModalLabel">Confirm Action</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">Are you sure you want to proceed with this action?</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" id="confirmActionBtn">OK</button>
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
    <script>
        let actionId, actionType;

        function confirmAction(id, type, status) {
            actionId = id;
            actionType = type;

            // Set up the confirmation message based on the status
            let message = "Are you sure you want to proceed with this action?";
            if (status === 'Pending') {
                message = "Are you sure you want to " + (type === 'accept' ? "accept" : "reject") + " this request?";
            } else if (status === 'Valid' && type === 'reject') {
                message = "This request has already been accepted. Do you want to change it to 'Invalid'?";
            } else if (status === 'Invalid' && type === 'accept') {
                message = "This request has already been rejected. Do you want to change it to 'Valid'?";
            }

            // Set the modal message and show the modal
            document.getElementById('confirmActionModalLabel').innerText = "Confirm Action";
            document.querySelector('#confirmActionModal .modal-body').innerText = message;
            $('#confirmActionModal').modal('show');
        }

        document.getElementById('confirmActionBtn').addEventListener('click', function() {
            // Create a form dynamically to submit the action
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = ''; // Current page

            // Add hidden inputs for attendance_id and action type
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'attendance_id';
            idInput.value = actionId;
            form.appendChild(idInput);

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = actionType;
            actionInput.value = true;
            form.appendChild(actionInput);

            // Append form to body and submit
            document.body.appendChild(form);
            form.submit();
        });
    </script>

</body>

</html>