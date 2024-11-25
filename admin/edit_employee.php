<?php
session_start();
require_once '../config.php'; // Kết nối đến file config.php
$errorMessage = '';
$successMessage = '';

// Fetch salary levels
$stmt = $conn->prepare("SELECT * FROM salary_levels ORDER BY level");
$stmt->execute();
$salary_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra nếu có ID nhân viên được gửi từ URL
if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Lấy thông tin nhân viên từ cơ sở dữ liệu
    $stmt = $conn->prepare("SELECT * FROM `User` WHERE Id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $errorMessage = "Nhân viên không tồn tại.";
    }

    // Lấy thông tin cấu hình email của nhân viên (nếu có)
    $stmtEmailConfig = $conn->prepare("SELECT * FROM emailConfig WHERE userId = :userId");
    $stmtEmailConfig->bindParam(':userId', $userId);
    $stmtEmailConfig->execute();
    $emailConfig = $stmtEmailConfig->fetch(PDO::FETCH_ASSOC);

    // Đảm bảo gán giá trị mặc định cho checkInTime và checkOutTime nếu không có dữ liệu
    $checkInTime = isset($emailConfig['checkInTime']) ? $emailConfig['checkInTime'] : '08:00';
    $checkOutTime = isset($emailConfig['checkOutTime']) ? $emailConfig['checkOutTime'] : '17:00';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Lấy dữ liệu từ biểu mẫu
        $fullName = $_POST['fullName'];
        $email = $_POST['email'];
        $phoneNumber = $_POST['phoneNumber'];
        $departmentID = $_POST['departmentID'];
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $checkInTime = $_POST['checkInTime'];
        $checkOutTime = $_POST['checkOutTime'];
        $salary_level_id = $_POST['salary_level_id']; // Lấy giá trị salary_level_id từ biểu mẫu

        // Kiểm tra xem email đã tồn tại hay chưa
        $emailCheck = $conn->prepare("SELECT * FROM `User` WHERE Email = :email AND Id != :id");
        $emailCheck->bindParam(':email', $email);
        $emailCheck->bindParam(':id', $userId);
        $emailCheck->execute();


        if ($emailCheck->rowCount() > 0) {
            $errorMessage = "Email đã tồn tại. Vui lòng sử dụng email khác.";
        } else {
            // Cập nhật thông tin nhân viên
            $sql = "UPDATE `User` SET FullName = :fullName, Email = :email, PhoneNumber = :phoneNumber, DepartmentID = :departmentID, salary_level_id = :salary_level_id WHERE Id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':fullName', $fullName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phoneNumber', $phoneNumber);
            $stmt->bindParam(':departmentID', $departmentID);
            $stmt->bindParam(':salary_level_id', $salary_level_id); // Gán giá trị salary_level_id
            $stmt->bindParam(':id', $userId);
            $stmt->execute();

            // Cập nhật cấu hình email
            $updateEmailConfig = "UPDATE emailConfig SET checkInTime = :checkInTime, checkOutTime = :checkOutTime WHERE userId = :userId";
            $stmtEmail = $conn->prepare($updateEmailConfig);
            $stmtEmail->bindParam(':checkInTime', $checkInTime);
            $stmtEmail->bindParam(':checkOutTime', $checkOutTime);
            $stmtEmail->bindParam(':userId', $userId);
            $stmtEmail->execute();

            $_SESSION['successMessage'] = "Cập nhật nhân viên và cấu hình email thành công!";
            header("Location: manage_employees.php");
            exit();
        }
    }
}

// Lấy danh sách phòng ban
$departments = $conn->query("SELECT * FROM `Department`")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Admin - EDMS - Edit Employee</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .container {
            margin-top: 30px;
        }

        .form-control {
            width: 100%;
            max-width: 500px;
            margin: 10px 0;
            border-radius: 15px;
            /* Bo tròn các ô nhập liệu */
        }

        label {
            font-weight: bold;
        }

        button {
            margin-top: 20px;
        }

        /* Thêm kiểu lưới cho biểu mẫu */
        .row {
            margin-bottom: 20px;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('../admin/sidebar.php') ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('../templates/navbar.php') ?>

                <div class="container">
                    <h2>Edit This Employee</h2>
                    <h6>Please fill in the blank. Click Update to save any change</h6>
                    <br></br>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success">
                            <?php echo $successMessage; ?>
                        </div>
                        <meta http-equiv="refresh" content="1; url=manage_employees.php">
                    <?php elseif ($errorMessage): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fullName">Full Name:</label>
                                    <input type="text" class="form-control" id="fullName" name="fullName" value="<?= htmlspecialchars($user['FullName']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email:</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phoneNumber">Phone Number:</label>
                                    <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" value="<?= htmlspecialchars($user['PhoneNumber']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="departmentID">Department:</label>
                                    <select class="form-control" id="departmentID" name="departmentID" required>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?= $department['Id']; ?>" <?= ($department['Id'] == $user['DepartmentID']) ? 'selected' : ''; ?>><?= $department['DepartmentName']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">New Password (leave blank to keep current):</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>

                        <div class="form-group">
                            <label for="checkInTime">Check-in Time:</label>
                            <input type="time" class="form-control" id="checkInTime" name="checkInTime" value="<?= htmlspecialchars($checkInTime) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="checkOutTime">Check-out Time:</label>
                            <input type="time" class="form-control" id="checkOutTime" name="checkOutTime" value="<?= htmlspecialchars($checkOutTime) ?>" required>
                        </div>

                        <!-- Add this inside the employee edit form -->

                        <div class="form-group">
                            <label for="salary_level">Salary Level:</label>
                            <select class="form-control" id="salary_level_id" name="salary_level_id" required>
                                <?php foreach ($salary_levels as $level): ?>
                                    <option value="<?= $level['id'] ?>" <?= $level['id'] == $user['salary_level_id'] ? 'selected' : '' ?>>
                                        Level <?= $level['level'] ?> - <?= htmlspecialchars($level['alias']) ?> - Daily: <?= number_format($level['daily_salary'], 0, ',', '.') ?> VND
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                    </form>
                </div>
            </div>
            <?php include('../templates/footer.php') ?>
        </div>
    </div>
</body>

</html>