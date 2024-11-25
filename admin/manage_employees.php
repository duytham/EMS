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
                    <p class="mb-4">Displays the list of employees of departments, along with adding employees, downloading the employee list, and editing or disabling that employee..</p>

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
                                            <th>Salary</th> <!-- Thêm cột Calculate Salary -->
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
                                                    <td>
                                                        <!-- Button to open "Tính lương" modal -->
                                                        <button class="btn btn-primary" onclick="openSalaryModal(<?= $employee['Id'] ?>)">Tính lương</button>
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

        <!-- Salary Calculation Modal -->
        <div class="modal fade" id="salaryModal" tabindex="-1" role="dialog" aria-labelledby="salaryModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="salaryModalLabel">Tính Lương</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="salaryForm">
                            <div class="form-group">
                                <label for="employee">Họ tên:</label>
                                <select class="form-control" id="employee" name="employee_id" required onchange="fetchSalaryDetails(this.value)">
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?= $employee['Id'] ?>"><?= htmlspecialchars($employee['FullName']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="salaryLevel">Hệ số lương:</label>
                                <input type="text" class="form-control" id="salaryLevel" name="salaryLevel" readonly>
                            </div>
                            <div class="form-group">
                                <label for="validDays">Số ngày công hợp lệ:</label>
                                <input type="number" class="form-control" id="validDays" name="validDays" readonly>
                            </div>
                            <div class="form-group">
                                <label for="invalidDays">Số ngày công không hợp lệ:</label>
                                <input type="number" class="form-control" id="invalidDays" name="invalidDays" readonly>
                            </div>
                            <div class="form-group">
                                <label for="salaryReceived">Lương nhận được:</label>
                                <input type="text" class="form-control" id="salaryReceived" name="salaryReceived" readonly>
                            </div>
                            <button type="submit" class="btn btn-success">Lưu trữ tính lương</button>
                        </form>
                        <div id="responseMessage" class="mt-3"></div>
                    </div>
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

        <script>
            // Hàm mở modal tính lương
            function openSalaryModal(employeeId) {
                const currentMonth = new Date().getMonth() + 1;
                const currentYear = new Date().getFullYear();

                $.ajax({
                    url: 'get_salary_details.php',
                    method: 'GET',
                    data: {
                        employee_id: employeeId,
                        month: currentMonth,
                        year: currentYear
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                // Gán dữ liệu vào form
                                $('#employee').val(employeeId);
                                fetchSalaryDetails(employeeId); // Gọi hàm để lấy thông tin lương
                                $('#salaryLevel').val(`${data.data.SalaryAlias} (Daily: ${data.data.DailySalary})`); // Xóa Monthly
                                $('#validDays').val(data.data.ValidDays || 0);
                                $('#invalidDays').val(data.data.InvalidDays || 0);
                                $('#salaryReceived').val(data.data.CalculatedSalary || 0);
                                $('#salaryModal').modal('show');
                            } else {
                                alert(data.message || 'Không tìm thấy thông tin lương');
                            }
                        } catch (e) {
                            console.error("Error parsing JSON:", e);
                            alert('Dữ liệu phản hồi không hợp lệ');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", xhr, status, error);
                        alert('Không thể lấy thông tin lương');
                    }
                });
            }

            // Hàm lưu thông tin lương
            function saveSalary() {
                const data = {
                    employee_id: $('#employee').val(),
                    valid_days: $('#validDays').val() || 0,
                    invalid_days: $('#invalidDays').val() || 0,
                    salary: $('#salaryReceived').val() || 0,
                    month: new Date().getMonth() + 1,
                    year: new Date().getFullYear()
                };

                debugger;  // Tạm dừng mã để kiểm tra giá trị của `data`
                console.log('Dữ liệu gửi đi:', data); // Kiểm tra xem dữ liệu có được in ra không

                $.ajax({
                    url: 'save_salary.php',
                    method: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        const messageDiv = $('#salary-message');

                        if (response.success) {
                            messageDiv.text(response.message).css('color', 'green').show();
                        } else {
                            messageDiv.text(response.message).css('color', 'red').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Lỗi AJAX:', error, xhr.responseText);
                        $('#salary-message')
                            .text('Có lỗi xảy ra khi lưu trữ thông tin lương.')
                            .css('color', 'red')
                            .show();
                    },
                });
            }

            // Hàm lấy thông tin lương
            function fetchSalaryDetails(employeeId) {
                const currentMonth = new Date().getMonth() + 1;
                const currentYear = new Date().getFullYear();

                $.ajax({
                    url: 'get_salary_details.php',
                    method: 'GET',
                    data: {
                        employee_id: employeeId,
                        month: currentMonth,
                        year: currentYear
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                // Populate the form fields
                                $('#employee').val(employeeId);
                                $('#salaryLevel').val(`${data.data.SalaryAlias} (Daily: ${formatCurrency(data.data.DailySalary)})`); // Xóa Monthly
                                $('#validDays').val(data.data.ValidDays || 0);
                                $('#invalidDays').val(data.data.InvalidDays || 0);
                                $('#salaryReceived').val(formatCurrency(data.data.CalculatedSalary || 0));
                                $('#salaryModal').modal('show');
                            } else {
                                alert(data.message || 'Không tìm thấy thông tin lương');
                            }
                        } catch (e) {
                            console.error("Error parsing JSON:", e);
                            alert('Dữ liệu phản hồi không hợp lệ');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", xhr, status, error);
                        alert('Không thể lấy thông tin lương');
                    }
                });
            }

            // Hàm lưu thông tin lương
            document.getElementById('salaryForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Ngăn chặn việc gửi form mặc định

                let formData = new FormData(this);

                fetch('save_salary.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        let responseMessage = document.getElementById('responseMessage');
                        if (data.success) {
                            responseMessage.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        } else {
                            responseMessage.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('responseMessage').innerHTML = `<div class="alert alert-danger">Lỗi: ${error.message}</div>`;
                    });
            });

            function clearSuccessMessage() {
                document.getElementById('responseMessage').innerHTML = '';
            }

            function formatCurrency(value) {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(value);
            }
        </script>
    </div>
</body>

</html>