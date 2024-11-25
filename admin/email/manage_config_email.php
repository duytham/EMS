<?php
include '../../config.php';
if ($conn) {
    echo "Database connected successfully!";
} else {
    echo "Failed to connect to the database.";
}
// Lấy danh sách cấu hình email
$query = "SELECT e.Id, u.FullName, e.CheckInTime, e.CheckOutTime 
          FROM emailConfig e 
          JOIN user u ON e.UserID = u.Id";
$stmt = $conn->prepare($query);
$stmt->execute();
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cập nhật giờ check-in/check-out
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['bulkCheckInTime']) && isset($_POST['bulkCheckOutTime'])) {
        // Cập nhật hàng loạt
        $bulkCheckInTime = $_POST['bulkCheckInTime'];
        $bulkCheckOutTime = $_POST['bulkCheckOutTime'];
        $updateQuery = "UPDATE emailConfig SET CheckInTime = ?, CheckOutTime = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$bulkCheckInTime, $bulkCheckOutTime]);
    } elseif (isset($_POST['UserID']) && isset($_POST['CheckInTime']) && isset($_POST['CheckOutTime'])) {
        // Cập nhật từng user
        $userId = $_POST['UserID'];
        $checkInTime = $_POST['CheckInTime'];
        $checkOutTime = $_POST['CheckOutTime'];
        $updateQuery = "UPDATE emailConfig SET CheckInTime = ?, CheckOutTime = ? WHERE UserID = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$checkInTime, $checkOutTime, $userId]);
    }

    header("Location: manage_config_email.php");
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Email Configurations</title>
</head>

<body>
    <h1>Manage Email Configurations</h1>

    <!-- Form chỉnh sửa giờ hàng loạt -->
    <form method="POST" action="">
        <label>New Check-in Time: <input type="time" name="bulkCheckInTime"></label>
        <label>New Check-out Time: <input type="time" name="bulkCheckOutTime"></label>
        <button type="submit">Update All</button>
    </form>

    <hr>

    <!-- Bảng chỉnh sửa giờ từng user -->
    <table border="1">
        <tr>
            <th>Employee Name</th>
            <th>Check-in Time</th>
            <th>Check-out Time</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($configs as $config): ?>
            <tr>
                <form method="POST" action="">
                    <td><?php echo htmlspecialchars($config['FullName']); ?></td>
                    <td><input type="time" name="CheckInTime" value="<?php echo htmlspecialchars($config['CheckInTime']); ?>"></td>
                    <td><input type="time" name="CheckOutTime" value="<?php echo htmlspecialchars($config['CheckOutTime']); ?>"></td>
                    <td>
                        <input type="hidden" name="UserID" value="<?php echo htmlspecialchars($config['Id']); ?>">
                        <button type="submit">Update</button>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>