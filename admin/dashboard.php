<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: /login.php");
    exit();
}

include "../config.php";

/**
 * SQL phục vụ cho show biểu đồ
 */

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

// Truy vấn dữ liệu tỉ lệ nhân sự giữa các phòng ban
$sqlEmployeeRatio = "
    SELECT d.DepartmentName, COUNT(u.Id) as TotalEmployees
    FROM Department d
    LEFT JOIN User u ON u.DepartmentID = d.Id
    GROUP BY d.Id
";
$employeeRatioStmt = $conn->query($sqlEmployeeRatio);
$employeeRatioData = $employeeRatioStmt->fetchAll(PDO::FETCH_ASSOC);

// Truy vấn độ tuổi và giới tính theo từng phòng ban
$sqlAgeGender = "
    SELECT d.DepartmentName, u.Gender, FLOOR(DATEDIFF(CURDATE(), u.BirthDate) / 365) as Age
    FROM Department d
    LEFT JOIN User u ON u.DepartmentID = d.Id
";
$ageGenderStmt = $conn->query($sqlAgeGender);
$ageGenderData = $ageGenderStmt->fetchAll(PDO::FETCH_ASSOC);

//Truy vấn số ngày công hợp lệ/ không hợp lệ
$sqlValidInvalidDays = "
    SELECT u.FullName, 
           u.DepartmentID,  -- Thêm trường DepartmentID
           SUM(CASE WHEN c.Status = 'Valid' THEN 1 ELSE 0 END) as ValidDays,
           SUM(CASE WHEN c.Status = 'Invalid' THEN 1 ELSE 0 END) as InvalidDays
    FROM User u
    LEFT JOIN checkinout c ON u.Id = c.UserID
    GROUP BY u.Id
";
$validInvalidDaysStmt = $conn->query($sqlValidInvalidDays);
$validInvalidDaysData = $validInvalidDaysStmt->fetchAll(PDO::FETCH_ASSOC);

// Truy vấn hiệu suất làm việc
$sqlPerformance = "
    SELECT 
        u.FullName,
        u.DepartmentID,
        SUM(CASE WHEN c.Status = 'Valid' THEN 1 ELSE 0 END) as ValidDays,
        SUM(CASE WHEN c.Status = 'Invalid' THEN 1 ELSE 0 END) as InvalidDays,
        COUNT(c.Id) as TotalDays,
        (SUM(CASE WHEN c.Status = 'Valid' THEN 1 ELSE 0 END) / COUNT(c.Id)) * 100 as Performance
    FROM User u
    LEFT JOIN checkinout c ON u.Id = c.UserID
    GROUP BY u.Id
";
$performanceStmt = $conn->query($sqlPerformance);
$performanceData = $performanceStmt->fetchAll(PDO::FETCH_ASSOC);

// Đưa dữ liệu về định dạng JSON để sử dụng trên frontend
$chartData = [
    "employeeRatio" => $employeeRatioData,
    "ageGender" => $ageGenderData,
    "validInvalidDays" => $validInvalidDaysData,
    "performance" => $performanceData
];


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

    .department-header {
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .department-header:hover {
        background-color: #f1f1f1;
    }

    .sub-departments.active {
        display: block;
    }

    .card {
        transition: transform 0.2s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .scrollable-content {
        max-height: 600px;
        /* Chiều cao tối đa của vùng cuộn */
        overflow-y: auto;
        /* Kích hoạt cuộn dọc */
        border: 1px solid #ddd;
        /* Đường viền để dễ nhận biết */
        background: #fff;
        /* Màu nền */
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
                        <!-- Cột bên trái: Danh sách phòng ban -->
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="scrollable-content p-3">
                                <h6 class="font-weight-bold text-gray-800">Department List</h6>
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

                                if (!empty($departmentHierarchy) && is_array($departmentHierarchy)): ?>
                                    <?php foreach ($departmentHierarchy as $parentName => $subDepartments): ?>
                                        <div class="mb-3">
                                            <div class="department-header p-3 border bg-light rounded"
                                                onclick="toggleSubDepartments('<?php echo htmlspecialchars($parentName); ?>')">
                                                <h5 class="text-gray-800 font-weight-bold mb-0">
                                                    <?php echo htmlspecialchars($parentName); ?>
                                                    <span class="badge badge-primary"><?php echo $departmentCounts[$parentName]; ?> Sub-department(s)</span>
                                                </h5>
                                                <i class="fas fa-chevron-down float-right mt-1"></i>
                                            </div>
                                            <div class="sub-departments p-2" id="<?php echo htmlspecialchars($parentName); ?>">
                                                <div class="row">
                                                    <?php foreach ($subDepartments as $department): ?>
                                                        <div class="col-xl-12 col-md-6 col-sm-12 mb-2">
                                                            <a href="view_employee.php?department_id=<?php echo $department['DepartmentId']; ?>"
                                                                style="text-decoration: none;">
                                                                <div class="card shadow-sm">
                                                                    <div class="card-body">
                                                                        <div class="row align-items-center">
                                                                            <div class="col">
                                                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                                                    <?php echo htmlspecialchars($department['DepartmentName']); ?>
                                                                                </div>
                                                                                <div class="h6 mb-0 font-weight-bold text-gray-800">
                                                                                    <?php echo htmlspecialchars($department['TotalEmployees']); ?> employee(s)
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
                                <?php else: ?>
                                    <p class="text-danger">Không có phòng ban để hiển thị.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Cột bên phải: Biểu đồ không có combobox -->
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="scrollable-content p-3" style="max-height: 500px; overflow-y: auto;"> <!-- Tăng chiều cao tối đa -->
                                <div class="mb-4">
                                    <h6 class="font-weight-bold text-gray-800">Personnel ratio</h6>
                                    <p>Statistical chart of the number of employees of each department</p>
                                    <canvas id="employeeRatioChart"></canvas>
                                </div>

                                <div class="mb-4">
                                    <h6 class="font-weight-bold text-gray-800">Gender of each department</h6>
                                    <p>Gender statistical chart of employees in each department</p>
                                    <canvas id="ageGenderChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown lọc phòng ban -->
                        Select Department:
                        <select id="departmentFilter" class="form-control mb-3" onchange="updateValidInvalidDaysChart()">
                            <option value="all">All Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['DepartmentId']; ?>">
                                    <?php echo htmlspecialchars($department['DepartmentName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Cột bên phải: Biểu đồ có combobox -->
                        <div class="col-lg-12 col-md-12 mb-4">
                            <div class="scrollable-content p-3" style="max-height: auto; max-width: auto"> <!-- Giảm chiều cao tối đa -->
                                <!-- Biểu đồ số ngày công hợp lệ/không hợp lệ -->
                                <h6 id="chartTitle" class="font-weight-bold text-gray-800">Number of valid/invalid working days</h6>
                                <p>Statistical chart of valid/invalid days for each employee</p>
                                <canvas id="validInvalidDaysChart" height="100"></canvas> <!-- Giảm kích thước canvas -->

                                <!-- Biểu đồ hiệu suất làm việc -->
                                <br></br>
                                <h6 id="chartTitle" class="font-weight-bold text-gray-800">Work performance</h6>
                                <p>Statistical chart of each employee's performance</p>
                                <canvas id="performanceChart" height="100"></canvas> <!-- Giảm kích thước canvas -->

                                <!-- Biểu đồ tỷ lệ ngày công hợp lệ/không hợp lệ -->
                                <br></br>
                                <h6 id="chartTitle" class="font-weight-bold text-gray-800">Valid and invalid ratio</h6>
                                <p>Statistical chart of the percentage of valid and invalid working days in the department.</p>
                                <canvas id="validInvalidChart" height="100"></canvas> <!-- Giảm kích thước canvas -->
                            </div>
                        </div>
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

    <!-- Nhúng chart.js vào -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

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

    <script>
        // Lấy dữ liệu từ PHP
        const chartData = <?php echo json_encode($chartData); ?>;

        /**
         * Biểu đồ cột cho tỉ lệ nhân sự giữa các phòng ban
         */
        const employeeRatioCtx = document.getElementById('employeeRatioChart').getContext('2d');
        const employeeRatioChart = new Chart(employeeRatioCtx, {
            type: 'bar', // Biểu đồ dạng cột
            data: {
                labels: chartData.employeeRatio.map(dept => dept.DepartmentName),
                datasets: [{
                    label: 'Number of Employees', // Chú thích hiển thị trên biểu đồ
                    data: chartData.employeeRatio.map(dept => dept.TotalEmployees),
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false, // Ẩn chú thích
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || 'Unknown';
                                const value = context.raw || 0;
                                return `${label}: ${value} employees`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Departments'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Number of Employees'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
        
        /**
         * Biểu đồ cột cho độ tuổi và giới tính
         */
        const ageGenderCtx = document.getElementById('ageGenderChart').getContext('2d');

        // Xử lý dữ liệu giới tính theo phòng ban
        const departmentNames = [...new Set(chartData.ageGender.map(item => item.DepartmentName))]; // Lấy danh sách tên phòng ban duy nhất
        const maleCounts = departmentNames.map(department =>
            chartData.ageGender.filter(item => item.DepartmentName === department && item.Gender === 'Male').length);
        const femaleCounts = departmentNames.map(department =>
            chartData.ageGender.filter(item => item.DepartmentName === department && item.Gender === 'Female').length);
        const otherCounts = departmentNames.map(department =>
            chartData.ageGender.filter(item => item.DepartmentName === department && item.Gender === 'Other').length);

        new Chart(ageGenderCtx, {
            type: 'bar',
            data: {
                labels: departmentNames, // Tên các phòng ban
                datasets: [{
                        label: 'Male', // Chú thích cho giới tính Nam
                        data: maleCounts, // Dữ liệu số lượng nhân viên Nam trong mỗi phòng ban
                        backgroundColor: '#4e73df',
                    },
                    {
                        label: 'Female', // Chú thích cho giới tính Nữ
                        data: femaleCounts, // Dữ liệu số lượng nhân viên Nữ trong mỗi phòng ban
                        backgroundColor: '#e74a3b',
                    },
                    {
                        label: 'Other', // Chú thích cho giới tính Khác
                        data: otherCounts, // Dữ liệu số lượng nhân viên Khác trong mỗi phòng ban
                        backgroundColor: '#36b9cc',
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top', // Vị trí chú thích
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Department', // Tiêu đề trục X
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Number of Employees', // Tiêu đề trục Y
                        },
                        beginAtZero: true
                    }
                }
            }
        });

        /**
         * Biểu đồ số ngày công hợp lệ/ không hợp lệ
         */
        let validInvalidDaysChart;

        function updateValidInvalidDaysChart() {
            const selectedDepartmentId = document.getElementById('departmentFilter').value;
            const filteredData = selectedDepartmentId === 'all' ?
                chartData.validInvalidDays :
                chartData.validInvalidDays.filter(user => user.DepartmentID && user.DepartmentID == selectedDepartmentId);

            const validInvalidDaysCtx = document.getElementById('validInvalidDaysChart').getContext('2d');

            if (validInvalidDaysChart) {
                validInvalidDaysChart.destroy();
            }

            validInvalidDaysChart = new Chart(validInvalidDaysCtx, {
                type: 'bar',
                data: {
                    labels: filteredData.map(user => user.FullName.length > 20 ? user.FullName.slice(0, 17) + '...' : user.FullName),
                    datasets: [{
                            label: 'Vaild Working Days',
                            data: filteredData.map(user => user.ValidDays),
                            backgroundColor: '#1cc88a',
                        },
                        {
                            label: 'Invalid Working Days',
                            data: filteredData.map(user => user.InvalidDays),
                            backgroundColor: '#e74a3b',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Employees',
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Number of working days',
                            },
                            beginAtZero: true,
                        }
                    }
                }
            });
        }
        // Gọi hàm để hiển thị biểu đồ ngay khi trang được tải
        document.addEventListener("DOMContentLoaded", () => {
            updateValidInvalidDaysChart();
        });

        /**
         * Biểu đồ thanh ngang cho hiệu suất làm việc
         */
        let performanceChart; // Khai báo biến toàn cục để lưu biểu đồ

        function updatePerformanceChart() {
            const selectedDepartmentId = document.getElementById('departmentFilter').value;
            const filteredData = selectedDepartmentId === 'all' ? chartData.performance : chartData.performance.filter(user => user.DepartmentID == selectedDepartmentId);

            const performanceCtx = document.getElementById('performanceChart').getContext('2d');

            // Xóa biểu đồ cũ nếu có
            if (performanceChart) {
                performanceChart.destroy();
            }

            // Tạo biểu đồ mới
            performanceChart = new Chart(performanceCtx, {
                type: 'bar', // Biểu đồ thanh ngang
                data: {
                    labels: filteredData.map(user => user.FullName),
                    datasets: [{
                        label: 'Working Performence (%)',
                        data: filteredData.map(user => user.Performance), // Hiệu suất làm việc
                        backgroundColor: '#4e73df', // Màu của các thanh
                        borderColor: '#4e73df', // Màu viền
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y', // Chuyển trục x thành trục y (biểu đồ thanh ngang)
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Performance (%)'
                            },
                            beginAtZero: true
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Employees'
                            }
                        }
                    }
                }
            });
        }
        // Gọi hàm để hiển thị biểu đồ ngay khi trang được tải
        updatePerformanceChart();

        /**
         * Biểu đồ thanh ngang cho tỷ lệ hợp lệ và không hợp lệ
         */
        let validInvalidChart; // Khai báo biến toàn cục để lưu biểu đồ

        function updateValidInvalidChart() {
            const selectedDepartmentId = document.getElementById('departmentFilter').value;
            const filteredData = selectedDepartmentId === 'all' ? chartData.validInvalidDays : chartData.validInvalidDays.filter(user => user.DepartmentID == selectedDepartmentId);

            const validInvalidCtx = document.getElementById('validInvalidChart').getContext('2d');

            // Xóa biểu đồ cũ nếu có
            if (validInvalidChart) {
                validInvalidChart.destroy();
            }

            // Tạo biểu đồ mới
            validInvalidChart = new Chart(validInvalidCtx, {
                type: 'bar', // Biểu đồ thanh ngang
                data: {
                    labels: filteredData.map(user => user.FullName),
                    datasets: [{
                            label: 'Vaild Working Days (%)',
                            data: filteredData.map(user => (user.ValidDays / (user.ValidDays + user.InvalidDays)) * 100), // Tính tỷ lệ hợp lệ
                            backgroundColor: '#1cc88a', // Màu của thanh hợp lệ
                            borderColor: '#1cc88a', // Màu viền
                            borderWidth: 1
                        },
                        {
                            label: 'Invaild working days (%)',
                            data: filteredData.map(user => (user.InvalidDays / (user.ValidDays + user.InvalidDays)) * 100), // Tính tỷ lệ không hợp lệ
                            backgroundColor: '#e74a3b', // Màu của thanh không hợp lệ
                            borderColor: '#e74a3b', // Màu viền
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    indexAxis: 'y', // Chuyển trục x thành trục y (biểu đồ thanh ngang)
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Percentage (%)'
                            },
                            beginAtZero: true
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Employees'
                            }
                        }
                    }
                }
            });
        }
        // Gọi hàm để hiển thị biểu đồ ngay khi trang được tải
        updateValidInvalidChart();

        // Gọi hàm cập nhật biểu đồ khi thay đổi phòng ban
        document.getElementById('departmentFilter').addEventListener('change', () => {
            updateValidInvalidDaysChart(); // Biểu đồ ngày công hợp lệ/không hợp lệ
            updatePerformanceChart(); // Biểu đồ hiệu suất làm việc
            updateValidInvalidChart(); // Biểu đồ tỷ lệ hợp lệ/không hợp lệ
        });
    </script>
</body>

</html>