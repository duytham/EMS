<?php
include '../config.php'; // Kết nối cơ sở dữ liệu

// Lấy danh sách cấu hình email của tất cả nhân viên
$query = "SELECT e.id, u.FullName, e.checkInTime, e.checkOutTime 
          FROM emailConfig e 
          JOIN user u ON e.userId = u.Id";
$stmt = $conn->prepare($query);
$stmt->execute();
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cập nhật cấu hình email của nhân viên
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['userId'];
    $checkInTime = $_POST['checkInTime'];
    $checkOutTime = $_POST['checkOutTime'];

    $updateQuery = "UPDATE emailConfig SET checkInTime = :checkInTime, checkOutTime = :checkOutTime WHERE userId = :userId";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':checkInTime', $checkInTime);
    $updateStmt->bindParam(':checkOutTime', $checkOutTime);
    $updateStmt->bindParam(':userId', $userId);
    $updateStmt->execute();

    header("Location: manage_email_config.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Email Configurations</title>
</head>
<body>
    <h1>Email Configurations</h1>
    <table border="1">
        <tr>
            <th>Employee Name</th>
            <th>Check-in Time</th>
            <th>Check-out Time</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($configs as $config): ?>
        <tr>
            <form method="POST" action="manage_email_config.php">
                <td><?php echo htmlspecialchars($config['FullName']); ?></td>
                <td><input type="time" name="checkInTime" value="<?php echo htmlspecialchars($config['checkInTime']); ?>"></td>
                <td><input type="time" name="checkOutTime" value="<?php echo htmlspecialchars($config['checkOutTime']); ?>"></td>
                <td>
                    <input type="hidden" name="userId" value="<?php echo htmlspecialchars($config['id']); ?>">
                    <input type="submit" value="Update">
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
