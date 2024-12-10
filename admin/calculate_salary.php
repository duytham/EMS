<?php
// Kết nối với cơ sở dữ liệu
session_start(); // Bắt đầu phiên
require_once '../config.php';  // Sử dụng file config.php với PDO
//include 'salary_functions.php'; // Bao gồm file chứa các hàm SQL

// Lấy danh sách phòng ban
$departments = $conn->query("SELECT Id, DepartmentName FROM department")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<?php
include "../config.php";
?>

<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <style>
        /* Style cho các phần tử nhân viên */
        .employee-item {
            cursor: pointer;
            /* Đảm bảo con trỏ thay đổi thành bàn tay khi hover */
            padding: 10px;
            /* Cung cấp một chút không gian */
            background-color: transparent;
            /* Không có nền */
            border: none;
            /* Không viền */
            color: inherit;
            /* Sử dụng màu sắc văn bản mặc định */
            text-decoration: none;
            /* Không gạch chân */
            display: block;
            /* Hiển thị như một block */
        }

        /* Hover effect nếu muốn */
        .employee-item:hover {
            background-color: #f0f0f0;
            /* Thêm hiệu ứng hover nhẹ */
            color: #007bff;
            /* Màu chữ khi hover */
        }

        .employee-button {
            background: none;
            /* Không có nền */
            border: none;
            /* Không có viền */
            color: inherit;
            /* Kế thừa màu chữ từ bố cục cha */
            text-decoration: none;
            /* Không có gạch chân */
            cursor: pointer;
            /* Đổi con trỏ chuột thành hình tay khi rê qua */
        }

        .employee-button:focus {
            outline: none;
            /* Xóa viền khi nút được chọn */
        }

        .employee-button:hover {
            text-decoration: underline;
            /* Thêm gạch chân khi hover để rõ ràng hơn */
        }
    </style>
    <title>Admin - EDMS - Salary Calculation</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include('../admin/sidebar.php') ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php include('../templates/navbar.php') ?>
                <div class="container-fluid">

                    <!-- DataTales Example -->
                    <div class="container-fluid">
                        <h1 class="h3 mb-2 text-gray-800">Salary Calculation</h1>
                        <p class="mb-4">Choose department first, the system will display the employees in that department to perform payroll</p>

                        <a href="employee_salary_calculation.php" >Back to Employee Salary Calculation</a>

                        <div>
                            <!-- Form để chọn Department -->
                            <form id="salaryForm" class="mt-4">
                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <select id="department" name="department" class="form-control">
                                        <option value="">Select Department</option>
                                        <!-- Các options sẽ được tải từ cơ sở dữ liệu -->
                                        <?php
                                        foreach ($departments as $department) {
                                            echo '<option value="' . $department['Id'] . '">' . $department['DepartmentName'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <button type="button" id="btn-ok" class="btn btn-primary mt-3">OK</button>
                                </div>

                                <!-- Chia layout theo 2 cột -->
                                <div class="row mt-4">
                                    <!-- Cột bên trái: Danh sách nhân viên -->
                                    <div class="col-md-6" id="employee-list">
                                        <!-- Danh sách nhân viên sẽ được tải vào đây sau khi bấm "OK" -->
                                    </div>

                                    <!-- Cột bên phải: Hiển thị thông tin lương của nhân viên đã chọn -->
                                    <div class="col-md-6" id="salary-info" style="display: none;">
                                        <h5>Salary Details</h5>
                                        <ul id="salary-details" class="list-group">
                                            <!-- Các thông tin về lương sẽ được hiển thị ở đây -->
                                        </ul>
                                        <button type="submit" class="btn btn-success mt-3">Save</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!-- End of Content Wrapper -->
                    </div>
                    <!-- End of Content Wrapper -->
                </div>
                <!-- End of Page Wrapper -->

            </div>
            <?php include('../templates/footer.php') ?>
        </div>
        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

        <!-- Logout Modal-->
        <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
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

        <!-- Script xử lý AJAX -->
        <script>
            let selectedEmployeeId = null; // Biến để lưu ID nhân viên đã chọn
            let salaryData = {}; // Đối tượng để lưu trữ dữ liệu tạm thời

            // Lắng nghe sự kiện khi chọn Department và nhấn OK
            document.getElementById('btn-ok').addEventListener('click', function() {
                const departmentId = document.getElementById('department').value;

                // Gửi yêu cầu AJAX để lấy danh sách nhân viên của department
                if (departmentId) {
                    fetch('get_employees.php', {
                            method: 'POST',
                            body: new URLSearchParams({
                                department_id: departmentId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            const employeeList = document.getElementById('employee-list');
                            employeeList.innerHTML = ''; // Clear previous list

                            // Thêm dòng "Danh sách Employee"
                            const title = document.createElement('h5');
                            title.textContent = 'Danh sách Employee';
                            employeeList.appendChild(title);

                            // Tạo danh sách nhân viên
                            data.forEach(employee => {
                                const employeeItem = document.createElement('div');
                                employeeItem.classList.add('employee-item', 'mb-2');
                                employeeItem.innerHTML = `
                                    <button class="employee-button" data-id="${employee.Id}">${employee.FullName}</button>
                                `;
                                employeeList.appendChild(employeeItem);
                            });
                        })
                        .catch(error => console.log('Error:', error));
                }
            });

            // Sự kiện khi người dùng bấm vào một nhân viên
            document.getElementById('employee-list').addEventListener('click', function(event) {
                if (event.target && event.target.matches('button')) {
                    // Xóa class 'active' khỏi tất cả các button trước đó
                    const previousActive = document.querySelector('#employee-list button.active');
                    if (previousActive) {
                        previousActive.classList.remove('active');
                    }

                    // Thêm class 'active' vào nhân viên được chọn
                    event.target.classList.add('active');

                    selectedEmployeeId = event.target.getAttribute('data-id'); // Lưu ID nhân viên đã chọn

                    // Kiểm tra nếu đã có dữ liệu lương tạm thời cho nhân viên này
                    if (salaryData[selectedEmployeeId]) {
                        displaySalaryInfo(salaryData[selectedEmployeeId]); // Hiển thị thông tin lương đã lưu
                    } else {
                        // Gửi yêu cầu AJAX để lấy thông tin lương của nhân viên đã chọn
                        fetch('get_salary_info.php', {
                                method: 'POST',
                                body: new URLSearchParams({
                                    employee_id: selectedEmployeeId // Sử dụng biến đã lưu
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log(data); // Debug 
                                const employeeList = document.getElementById('employee-list');
                                // Lưu dữ liệu vào đối tượng tạm thời
                                salaryData[selectedEmployeeId] = data;

                                // Hiển thị thông tin lương
                                displaySalaryInfo(data);
                            })
                            .catch(error => console.log('Error:', error));
                    }
                }
            });

            // Định dạng số theo định dạng tiền tệ Việt Nam
            const formatNumber = new Intl.NumberFormat('vi-VN', {
                style: 'decimal',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            });

            // Hàm hiển thị thông tin lương
            function displaySalaryInfo(data) {
                const salaryInfo = document.getElementById('salary-info');
                salaryInfo.style.display = 'block'; // Hiển thị thông tin lương

                const salaryDetails = document.getElementById('salary-details');
                salaryDetails.innerHTML = ''; // Clear previous details

                // Định dạng số theo định dạng tiền tệ
                const formatNumber = new Intl.NumberFormat('vi-VN', {
                    style: 'decimal',
                    currency: 'VND',
                    minimumFractionDigits: 0
                });

                // Hiển thị thông tin ngày lương và định dạng số
                salaryDetails.innerHTML = `
                    <li class="list-group-item">Employment Type: ${data.employment_type === 'full-time' ? 'Full-time' : 'Part-time'}</li>
                    <li class="list-group-item">Total Work Days: ${data.total_days}</li>
                    <li class="list-group-item">Valid Days: ${data.valid_days}</li>
                    <li class="list-group-item">Invalid Days: ${data.invalid_days}</li>
                    <li class="list-group-item">Total Salary: ${formatNumber.format(data.total_salary)}</li>
                `;
            }

            // Sự kiện submit để lưu thông tin lương
            document.getElementById('salaryForm').addEventListener('submit', function(event) {
                event.preventDefault(); // Ngừng gửi form mặc định

                if (!selectedEmployeeId) { // Kiểm tra xem có nhân viên nào được chọn không
                    alert("Please select an employee.");
                    return;
                }

                const salaryLevel = document.getElementById('salary-details').children[0].textContent.split(': ')[1];
                const totalDays = document.getElementById('salary-details').children[1].textContent.split(': ')[1];
                const validDays = document.getElementById('salary-details').children[2].textContent.split(': ')[1];
                const invalidDays = document.getElementById('salary-details').children[3].textContent.split(': ')[1];
                const totalSalary = document.getElementById('salary-details').children[4].textContent.split(': ')[1];

                //totalSalary = totalSalary.replace(',', '.'); // Chuyển dấu phẩy thành dấu chấm
                // Gửi yêu cầu AJAX để lưu thông tin lương vào cơ sở dữ liệu
                fetch('save_salary_log.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            employee_id: selectedEmployeeId, // Sử dụng biến đã lưu
                            total_days: totalDays, // Thay vì gửi "QA2 - Monthly", gửi tổng số ngày
                            valid_days: validDays,
                            invalid_days: invalidDays,
                            total_salary: totalSalary,
                            month: new Date().getMonth() + 1,
                            year: new Date().getFullYear()
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Salary info saved successfully.');
                        } else {
                            if (data.error) {
                                alert('Error: ' + data.error); // Hiển thị lỗi từ PHP
                            } else {
                                alert('Error saving salary info.');
                            }
                        }
                    })
                    .catch(error => {
                        console.log('Error:', error);
                        alert('An error occurred while saving salary info.');
                    });
            });
        </script>
    </div>
</body>

</html>