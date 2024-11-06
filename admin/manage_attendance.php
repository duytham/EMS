<?php
session_start();
require '../config.php';
if (!isset($_SESSION['userId']) || ($_SESSION['role'] != 1 && $_SESSION['role'] != 3)) {
    header("Location: ../login.php");
    exit();
}

// Lấy dữ liệu chấm công
$stmt = $conn->prepare("
    SELECT User.FullName, CheckInOut.LogDate, CheckInOut.CheckInTime, CheckInOut.CheckOutTime
    FROM CheckInOut
    INNER JOIN User ON CheckInOut.UserID = User.Id
    ORDER BY CheckInOut.LogDate DESC, User.FullName ASC
");
$stmt->execute();
$attendanceLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quản lý chấm công</title>
</head>
<body>
    <h2>Quản lý chấm công</h2>
    <table border="1">
        <tr>
            <th>Họ và Tên</th>
            <th>Ngày</th>
            <th>Check-in</th>
            <th>Check-out</th>
        </tr>
        <?php foreach ($attendanceLogs as $log): ?>
            <tr>
                <td><?php echo htmlspecialchars($log['FullName']); ?></td>
                <td><?php echo htmlspecialchars($log['LogDate']); ?></td>
                <td><?php echo $log['CheckInTime'] ?: "Chưa check-in"; ?></td>
                <td><?php echo $log['CheckOutTime'] ?: "Chưa check-out"; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
