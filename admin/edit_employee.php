<?php
require_once '../config.php'; // Kết nối đến file config.php
$errorMessage = ''; // Khởi tạo biến thông báo lỗi
$successMessage = ''; // Khởi tạo biến thông báo thành công

// Kiểm tra nếu có ID nhân viên được gửi từ URL
if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Lấy thông tin nhân viên từ cơ sở dữ liệu
    $stmt = $conn->prepare("SELECT * FROM `User` WHERE Id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kiểm tra nếu nhân viên tồn tại
    if (!$user) {
        $errorMessage = "Nhân viên không tồn tại.";
    }

    // Kiểm tra nếu có dữ liệu được gửi từ biểu mẫu
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Lấy dữ liệu từ biểu mẫu
        $fullName = $_POST['fullName'];
        $email = $_POST['email'];
        $phoneNumber = $_POST['phoneNumber'];
        $departmentID = $_POST['departmentID']; // ID của phòng ban
        $password = isset($_POST['password']) ? $_POST['password'] : ''; // Mật khẩu

        // Kiểm tra xem email đã tồn tại hay chưa (trừ email hiện tại)
        $emailCheck = $conn->prepare("SELECT * FROM `User` WHERE Email = :email AND Id != :id");
        $emailCheck->bindParam(':email', $email);
        $emailCheck->bindParam(':id', $userId);
        $emailCheck->execute();

        if ($emailCheck->rowCount() > 0) {
            $errorMessage = "Email đã tồn tại. Vui lòng sử dụng email khác.";
        } else {
            // Mã hóa mật khẩu nếu được cung cấp
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE `User` SET FullName = :fullName, Email = :email, PhoneNumber = :phoneNumber, DepartmentID = :departmentID, Password = :password WHERE Id = :id";
            } else {
                $sql = "UPDATE `User` SET FullName = :fullName, Email = :email, PhoneNumber = :phoneNumber, DepartmentID = :departmentID WHERE Id = :id";
            }

            try {
                // Chuẩn bị câu truy vấn
                $stmt = $conn->prepare($sql);

                // Ràng buộc giá trị
                $stmt->bindParam(':fullName', $fullName);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phoneNumber', $phoneNumber);
                $stmt->bindParam(':departmentID', $departmentID);
                $stmt->bindParam(':id', $userId);

                if (!empty($password)) {
                    $stmt->bindParam(':password', $hashedPassword); // Ràng buộc mật khẩu đã mã hóa
                }

                // Thực thi truy vấn
                $stmt->execute();

                // Thiết lập thông báo thành công
                $successMessage = "Cập nhật nhân viên thành công!";
            } catch (PDOException $e) {
                $errorMessage = "Lỗi: " . $e->getMessage();
            }
            if ($successMessage) {
                // Chuyển hướng về trang quản lý nhân viên sau 1 giây
                header("refresh:1; url=manage_employees.php");
            }
        }
    }
}

// Lấy danh sách phòng ban để hiển thị trong combobox
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

    <title>Admin - EDMS - Edit employee</title>

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
                    <h2>Edit this employee</h2>
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
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                    </form>
                </div>
            </div>
            <?php include('../templates/footer.php') ?>
        </div>
    </div>
</body>

</html>