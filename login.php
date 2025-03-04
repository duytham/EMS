<?php
session_start();
include("config.php"); // Kết nối database qua PDO

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Truy vấn PDO để kiểm tra người dùng có tồn tại không
    $stmt = $conn->prepare("SELECT Id, FullName, Password, RoleID, Status FROM user WHERE Email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra mật khẩu
        if (password_verify($password, $row['Password'])) {
            // Kiểm tra trạng thái tài khoản
            if ($row['Status'] === 'active') {
                $_SESSION['user_id'] = $row['Id'];
                $_SESSION['user_name'] = $row['FullName'];
                $_SESSION['role_id'] = $row['RoleID'];
                $_SESSION['email'] = $email; // Đảm bảo email được lưu vào session

                // Phân quyền dựa trên RoleID
                switch ($row['RoleID']) {
                    case 1: // Admin
                        header("Location: admin/dashboard.php");
                        break;
                    case 2: // Employee
                        header("Location: employee/dashboard.php");
                        break;
                    case 3: // Department Manager
                        header("Location: department/dashboard.php");
                        break;
                    default:
                        echo "Unauthorized Access!";
                        exit;
                }
                exit();
            } else {
                $error = "Your account has been locked. Please contact the administrator for more information.";
            }
        } else {
            $error = "Password is incorrect!";
        }
    } else {
        $error = "Email does not exist!";
    }
}

if (isset($_SESSION['user_id'])) {
    // Chuyển hướng dựa trên role của user
    if ($_SESSION['role_id'] == 1) {
        header("Location: /EMS/admin/dashboard.php");
    } elseif ($_SESSION['role_id'] == 2) {
        header("Location: /EMS/employee/dashboard.php");
    } elseif ($_SESSION['role_id'] == 3) {
        header("Location: /EMS/department/dashboard.php");
    }
    exit(); // Dừng việc xử lý tiếp theo
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

    <title>EDMS - Login</title>

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

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Welcome Back!</h1>
                                    </div>
                                    <form class="user" method="post" action="">
                                        <div class="form-group">
                                            <input type="email" name="email" class="form-control form-control-user" placeholder="Enter Email Address...">
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password" class="form-control form-control-user" placeholder="Password">
                                        </div>
                                        <div class="form-group">
                                            <input type="submit" value="Login" class="btn btn-primary btn-user btn-block">
                                        </div>
                                        <!-- Hiển thị lỗi nếu có -->
                                        <?php if (isset($error)): ?>
                                            <div class="alert alert-danger">
                                                <?php echo $error; ?>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="forgot-password.php">Forgot Password?</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="register.php">Create an Account!</a>
                                    </div>
                                </div>
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