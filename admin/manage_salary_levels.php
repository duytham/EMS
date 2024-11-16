<?php
session_start();
include '../config.php';

// Handle form submission for adding or updating salary levels
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $level = $_POST['level'];
    $alias = $_POST['alias'];

    // Loại bỏ dấu phân cách hàng nghìn
    $monthly_salary = str_replace('.', '', $_POST['monthly_salary']);
    $daily_salary = str_replace('.', '', $_POST['daily_salary']);

    // Kiểm tra xem giá trị có phải là số và không âm
    if (!is_numeric($monthly_salary) || !is_numeric($daily_salary) || $monthly_salary < 0 || $daily_salary < 0) {
        die("Invalid salary value. Please try again.");
    }

    $id = $_POST['id'] ?? null;

    /**
     * Kiểm tra trùng alias cho tất cả các mức lương (alias phải là DUY NHẤT)
     */
    $stmt = $conn->prepare("SELECT * FROM salary_levels WHERE alias = ? AND (id != ? OR ? IS NULL)");
    $stmt->execute([$alias, $id, $id]);

    /**
     * Kiểm tra trùng alias cho mức lương hiện tại (alias phải là duy nhất cho mỗi mức lương)
     */
    // $stmt = $conn->prepare("SELECT * FROM salary_levels WHERE alias = ? AND level = ? AND (id != ? OR ? IS NULL)");
    // $stmt->execute([$alias, $level, $id, $id]);

    if ($stmt->fetch()) {
        $_SESSION['error_message'] = "Alias $alias already exists for this level. Please choose a different alias.";
        header('Location: manage_salary_levels.php');
        exit();
    }

    if ($id) {
        // Cập nhật mức lương hiện có
        $stmt = $conn->prepare("UPDATE salary_levels SET level = ?, alias = ?, monthly_salary = ?, daily_salary = ? WHERE id = ?");
        $stmt->execute([$level, $alias, $monthly_salary, $daily_salary, $id]);
        $_SESSION['message'] = "Successfully updated the salary grade coefficient.";
    } else {
        // Thêm mức lương mới
        $stmt = $conn->prepare("INSERT INTO salary_levels (level, alias, monthly_salary, daily_salary) VALUES (?, ?, ?, ?)");
        $stmt->execute([$level, $alias, $monthly_salary, $daily_salary]);
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
$stmt = $conn->prepare("SELECT * FROM salary_levels ORDER BY level, alias");
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
                    <p class="mb-4">Manage employee salary levels, including adding, editing, and deleting existing salary levels. Ensure each employee is assigned a unique salary coefficient</p>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error_message'] ?>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['message'] ?>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Add New Salary Levels</h6>
                        </div>
                        <div class="card-body">
                            <form action="manage_salary_levels.php" method="POST" class="mb-4">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="level">Level:</label>
                                        <input type="number" class="form-control" id="level" name="level" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="alias">Alias:</label>
                                        <input type="text" class="form-control" id="alias" name="alias" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="monthly_salary">Monthly Salary:</label>
                                        <input type="text" class="form-control" id="monthly_salary" name="monthly_salary" required oninput="formatNumber(this)">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="daily_salary">Daily Salary:</label>
                                        <input type="text" class="form-control" id="daily_salary" name="daily_salary" required oninput="formatNumber(this)">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">Save</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Salary Levels List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Level</th>
                                            <th>Alias</th> <!-- Cột Alias mới -->
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
                                                <td><?= htmlspecialchars($level['alias']) ?></td> <!-- Hiển thị Alias -->
                                                <td><?= htmlspecialchars(number_format($level['monthly_salary'], 0, ',', '.')) . ' đ' ?></td>
                                                <td><?= htmlspecialchars(number_format($level['daily_salary'], 0, ',', '.')) . ' đ' ?></td>
                                                <td>
                                                    <button class="btn btn-warning btn-sm" onclick="editSalaryLevel(<?= htmlspecialchars(json_encode($level)) ?>)">Edit</button>
                                                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= htmlspecialchars($level['id']) ?>)">Delete</button>
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
                                    document.getElementById('monthly_salary').value = formatNumberForEdit(level.monthly_salary);
                                    document.getElementById('daily_salary').value = formatNumberForEdit(level.daily_salary);
                                }

                                function formatNumber(input) {
                                    // Remove all non-digit characters
                                    let value = input.value.replace(/[^0-9]/g, '');
                                    // Add thousand separators
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

                                // Định dạng số với dấu phân tách hàng nghìn cho chế độ edit
                                function formatNumberForEdit(number) {
                                    // Làm tròn số về số nguyên rồi định dạng dấu phân tách hàng nghìn
                                    let roundedNumber = Math.round(number);
                                    return roundedNumber.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                }

                                function confirmDelete(id) {
                                    // Set the delete URL with the specified ID in the confirmation button
                                    document.getElementById('confirmDeleteBtn').href = 'manage_salary_levels.php?delete=' + id;
                                    // Show the delete confirmation modal
                                    $('#deleteModal').modal('show');
                                }
                            </script>
                        </div>

                    </div>
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

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">Are you sure you want to delete this salary level?</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                        <a class="btn btn-danger" id="confirmDeleteBtn" href="#">Delete</a>
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
    </div>

</body>

</html>