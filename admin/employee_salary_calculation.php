<?php
session_start();
// Include file cấu hình kết nối
require_once '../config.php'; // Đường dẫn đến file config của bạn

$employees = []; // Gán giá trị mặc định là một mảng rỗng.

// Lấy tháng và năm hiện tại nếu không có dữ liệu từ form hoặc URL
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m'); // Lấy tháng hiện tại (1-12)
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');    // Lấy năm hiện tại (e.g., 2024)

// Xử lý form nếu có dữ liệu POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_all_salaries'])) {
    try {
        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

        // Lấy danh sách nhân viên
        $sql = "
            SELECT 
                u.Id AS employee_id,
                u.EmploymentType,
                COALESCE(SUM(ci.status = 'Valid'), 0) AS valid_days,
                COALESCE(SUM(ci.status = 'Invalid'), 0) AS invalid_days,
                COUNT(ci.Id) AS total_days,
                s.monthly_salary AS monthly_salary, -- Sửa MonthlySalary thành monthly_salary
                s.daily_salary AS daily_salary,     -- Sửa DailySalary thành daily_salary
                u.salary_level_id
            FROM user u
            LEFT JOIN checkinout ci ON u.Id = ci.UserID AND MONTH(ci.LogDate) = :month AND YEAR(ci.LogDate) = :year
            LEFT JOIN salary_levels s ON u.salary_level_id = s.id
            WHERE u.RoleID = 2
            GROUP BY u.Id;
        ";

        if (isset($sql) && !empty($sql)) {
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':month' => $month,
                ':year' => $year
            ]);
        } else {
            echo "Lỗi: Biến \$sql chưa được khởi tạo.";
        }

        // Bắt đầu tính lương và lưu vào salary_logs
        $insertQuery = "
            INSERT INTO salary_logs (employee_id, total_days, valid_days, invalid_days, month, year, total_salary, processed_by, processed_at, salary_level)
            VALUES (:employee_id, :total_days, :valid_days, :invalid_days, :month, :year, :total_salary, :processed_by, NOW(), :salary_level)
            ON DUPLICATE KEY UPDATE 
                total_days = VALUES(total_days),
                valid_days = VALUES(valid_days),
                invalid_days = VALUES(invalid_days),
                total_salary = VALUES(total_salary),
                updated_by = :processed_by,
                updated_at = NOW()
        ";
        $insertStmt = $conn->prepare($insertQuery);

        foreach ($employees as $employee) {
            $validDays = $employee['valid_days'];
            $invalidDays = $employee['invalid_days'];
            $totalDays = $employee['total_days'];
            $salaryLevel = $employee['salary_level_id'];
            $employmentType = $employee['EmploymentType'];

            // Tính lương dựa trên loại nhân viên
            $totalSalary = 0;
            if ($employmentType === 'Full-time') {
                $monthlySalary = $employee['MonthlySalary'];
                $totalSalary = ($validDays / $totalDays) * $monthlySalary;
            } elseif ($employmentType === 'Part-time') {
                $dailySalary = $employee['DailySalary'];
                $totalSalary = $validDays * $dailySalary;
            }

            // Lưu thông tin vào salary_logs
            $insertStmt->execute([
                ':employee_id' => $employee['employee_id'],
                ':total_days' => $totalDays,
                ':valid_days' => $validDays,
                ':invalid_days' => $invalidDays,
                ':month' => $month,
                ':year' => $year,
                ':total_salary' => $totalSalary,
                ':processed_by' => $_SESSION['user_id'], // Lấy ID của người xử lý từ session
                ':salary_level' => $salaryLevel,
            ]);
        }

        $successMessage = "Calculate the salary of all employees successfully!";
    } catch (Exception $e) {
        $errorMessage = "An error occurred while calculating the salary: " . $e->getMessage();
    }
}

// Truy vấn dữ liệu nhân viên và lương theo bộ lọc
$sql = "
    SELECT 
        u.Id AS employee_id,
        u.FullName,
        CONCAT(d.DepartmentName, ' - ', IFNULL(d2.DepartmentName, 'No Parent')) AS Department,
        COALESCE(sl.alias, 'No Level') AS SalaryLevel,
        COALESCE(SUM(CASE WHEN c.status = 'Valid' THEN 1 ELSE 0 END), 0) AS valid_days,
        COALESCE(SUM(CASE WHEN c.status = 'Invalid' THEN 1 ELSE 0 END), 0) AS invalid_days,
        COALESCE(SUM(CASE WHEN c.status IN ('Valid', 'Invalid') THEN 1 ELSE 0 END), 0) AS total_work_days,
        COALESCE(SUM(CASE WHEN c.status = 'Valid' THEN sl.daily_salary ELSE 0 END), 0) AS total_daily_salary,
        CASE 
            WHEN u.EmploymentType = 'full-time' THEN 'Full-Time'
            WHEN u.EmploymentType = 'part-time' THEN 'Part-Time'
            ELSE 'Unknown'
        END AS EmploymentType
    FROM user u
    LEFT JOIN department d ON u.DepartmentID = d.Id
    LEFT JOIN department d2 ON d.ParentDepartmentID = d2.Id
    LEFT JOIN salary_levels sl ON u.salary_level_id = sl.id
    LEFT JOIN checkinout c ON u.Id = c.UserID
        AND MONTH(c.LogDate) = :month
        AND YEAR(c.LogDate) = :year
    WHERE u.RoleID = 2 -- Chỉ hiển thị nhân viên
    GROUP BY u.Id, sl.alias
    ORDER BY u.FullName;
";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':month', $month, PDO::PARAM_INT);
$stmt->bindParam(':year', $year, PDO::PARAM_INT);
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

    <style>
        .employment-full-time {
            color: greenyellow;
        }

        .employment-part-time {
            color: blue;
        }
    </style>

    <title>Admin - EDMS - Employee's Salary Calculation</title>

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
                    <?php echo "Month: $month, Year: $year"; ?>

                    <body>
                        <h1 class="h3 mb-2 text-gray-800">Employee Salary Calculation</h1>
                        <p class="mb-4">Displays the list of employees of departments, along with each employee's salary</p>

                        <?php if (isset($statusMessage)): ?>
                            <p><?= $statusMessage ?></p>
                        <?php endif; ?>

                        <div class="d-flex mb-3">
                            <form method="POST" action="" class="mr-2">
                                <button type="submit" name="calculate_salary" class="btn btn-warning">
                                    <i class="fas fa-sync-alt"></i> Recalculate
                                </button>
                            </form>

                            <a href="calculate_salary.php" class="btn btn-primary mr-2">Calculate salary</a>

                            <form method="POST" action="">
                                <button type="submit" name="calculate_all_salaries" class="btn btn-success">
                                    <i class="fas fa-calculator"></i> Calculate salary for all employees
                                </button>
                            </form>
                        </div>
                        <!-- <form method="POST" action="">
                        </form> -->

                        <!-- Form lọc theo tháng và năm -->
                        <form method="GET" action="employee_salary_calculation.php" class="mb-3">
                            <label for="month">Month:</label>
                            <select id="month" name="month" class="form-control" style="width: auto; display: inline-block;">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($i == $month) ? 'selected' : '' ?>>
                                        <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?> <!-- Hiển thị 01, 02... -->
                                    </option>
                                <?php endfor; ?>
                            </select>

                            <label for="year">Year:</label>
                            <select id="year" name="year" class="form-control" style="width: auto; display: inline-block;">
                                <?php for ($i = date('Y') - 5; $i <= date('Y') + 1; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($i == $year) ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>

                            <button type="submit" class="btn btn-primary">Filter</button>
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
                                                    <th>Employment Type</th> <!-- Thêm cột Employment Type -->
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
                                                        <td>
                                                            <?php if ($employee['EmploymentType'] === 'Full-Time'): ?>
                                                                <span class="employment-full-time"><?= $employee['EmploymentType'] ?></span>
                                                            <?php elseif ($employee['EmploymentType'] === 'Part-Time'): ?>
                                                                <span class="employment-part-time"><?= $employee['EmploymentType'] ?></span>
                                                            <?php else: ?>
                                                                <span><?= $employee['EmploymentType'] ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= $employee['total_work_days'] ?? 0 ?></td>
                                                        <td><?= $employee['valid_days'] ?? 0 ?></td>
                                                        <td><?= $employee['invalid_days'] ?? 0 ?></td>
                                                        <td><?= number_format($employee['total_daily_salary'], 0, ',', '.') . ' đ' ?></td>
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