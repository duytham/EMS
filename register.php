<?php

// Bật báo cáo lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include file cấu hình database
include 'config.php';

// Biến để lưu thông báo lỗi
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $full_name = $_POST['first_name'] . ' ' . $_POST['last_name']; // Ghép tên và họ
    $email = $_POST['email'];
    $password = $_POST['password'];
    $repeat_password = $_POST['repeat_password'];
    $role_id = 2; // Giá trị mặc định cho role_id

    // Kiểm tra xem mật khẩu có khớp không
    if ($password !== $repeat_password) {
        $error_message = "Mật khẩu không khớp.";
        exit;
    } else {
        // Mã hóa mật khẩu để bảo mật
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Chuẩn bị truy vấn để thêm người dùng vào bảng User
        $sql = "INSERT INTO `User` (FullName, Email, Password, RoleID) 
            VALUES (:full_name, :email, :password, :role_id)";

        try {
            // Thực thi truy vấn
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role_id', $role_id);
            $stmt->execute();

            // Hiển thị thông báo đăng ký thành công
            echo "<script>alert('Đăng ký thành công!'); window.location.href='login.php';</script>";
            exit;
        } catch (PDOException $e) {
            $error_message = "Lỗi: " . $e->getMessage();
        }
    }
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

    <title>EDMS - Register</title>
    <style>
        .error {
            color: red;
        }
    </style>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body class="bg-gradient-primary">

    <div class="container">

        <div class="card o-hidden border-0 shadow-lg my-5">
            <div class="card-body p-0">
                <!-- Nested Row within Card Body -->
                <div class="row">
                    <div class="col-lg-5 d-none d-lg-block bg-register-image"></div>
                    <div class="col-lg-7">
                        <div class="p-5">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">Create an Account!</h1>
                            </div>
                            <form class="user" method="POST" action="register.php">
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="text" class="form-control form-control-user" name="first_name" id="exampleFirstName" placeholder="First Name" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control form-control-user" name="last_name" id="exampleLastName" placeholder="Last Name" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="email" class="form-control form-control-user" name="email" id="exampleInputEmail" placeholder="Email Address" required>
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <input type="password" class="form-control form-control-user" name="password" id="exampleInputPassword" placeholder="Password" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="password" class="form-control form-control-user" name="repeat_password" id="exampleRepeatPassword" placeholder="Repeat Password" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-user btn-block">
                                    Register Account
                                </button>
                                <?php if (!empty($error_message)){
                                    echo '<script>alert("Lỗi mẹ rồi")</script>';
                                }?>
                            </form>
                            <hr>
                            <div class="text-center">
                                <a class="small" href="forgot-password.php">Forgot Password?</a>
                            </div>
                            <div class="text-center">
                                <a class="small" href="login.php">Already have an account? Login!</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
</body>

</html>