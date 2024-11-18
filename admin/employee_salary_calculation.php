<?php
session_start();
// Include file cấu hình kết nối
require_once '../config.php'; // Đường dẫn đến file config của bạn

// Khởi tạo biến $errorMessage với giá trị mặc định
$errorMessage = '';

// Admin đang đăng nhập (thay đổi cho phù hợp với session của bạn)
$currentAdminId = 9; // ID của admin

// Khởi tạo mảng để chứa dữ liệu lương
$employees = [];

// Hàm đệ quy để SHOW cây phòng ban 
function renderDepartmentTree($departments)
{
    echo '<ul>';
    foreach ($departments as $department) {
        echo '<li>';
        echo '<input type="checkbox" name="departments[]" value="' . $department['id'] . '"> ' . htmlspecialchars($department['DepartmentName']);
        if (!empty($department['children'])) {
            renderDepartmentTree($department['children']);
        }
        echo '</li>';
    }
    echo '</ul>';
}

// Hàm đệ quy để GET cây phòng ban
function getDepartments($parentId = 0)
{
    global $conn;
    $sql = "SELECT id, DepartmentName, ParentDepartmentID FROM department WHERE ParentDepartmentID = :parentId";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':parentId' => $parentId]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($departments as $department) {
        $children = getDepartments($department['id']);
        $department['children'] = $children;
        $result[] = $department;
    }
    return $result;
}

$departmentsTree = getDepartments(); // Gọi hàm để lấy cây phòng ban

if (isset($_POST['filter'])) {
    $selectedDepartments = $_POST['departments'] ?? [];
    // Thực hiện truy vấn để lấy nhân viên theo phòng ban đã chọn
    $departmentCondition = '';
    if (!empty($selectedDepartments)) {
        $departmentCondition = 'WHERE u.DepartmentID IN (' . implode(',', array_map('intval', $selectedDepartments)) . ')';
    }

    $sql = "
        SELECT 
            u.Id AS employee_id,
            u.FullName,
            CONCAT(d.DepartmentName, ' - ', COALESCE(pd.DepartmentName, '')) AS Department,
            sl.alias AS SalaryLevel,
            sl.monthly_salary,
            sl.daily_salary,
            -- Các trường khác...
        FROM 
            user u
        LEFT JOIN 
            department d ON u.DepartmentID = d.id
        LEFT JOIN 
            department pd ON d.ParentDepartmentID = pd.id
        LEFT JOIN 
            salary_levels sl ON u.salary_level_id = sl.id
        LEFT JOIN 
            checkinout c ON u.Id = c.UserID
        $departmentCondition
        GROUP BY 
            u.Id;
    ";

    // Tiếp tục thực hiện truy vấn như trước
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
    // Truy vấn tính valid_days, invalid_days, và tính lương
    $sql = "
        SELECT 
            u.Id AS employee_id,
            u.FullName,
            CONCAT(d.DepartmentName, ' - ', COALESCE(pd.DepartmentName, '')) AS Department,
            sl.alias AS SalaryLevel,
            sl.monthly_salary,
            sl.daily_salary,
            -- Tính valid_days
            COUNT(DISTINCT CASE 
                WHEN (c.ActionType = 'checkin' AND TIME(c.CheckInTime) <= '08:00:00') 
                OR (c.ActionType = 'checkout' AND TIME(c.CheckOutTime) >= '17:00:00') 
                OR c.status = 'valid' 
                THEN c.LogDate 
            END) AS valid_days,

            -- Tính invalid_days (Check-in muộn hoặc check-out sớm)
            COUNT(DISTINCT CASE 
                WHEN (c.ActionType = 'checkin' AND TIME(c.CheckInTime) > '08:00:00') 
                OR (c.ActionType = 'checkout' AND TIME(c.CheckOutTime) < '17:00:00') 
                THEN c.LogDate 
            END) AS invalid_days,

            -- Tổng số ngày làm việc trong tháng (không phân biệt valid và invalid)
            COUNT(DISTINCT c.LogDate) AS total_work_days, 

            -- Tổng lương tính theo tháng
            ROUND(
                (
                    COUNT(DISTINCT CASE 
                        WHEN (c.ActionType = 'checkin' AND TIME(c.CheckInTime) <= '08:00:00') 
                        OR (c.ActionType = 'checkout' AND TIME(c.CheckOutTime) >= '17:00:00') 
                        OR c.status = 'valid' 
                        THEN c.LogDate 
                    END)
                    + 0.5 * COUNT(DISTINCT CASE 
                        WHEN c.status = 'rejected' THEN c.LogDate 
                    END)
                ) / 22 * sl.monthly_salary,
                2
            ) AS total_monthly_salary,

            -- Tổng lương tính theo ngày
            ROUND(
                (
                    COUNT(DISTINCT CASE 
                        WHEN (c.ActionType = 'checkin' AND TIME(c.CheckInTime) <= '08:00:00') 
                        OR (c.ActionType = 'checkout' AND TIME(c.CheckOutTime) >= '17:00:00') 
                        OR c.status = 'valid' 
                        THEN c.LogDate 
                    END)
                    + 0.5 * COUNT(DISTINCT CASE 
                        WHEN c.status = 'rejected' THEN c.LogDate 
                    END)
                ) * sl.daily_salary,
                2
            ) AS total_daily_salary
        FROM 
            user u
        LEFT JOIN 
            department d ON u.DepartmentID = d.id
        LEFT JOIN 
            department pd ON d.ParentDepartmentID = pd.id
        LEFT JOIN 
            salary_levels sl ON u.salary_level_id = sl.id
        LEFT JOIN 
            checkinout c ON u.Id = c.UserID
        WHERE 
            u.RoleID = 2 
        GROUP BY 
            u.Id;
    ";

    // Chuẩn bị và thực thi truy vấn
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    // Xử lý lưu trữ tính lương khi nút được nhấn
    if (isset($_POST['save_salary'])) {
        try {
            // Kiểm tra xem bản ghi đã tồn tại chưa
            $check_stmt = $conn->prepare("
                SELECT COUNT(*) FROM salary_logs 
                WHERE month = :month AND year = :year AND employee_id = :employee_id
            ");

            // Cập nhật thông tin lương nếu bản ghi đã tồn tại
            $update_stmt = $conn->prepare("
                UPDATE salary_logs 
                SET valid_days = :valid_days, invalid_days = :invalid_days, salary = :salary, processed_by = :processed_by 
                WHERE month = :month AND year = :year AND employee_id = :employee_id
            ");

            // Thêm mới bản ghi nếu chưa tồn tại
            $insert_stmt = $conn->prepare("
                INSERT INTO salary_logs (employee_id, valid_days, invalid_days, month, year, salary, processed_by)
                VALUES (:employee_id, :valid_days, :invalid_days, :month, :year, :salary, :processed_by)
            ");

            // Thực hiện vòng lặp xử lý dữ liệu
            foreach ($employees as $employee) {
                // Kiểm tra xem bản ghi đã tồn tại chưa
                $check_stmt->execute([
                    ':month' => date('m'),
                    ':year' => date('Y'),
                    ':employee_id' => $employee['employee_id']
                ]);

                $exists = $check_stmt->fetchColumn();

                if ($exists > 0) {
                    // Nếu bản ghi đã tồn tại, cập nhật thông tin
                    $update_stmt->execute([
                        ':employee_id' => $employee['employee_id'],
                        ':valid_days' => $employee['valid_days'],
                        ':invalid_days' => $employee['invalid_days'],
                        ':month' => date('m'),
                        ':year' => date('Y'),
                        ':salary' => $employee['total_monthly_salary'],
                        ':processed_by' => $currentAdminId
                    ]);

                    if ($update_stmt->rowCount() > 0) {
                        echo "Updated salary record for employee ID: " . $employee['employee_id'] . "<br>";
                    }
                } else {
                    // Nếu bản ghi chưa tồn tại, chèn bản ghi mới
                    $insert_stmt->execute([
                        ':employee_id' => $employee['employee_id'],
                        ':valid_days' => $employee['valid_days'],
                        ':invalid_days' => $employee['invalid_days'],
                        ':month' => date('m'),
                        ':year' => date('Y'),
                        ':salary' => $employee['total_monthly_salary'],
                        ':processed_by' => $currentAdminId
                    ]);

                    // Kiểm tra xem có bản ghi được cập nhật không
                    if ($update_stmt->rowCount() > 0) {
                        echo "Updated salary record for employee ID: " . $employee['employee_id'] . "<br>";
                    } else {
                        echo "No record updated for employee ID: " . $employee['employee_id'] . "<br>";
                    }

                    // Kiểm tra xem có bản ghi được chèn không
                    if ($insert_stmt->rowCount() > 0) {
                        echo "Inserted new salary record for employee ID: " . $employee['employee_id'] . "<br>";
                    } else {
                        echo "Failed to insert salary record for employee ID: " . $employee['employee_id'] . "<br>";
                    }
                }
            }

            $successMessage = "Salary records saved successfully!";
            // if ($stmt->errorCode() != '00000') {
            //     print_r($stmt->errorInfo());
            // }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
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

                        <form method="POST" action="">
                            <h3>Lọc theo phòng ban</h3>
                            <?php renderDepartmentTree($departmentsTree); ?>
                            <button type="submit" name="filter" class="btn btn-primary">Lọc</button>
                        </form>

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
                                                    <th>Total Monthly Salary</th>
                                                    <th>Total Daily Salary</th>
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
                                                        <td><?= number_format($employee['total_monthly_salary'], 0, ',', '.') . ' đ' ?></td> <!-- Định dạng lương tháng -->
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