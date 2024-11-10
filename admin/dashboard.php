<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: /login.php");
    exit();
}

include "../config.php";

// Query to get department name, parent department name, and employee count for each department
$sql = "
    SELECT d1.Id AS DepartmentId,
           d1.DepartmentName AS DepartmentName, 
           d2.DepartmentName AS ParentDepartmentName,
           COUNT(u.Id) as TotalEmployees
    FROM Department d1
    LEFT JOIN Department d2 ON d1.ParentDepartmentID = d2.Id
    LEFT JOIN User u ON u.DepartmentID = d1.Id
    GROUP BY d1.Id
";
$stmt = $conn->query($sql);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<style>
    body {
        font-family: 'Nunito', sans-serif;
        background-color: #f8f9fc;
    }

    .department-header {
        cursor: pointer;
        padding: 15px;
        background-color: #4e73df;
        /* Màu xanh dịu hơn */
        color: white;
        border-radius: 5px;
        margin-bottom: 10px;
        transition: background-color 0.3s, transform 0.3s;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .department-header:hover {
        background-color: #2e59d9;
        /* Màu tối hơn khi hover */
        transform: scale(1.02);
    }

    .sub-departments {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.5s ease-out;
        background-color: #f1f1f1;
        /* Màu nền nhẹ cho các phòng ban con */
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 10px;
        padding: 10px;
    }

    .sub-departments.show {
        max-height: 500px;
        /* Giới hạn chiều cao tối đa để tạo hiệu ứng */
    }

    .card {
        transition: transform 0.2s, box-shadow 0.2s;
        margin: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #ffffff;
        /* Màu nền trắng cho thẻ */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .card:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .card-body {
        padding: 15px;
    }

    .text-warning {
        color: #ffc107;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        /* Cho phép các thẻ xuống dòng */
        margin: -10px;
        /* Giảm khoảng cách giữa các thẻ */
    }

    .col-xl-4 {
        flex: 0 0 33.333%;
        /* Chiều rộng 1/3 cho 3 thẻ mỗi hàng */
        max-width: 33.333%;
        /* Chiều rộng tối đa */
        padding: 10px;
        /* Khoảng cách giữa các thẻ */
    }
</style>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Admin - EDMS - Dashboard</title>
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
                    <div class="row">
                        <?php
                        // Tạo một mảng để nhóm các phòng ban theo phòng ban cha
                        $departmentHierarchy = [];
                        $departmentCounts = []; // Mảng để lưu trữ số lượng phòng ban con

                        foreach ($departments as $department) {
                            $parentName = $department['ParentDepartmentName'] ?: 'No Parent Department';
                            $departmentHierarchy[$parentName][] = $department;
                            if (!isset($departmentCounts[$parentName])) {
                                $departmentCounts[$parentName] = 0;
                            }
                            $departmentCounts[$parentName]++;
                        }

                        // Hiển thị các phòng ban dựa trên cấu trúc cha-con
                        foreach ($departmentHierarchy as $parentName => $subDepartments): ?>
                            <div class="col-xl-12 mb-4">
                                <div class="department-header" onclick="toggleSubDepartments('<?php echo htmlspecialchars($parentName); ?>')">
                                    <h5 class="text-gray-800 font-weight-bold">
                                        <?php echo htmlspecialchars($parentName); ?> (<?php echo $departmentCounts[$parentName]; ?>)
                                    </h5>
                                    <i class="fas fa-chevron-down"></i> <!-- Thêm biểu tượng mũi tên -->
                                </div>
                                <div class="sub-departments" id="<?php echo htmlspecialchars($parentName); ?>">
                                    <div class="row">
                                        <?php foreach ($subDepartments as $department): ?>
                                            <div class="col-xl-4 col-md-6 mb-4">
                                                <a href="view_employee.php?department_id=<?php echo $department['DepartmentId']; ?>" style="text-decoration: none;">
                                                    <div class="card shadow h-100 py-2">
                                                        <div class="card-body">
                                                            <div class="row no-gutters align-items-center">
                                                                <div class="col mr-2">
                                                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                                        <?php echo htmlspecialchars($department['DepartmentName']); ?>
                                                                    </div>
                                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                        <?php echo htmlspecialchars($department['TotalEmployees']); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php include('../templates/footer.php') ?>
        </div>
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

    <script>
        function toggleSubDepartments(parentName) {
            var subDepartments = document.getElementById(parentName);
            subDepartments.classList.toggle('show');

            // Thay đổi biểu tượng mũi tên
            var icon = subDepartments.previousElementSibling.querySelector('i');
            if (subDepartments.classList.contains('show')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
    </script>
</body>

</html>