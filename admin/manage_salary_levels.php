<?php
session_start();
include '../config.php';

// Handle form submission for adding or updating salary levels
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $level = $_POST['level'];

    // Loại bỏ dấu phân cách hàng nghìn
    $monthly_salary = str_replace('.', '', $_POST['monthly_salary']);
    $daily_salary = str_replace('.', '', $_POST['daily_salary']);

    // Kiểm tra xem giá trị có phải là số và không âm
    if (!is_numeric($monthly_salary) || !is_numeric($daily_salary) || $monthly_salary < 0 || $daily_salary < 0) {
        die("Invalid salary value. Please try again.");
    }

    $id = $_POST['id'] ?? null;

    if ($id) {
        // Cập nhật mức lương hiện có
        $stmt = $conn->prepare("UPDATE salary_levels SET level = ?, monthly_salary = ?, daily_salary = ? WHERE id = ?");
        $stmt->execute([$level, $monthly_salary, $daily_salary, $id]);
        $_SESSION['message'] = "Cập nhật hệ số bậc lương thành công.";
    } else {
        // Thêm mức lương mới
        $stmt = $conn->prepare("INSERT INTO salary_levels (level, monthly_salary, daily_salary) VALUES (?, ?, ?)");
        $stmt->execute([$level, $monthly_salary, $daily_salary]);
        $_SESSION['message'] = "Successfully added the salary grade coefficient.";
    }

    header('Location: manage_salary_levels.php');
    exit();
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM salary_levels WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['message'] = "Successfully removed the salary grade coefficient.";
    header('Location: manage_salary_levels.php');
    exit();
}

// Fetch all salary levels
$stmt = $conn->prepare("SELECT * FROM salary_levels ORDER BY level");
$stmt->execute();
$salary_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Admin - EDMS - Manage Salary Levels</title>
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
                <div class="container">
                    <h2>Manage Salary Levels</h2>

                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['message'] ?>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <form action="manage_salary_levels.php" method="POST">
                        <input type="hidden" name="id" id="id">
                        <div class="form-group">
                            <label for="level">Level:</label>
                            <input type="number" class="form-control" id="level" name="level" required>
                        </div>
                        <div class="form-group">
                            <label for="monthly_salary">Monthly Salary:</label>
                            <input type="text" step="0.01" class="form-control" id="monthly_salary" name="monthly_salary" required oninput="formatNumber(this)">
                        </div>
                        <div class="form-group">
                            <label for="daily_salary">Daily Salary:</label>
                            <input type="text" step="0.01" class="form-control" id="daily_salary" name="daily_salary" required oninput="formatNumber(this)">
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>

                    <div class="card-body">
                        <div class="table-responsive">
                            <h3>Salary Levels</h3>
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Level</th>
                                        <th>Monthly Salary</th>
                                        <th>Daily Salary</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salary_levels as $level): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($level['id']) ?></td>
                                            <td><?= htmlspecialchars($level['level']) ?></td>
                                            <td><?= htmlspecialchars(number_format($level['monthly_salary'], 0, ',', '.')) . ' đ' ?></td>
                                            <td><?= htmlspecialchars(number_format($level['daily_salary'], 0, ',', '.')) . ' đ' ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm" onclick="editSalaryLevel(<?= htmlspecialchars(json_encode($level)) ?>)">Edit</button>
                                                <a href="manage_salary_levels.php?delete=<?= htmlspecialchars($level['id']) ?>" class="btn btn-danger btn-sm">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <script>
                            function editSalaryLevel(level) {
                                document.getElementById('id').value = level.id;
                                document.getElementById('level').value = level.level;
                                document.getElementById('monthly_salary').value = level.monthly_salary;
                                document.getElementById('daily_salary').value = level.daily_salary;
                            }

                            function formatNumber(input) {
                                // Xóa tất cả ký tự không phải số
                                let value = input.value.replace(/[^0-9]/g, '');
                                // Định dạng số theo hàng triệu, trăm, chục
                                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                input.value = value;
                            }

                            // Thêm sự kiện cho các input
                            document.getElementById('monthly_salary').addEventListener('input', function() {
                                formatNumber(this);
                            });

                            document.getElementById('daily_salary').addEventListener('input', function() {
                                formatNumber(this);
                            });

                            function formatNumberForEdit(number) {
                                return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        </script>
                    </div>
                </div>
                <?php include('../templates/footer.php') ?>
            </div>

            <a class="scroll-to-top rounded" href="#page-top">
                <i class="fas fa-angle-up"></i>
            </a>

            <!-- Logout Modal-->
            <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
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

            <script src="../vendor/jquery/jquery.min.js"></script>
            <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
            <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
            <script src="../js/sb-admin-2.min.js"></script>
        </div>
    </div>
</body>

</html>