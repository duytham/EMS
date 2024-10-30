<?php
session_start();
require '../config.php'; // Kết nối với database
require '../vendor/autoload.php'; // Nạp thư viện PhpSpreadsheet

unset($_SESSION['errorMessage']);
unset($_SESSION['successMessage']);

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['import'])) {
    // Kiểm tra file đã tải lên chưa
    if (!empty($_FILES['employee_file']['tmp_name'])) {
        $file = $_FILES['employee_file']['tmp_name'];

        // Đọc file Excel
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Biến đếm số lượng nhân viên đã thêm
        $employeeCount = 0;
        $duplicateEmails = []; // Mảng để lưu email trùng
        $existingEmails = []; // Mảng chứa email đã có trong cơ sở dữ liệu

        // Lấy tất cả email đã có trong cơ sở dữ liệu
        $stmt = $conn->prepare("SELECT Email FROM User");
        $stmt->execute();
        $existingEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Kiểm tra nếu không có dữ liệu (chỉ có header)
        if (count($data) <= 1) {
            $_SESSION['errorMessage'] = "File does not have employee data.";
            header("Location: manage_employees.php");
            exit();
        }

        // Bắt đầu từ dòng 2 vì dòng đầu là header
        foreach ($data as $index => $row) {
            if ($index == 0) continue; // Bỏ qua dòng header

            // Đọc các giá trị từ từng hàng
            list($stt, $email, $fullname, $phoneNumber, $department_name) = $row;

            // Làm sạch dữ liệu
            $email = trim($email);
            $fullname = trim($fullname);
            $phoneNumber = trim($phoneNumber);
            $department_name = trim($department_name);

            // Kiểm tra xem có dữ liệu email hay không
            if (empty($email) || empty($fullname) || empty($department_name)) {
                continue; // Bỏ qua dòng này nếu có bất kỳ trường nào trống
            }

            // Kiểm tra xem email đã tồn tại trong bảng User hay chưa
            $stmt = $conn->prepare("SELECT COUNT(*) FROM User WHERE Email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $emailExists = $stmt->fetchColumn();

            if ($emailExists) {
                $duplicateEmails[] = $email; // Thêm email trùng vào mảng
                continue; // Bỏ qua dòng này nếu email đã tồn tại
            }

            // Lấy `department_id` từ tên phòng ban
            $stmt = $conn->prepare("SELECT id FROM Department WHERE DepartmentName = :department_name");
            $stmt->bindParam(':department_name', $department_name);
            $stmt->execute();
            $department = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($department) {
                $department_id = $department['id'];

                // Tạo mật khẩu mặc định
                $password = password_hash("123", PASSWORD_DEFAULT);

                // Thêm nhân viên vào bảng User
                $stmt = $conn->prepare("INSERT INTO User (Email, FullName, PhoneNumber, Password, DepartmentID, RoleID) VALUES (:email, :fullname, :phoneNumber, :password, :department_id, :role_id)");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':fullname', $fullname);
                $stmt->bindParam(':phoneNumber', $phoneNumber);
                $stmt->bindParam(':password', $password);
                $stmt->bindParam(':department_id', $department_id);

                // Đặt role ID cho nhân viên
                $role_id = 2; // Role cho nhân viên
                $stmt->bindParam(':role_id', $role_id);

                // Thực hiện chèn và kiểm tra thành công
                if ($stmt->execute()) {
                    // Tăng biến đếm
                    $employeeCount++;
                } else {
                    // Hiển thị lỗi nếu không thể thêm
                    $errorInfo = $stmt->errorInfo();
                    $_SESSION['errorMessage'] = "Error adding employee: " . implode(", ", $errorInfo);
                    header("Location: manage_employees.php");
                    exit();
                }
            } else {
                // Nếu không tìm thấy phòng ban
                $_SESSION['errorMessage'] = "Department '$department_name' does not exist.";
                header("Location: manage_employees.php");
                exit();
            }
        }

        // Kiểm tra xem có nhân viên nào được thêm không
        if ($employeeCount > 0) {
            $_SESSION['successMessage'] = "Data imported successfully! Added $employeeCount employees.";
        }

        //Tạo thông báo cho các email trùng
        if (count($duplicateEmails) > 0) {
            $duplicatesList = implode(", ", $duplicateEmails);
            $_SESSION['errorMessage'] = "The following emails existed and were ignored: $duplicatesList.";
        }
        // Kiểm tra xem email đã tồn tại chưa
        if (in_array($email, $existingEmails)) {
            $duplicateEmails[] = $email;
            $_SESSION['errorMessage'] = "Duplicate email: " . htmlspecialchars($email);
            header("Location: manage_employees.php");
            exit();
        }



        header("Location: manage_employees.php");
        exit();
    } else {
        $_SESSION['errorMessage'] = "Please select a file to import.";
        header("Location: manage_employees.php");
        exit();
    }
}
