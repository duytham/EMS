<?php
include "../config.php";

// Kiểm tra xem có ID phòng ban trong URL không
if (!isset($_GET['id'])) {
    header("Location: manage_department.php");
    exit();
}

$departmentID = $_GET['id'];
$message = '';

// Kiểm tra xem form có được gửi không
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $departmentName = $_POST['departmentname'];
    $parentDepartmentID = !empty($_POST['parentdepartmentid']) ? $_POST['parentdepartmentid'] : null;

    try {
        // Cập nhật phòng ban
        $stmt = $conn->prepare("UPDATE Department SET DepartmentName = ?, ParentDepartmentID = ? WHERE id = ?");
        $stmt->execute([$departmentName, $parentDepartmentID, $departmentID]);

        // Kiểm tra số hàng được cập nhật
        if ($stmt->rowCount() > 0) {
            header("Location: manage_department.php?status=success");
            exit();
        } else {
            $message = "Không thể cập nhật phòng ban. Vui lòng thử lại.";
        }
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
    }
}

// Lấy thông tin phòng ban hiện tại
$stmt = $conn->prepare("SELECT DepartmentName, ParentDepartmentID, Status FROM Department WHERE id = ?");
$stmt->execute([$departmentID]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    header("Location: manage_department.php");
    exit();
}

// Kiểm tra trạng thái
if (isset($_GET['toggle_status'])) {
    $departmentID = $_GET['toggle_status'];

    // Lấy trạng thái hiện tại
    $stmt = $conn->prepare("SELECT Status FROM Department WHERE id = ?");
    $stmt->execute([$departmentID]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($department) {
        // Chuyển đổi trạng thái
        $newStatus = ($department['Status'] == 1) ? 0 : 1;
        $stmt = $conn->prepare("UPDATE Department SET Status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $departmentID]);

        // Kiểm tra xem cập nhật thành công hay không
        if ($stmt->rowCount() > 0) {
            echo "Trạng thái đã được cập nhật thành công.";
        } else {
            echo "Không thể cập nhật trạng thái.";
        }
    }
    header("Location: manage_department.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin - EDMS - Edit Department</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Edit Department</h1>

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
                            <form action="edit_department.php?id=<?php echo $departmentID; ?>" method="POST">
                                <div class="form-group">
                                    <label for="departmentname">Department Name</label>
                                    <input type="text" class="form-control" id="departmentname" name="departmentname" value="<?php echo htmlspecialchars($department['DepartmentName']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="parentdepartment">Parent Department</label>
                                    <select id="parentdepartment" name="parentdepartmentid" class="form-control">
                                        <option value="">Select parent department (if any)</option>
                                        <?php
                                        $stmt = $conn->query("SELECT id, departmentname FROM Department");
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $selected = ($row['id'] == $department['ParentDepartmentID']) ? 'selected' : '';
                                            echo "<option value='" . $row['id'] . "' $selected>" . htmlspecialchars($row['departmentname']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary btn-block">Update Department</button>
                            </form>
                            <br>
                            <!-- Nút chuyển đổi trạng thái -->
                            <form action="edit_department.php?id=<?php echo $departmentID; ?>" method="GET">
                                <button type="submit" name="toggle_status" class="btn btn-warning btn-block">
                                    <?php echo $department['Status'] == 1 ? 'Deactivate Department' : 'Activate Department'; ?>
                                </button>
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