<?php
require_once '../config.php'; // Kết nối đến file config.php
$errorMessage = ''; // Khởi tạo biến thông báo lỗi

// Kiểm tra nếu có dữ liệu được gửi từ biểu mẫu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ biểu mẫu
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phoneNumber'];
    $departmentID = $_POST['departmentID']; // ID của phòng ban
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Mật khẩu

    // Kiểm tra xem email đã tồn tại hay chưa
    $emailCheck = $conn->prepare("SELECT * FROM `User` WHERE Email = :email");
    $emailCheck->bindParam(':email', $email);
    $emailCheck->execute();

    if ($emailCheck->rowCount() > 0) {
        $errorMessage = "Email already exists. Please use another email.";
    } else {

        // Mã hóa mật khẩu
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Câu lệnh SQL để thêm nhân viên
        $sql = "INSERT INTO `User` (FullName, Email, PhoneNumber, DepartmentID, Password, RoleID) VALUES (:fullName, :email, :phoneNumber, :departmentID, :password, 2)"; // Vai trò mặc định là nhân viên (RoleID = 2)

        try {
            // Chuẩn bị câu truy vấn
            $stmt = $conn->prepare($sql);

            // Ràng buộc giá trị
            $stmt->bindParam(':fullName', $fullName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phoneNumber', $phoneNumber);
            $stmt->bindParam(':departmentID', $departmentID);
            $stmt->bindParam(':password', $hashedPassword); // Ràng buộc mật khẩu đã mã hóa

            // Thực thi truy vấn
            $stmt->execute();

            // Thiết lập thông báo thành công
            $successMessage = "Add employee successfully!";
            // Chuyển hướng về trang quản lý nhân viên
            header('Location: manage_employees.php?success=' . urlencode($successMessage));
            exit();
        } catch (PDOException $e) {
            $errorMessage = "Lỗi: " . $e->getMessage();
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

    <title>Admin - EDMS - Add employee</title>

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
                    <h2>Add new employee</h2>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fullName">Full name: </label>
                                    <input type="text" class="form-control" id="fullName" name="fullName" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email:</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phoneNumber">Phone number:</label>
                                    <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" required>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password:</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="departmentID">Phòng ban:</label>
                            <select class="form-control" id="departmentID" name="departmentID" required>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= $department['Id']; ?>"><?= $department['DepartmentName']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add employee</button>
                    </form>
                </div>
            </div>
            <?php include('../templates/footer.php') ?>
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