<?php
session_start();
require_once '../config.php'; // Kết nối cơ sở dữ liệu
if (isset($_POST['filter'])) {
    $selectedDepartments = $_POST['departments'] ?? [];
    $departmentCondition = '';
    if (!empty($selectedDepartments)) {
        $departmentCondition = 'WHERE u.DepartmentID IN (' . implode(',', array_map('intval', $selectedDepartments)) . ')';
    }

    $sql = "
        SELECT 
            u.Id AS employee_id,
            u.FullName,
            CONCAT(d.DepartmentName, ' - ', COALESCE(pd.DepartmentName, '')) AS Department,
            sl.alias AS SalaryLevel,
            sl.monthly_salary,
            sl.daily_salary
        FROM 
            user u
        LEFT JOIN 
            department d ON u.DepartmentID = d.id
        LEFT JOIN 
            department pd ON d.ParentDepartmentID = pd.id
        LEFT JOIN 
            salary_levels sl ON u.salary_level_id = sl.id
        $departmentCondition
        GROUP BY 
            u.Id;
    ";

    // Tiếp tục thực hiện truy vấn như trước
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDepartments($parentId = 0) {
    global $conn;
    $sql = "SELECT id, DepartmentName, ParentDepartmentID FROM department WHERE ParentDepartmentID = :parentId";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':parentId' => $parentId]);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($departments as $department) {
        $children = getDepartments($department['id']);
        $department['children'] = $children;
        $result[] = $department;
    }
    return $result;
}

$departmentsTree = getDepartments();

function renderDepartmentOptions($departments) {
    foreach ($departments as $department) {
        echo '<option value="' . $department['id'] . '">' . htmlspecialchars($department['DepartmentName']) . '</option>';
        if (!empty($department['children'])) {
            renderDepartmentOptions($department['children']); // Gọi đệ quy để hiển thị phòng ban con
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Departments</title>
    <!-- Thêm CSS cho Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
</head>
<body>
    <form method="POST" action="">
        <h3>Lọc theo phòng ban</h3>
        <select id="departmentSelect" name="departments[]" multiple="multiple" style="width: 100%;">
            <?php renderDepartmentOptions($departmentsTree); ?>
        </select>
        <button type="submit" name="filter" class="btn btn-primary">Lọc</button>
    </form>

    <!-- Thêm jQuery và Select2 JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#departmentSelect').select2({
                placeholder: "Chọn phòng ban",
                allowClear: true
            });
        });
    </script>
</body>
</html>