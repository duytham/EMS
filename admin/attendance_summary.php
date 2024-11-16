<?php
// Kết nối cơ sở dữ liệu và lấy dữ liệu phòng ban, nhân viên, và điểm danh tại đây
session_start();
include('../config.php');  // Kết nối cơ sở dữ liệu

// Lấy thông tin phòng ban (ví dụ từ session hoặc tham số GET)
$department_id = $_SESSION['department_id'];  // Hoặc từ tham số GET

// Lấy danh sách nhân viên trong phòng ban
$query = "SELECT u.FullName, u.UserID FROM user u WHERE u.DepartmentID = :department_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lọc theo tháng/năm
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng tổng hợp điểm danh</title>
    <!-- Include Bootstrap hoặc CSS của bạn -->
</head>

<body>
    <h1>Bảng tổng hợp điểm danh phòng ban</h1>

    <!-- Form lọc theo tháng/năm -->
    <form method="GET" action="attendance_summary.php">
        <select name="month">
            <?php for ($i = 1; $i <= 12; $i++) { ?>
                <option value="<?= $i ?>" <?= $i == $selected_month ? 'selected' : '' ?>>Tháng <?= $i ?></option>
            <?php } ?>
        </select>
        <select name="year">
            <?php for ($i = date('Y'); $i >= 2020; $i--) { ?>
                <option value="<?= $i ?>" <?= $i == $selected_year ? 'selected' : '' ?>>Năm <?= $i ?></option>
            <?php } ?>
        </select>
        <button type="submit">Lọc</button>
    </form>

    <!-- Bảng điểm danh -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nhân viên</th>
                <?php for ($day = 1; $day <= $days_in_month; $day++) { ?>
                    <th><?= $day ?></th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $employee) { ?>
                <tr>
                    <td><?= htmlspecialchars($employee['FullName']) ?></td>
                    <?php
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        // Truy vấn điểm danh của nhân viên trong ngày này
                        // Tính giờ làm, kiểm tra điểm danh và thay đổi màu
                        $query = "SELECT SUM(TIMESTAMPDIFF(MINUTE, CheckInTime, CheckOutTime)) AS total_minutes 
                                  FROM checkinout WHERE UserID = :user_id 
                                  AND MONTH(CheckInTime) = :month AND YEAR(CheckInTime) = :year 
                                  AND DAY(CheckInTime) = :day AND ActionType = 'checkout'";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':user_id', $employee['UserID'], PDO::PARAM_INT);
                        $stmt->bindParam(':month', $selected_month, PDO::PARAM_INT);
                        $stmt->bindParam(':year', $selected_year, PDO::PARAM_INT);
                        $stmt->bindParam(':day', $day, PDO::PARAM_INT);
                        $stmt->execute();
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);

                        $total_minutes = $result['total_minutes'] ?? 0;
                        $hours = floor($total_minutes / 60);
                        $minutes = $total_minutes % 60;

                        // Kiểm tra nếu ngày có điểm danh
                        $checkin_query = "SELECT 1 FROM checkinout WHERE UserID = :user_id 
                                          AND MONTH(CheckInTime) = :month AND YEAR(CheckInTime) = :year 
                                          AND DAY(CheckInTime) = :day LIMIT 1";
                        $checkin_stmt = $conn->prepare($checkin_query);
                        $checkin_stmt->bindParam(':user_id', $employee['UserID'], PDO::PARAM_INT);
                        $checkin_stmt->bindParam(':month', $selected_month, PDO::PARAM_INT);
                        $checkin_stmt->bindParam(':year', $selected_year, PDO::PARAM_INT);
                        $checkin_stmt->bindParam(':day', $day, PDO::PARAM_INT);
                        $checkin_stmt->execute();
                        $has_checkin = $checkin_stmt->fetch(PDO::FETCH_ASSOC);

                        // Đổi màu nếu có điểm danh
                        $bg_color = $has_checkin ? 'style="background-color: #d4edda;"' : '';

                        echo "<td $bg_color>{$hours}h{$minutes}m</td>";
                    }
                    ?>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</body>

</html>