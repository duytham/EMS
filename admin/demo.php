<?php
session_start();
include '../config.php';

// Fetch all employees for the combobox
$stmt = $conn->prepare("SELECT Id, FullName FROM user WHERE RoleID = 2");  // Assuming role '2' is for employees
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Tính Lương</title>
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <script src="../js/jquery.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container">
        <h2>Admin Dashboard</h2>
        <!-- Add employee list and action buttons -->
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><?= htmlspecialchars($employee['FullName']) ?></td>
                    <td>
                        <!-- Button to open "Tính lương" modal -->
                        <button class="btn btn-primary" onclick="openSalaryModal(<?= $employee['Id'] ?>)">Tính lương</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Salary Calculation Modal -->
    <div class="modal" id="salaryModal" tabindex="-1" role="dialog" aria-labelledby="salaryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="salaryModalLabel">Tính Lương</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="salaryForm">
                        <div class="form-group">
                            <label for="employee">Nhân viên</label>
                            <select class="form-control" id="employee" name="employee_id" required>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?= $employee['Id'] ?>"><?= htmlspecialchars($employee['FullName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="salaryLevel">Hệ số lương</label>
                            <input type="text" class="form-control" id="salaryLevel" readonly>
                        </div>
                        <div class="form-group">
                            <label for="validDays">Số ngày công hợp lệ</label>
                            <input type="number" class="form-control" id="validDays" readonly>
                        </div>
                        <div class="form-group">
                            <label for="invalidDays">Số ngày công không hợp lệ</label>
                            <input type="number" class="form-control" id="invalidDays" readonly>
                        </div>
                        <div class="form-group">
                            <label for="salaryReceived">Lương nhận được</label>
                            <input type="text" class="form-control" id="salaryReceived" readonly>
                        </div>
                        <button type="button" class="btn btn-success" onclick="saveSalary()">Lưu trữ tính lương</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Open the salary modal and populate fields
        function openSalaryModal(employeeId) {
            $.ajax({
                url: 'get_salary_details.php',
                method: 'GET',
                data: { employee_id: employeeId },
                success: function(response) {
                    const data = JSON.parse(response);
                    $('#employee').val(employeeId);
                    $('#salaryLevel').val(data.salary_level);
                    $('#validDays').val(data.valid_days);
                    $('#invalidDays').val(data.invalid_days);
                    $('#salaryReceived').val(data.salary);
                    $('#salaryModal').modal('show');
                }
            });
        }

        // Save salary details
        function saveSalary() {
            const formData = $('#salaryForm').serialize();
            $.ajax({
                url: 'save_salary.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    alert(response.message);
                    $('#salaryModal').modal('hide');
                }
            });
        }
    </script>
</body>
</html>
