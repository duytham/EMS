<?php
include "../config.php";

// Kiểm tra xem form có được gửi không
$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $departmentName = $_POST['departmentname'];
    $parentDepartmentID = !empty($_POST['parentdepartmentid']) ? $_POST['parentdepartmentid'] : null;

    try {
        // Kiểm tra trùng lặp trong cơ sở dữ liệu
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Department WHERE departmentname = ? AND (parentdepartmentid = ? OR (parentdepartmentid IS NULL AND ? IS NULL))");
        $checkStmt->execute([$departmentName, $parentDepartmentID, $parentDepartmentID]);
        $exists = $checkStmt->fetchColumn();

        if ($exists) {
            // Nếu tổ hợp tên phòng ban và phòng ban cha đã tồn tại
            $message = "Department with the name and parent department already exists. Please try again.";
        } else {
            // Chuẩn bị câu truy vấn thêm mới
            $stmt = $conn->prepare("INSERT INTO Department (DepartmentName, ParentDepartmentID, Status) VALUES (?, ?, 1)");
            $stmt->execute([$departmentName, $parentDepartmentID]);

            // Kiểm tra số hàng được chèn
            if ($stmt->rowCount() > 0) {
                // Chuyển hướng đến trang view_department.php với thông báo thành công
                header("Location: manage_department.php?status=success");
                exit();
            } else {
                $message = "Unable to add department. Please try again.";
            }
        }
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin - EDMS - Add New Department</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include '../admin/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('../templates/navbar.php') ?>

                <div class="container">
                    <h1 class="h3 mb-4 text-gray-800">Add New Department</h1>

                    <!-- Thông báo lỗi (nếu có) -->
                    <?php if ($message): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <!-- Card Form -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Department Information</h6>
                        </div>
                        <div class="card-body">
                            <form action="add_department.php" method="POST">
                                <div class="form-group">
                                    <label for="departmentname">Department name</label>
                                    <input type="text" class="form-control" id="departmentname" name="departmentname" required>
                                </div>
                                <div class="form-group">
                                    <label for="parentdepartment">Parent department</label>
                                    <select id="parentdepartment" name="parentdepartmentid" class="form-control">
                                        <option value="">Select (if any)</option>
                                        <?php
                                        $stmt = $conn->query("SELECT id, departmentname FROM Department");
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['departmentname']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary btn-block">Add Department</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
</body>
</html>
