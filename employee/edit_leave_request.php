<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveId = $_POST['leave_id'];
    $leaveStart = $_POST['leave_start'];
    $leaveEnd = $_POST['leave_end'];
    $reasonSelect = $_POST['reason_select'];
    $reason = $reasonSelect === 'Other' ? $_POST['reason'] : $reasonSelect;

    // Cập nhật đơn nghỉ phép
    $stmt = $conn->prepare("UPDATE LeaveRequest SET LeaveDateStart = ?, LeaveDateEnd = ?, Reason = ? WHERE Id = ? AND Status = 'Pending'");
    $stmt->execute([$leaveStart, $leaveEnd, $reason, $leaveId]);

    $_SESSION['message'] = "Đơn nghỉ phép đã được cập nhật.";
    header("Location: view_leave_requests.php");
    exit;
}

// Lấy thông tin đơn nghỉ phép để hiển thị trong form
if (isset($_GET['id'])) {
    $leaveId = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM LeaveRequest WHERE Id = ? AND Status = 'Pending'");
    $stmt->execute([$leaveId]);
    $leaveRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$leaveRequest) {
        $_SESSION['message'] = "Đơn nghỉ phép không tồn tại hoặc không thể sửa.";
        header("Location: view_leave_requests.php");
        exit;
    }
} else {
    header("Location: view_leave_requests.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Leave Request</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include('../employee/sidebar.php') ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include('../templates/navbar.php') ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Edit Leave Request</h1>

                    <form action="edit_leave_request.php" method="POST">
                        <input type="hidden" name="leave_id" value="<?= htmlspecialchars($leaveRequest['Id']) ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="leave_start">Start Date:</label>
                                    <input type="date" name="leave_start" class="form-control" value="<?= htmlspecialchars($leaveRequest['LeaveDateStart']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="leave_end">End Date:</label>
                                    <input type="date" name="leave_end" class="form-control" value="<?= htmlspecialchars($leaveRequest['LeaveDateEnd']) ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="reason_select">Reason:</label>
                            <select name="reason_select" id="reason_select" class="form-control" required onchange="toggleReasonInput(this)">
                                <option value="">Select Reason</option>
                                <option value="Sick" <?= $leaveRequest['Reason'] == 'Sick' ? 'selected' : '' ?>>Sick</option>
                                <option value="Vacation" <?= $leaveRequest['Reason'] == 'Vacation' ? 'selected' : '' ?>>Vacation</option>
                                <option value="Personal" <?= $leaveRequest['Reason'] == 'Personal' ? 'selected' : '' ?>>Personal</option>
                                <option value="Other" <?= !in_array($leaveRequest['Reason'], ['Sick', 'Vacation', 'Personal']) ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group" id="reasonInputGroup" style="display: <?= !in_array($leaveRequest['Reason'], ['Sick', 'Vacation', 'Personal']) ? 'block' : 'none' ?>;">
                            <label for="reason">Enter other reason here:</label>
                            <textarea name="reason" id="reason" class="form-control" rows="4" placeholder="Enter other reason"><?= htmlspecialchars($leaveRequest['Reason']) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                    <a href="view_leave_requests.php" class="btn btn-secondary mt-3">Back to Leave Requests</a>
                </div>
            </div>
            <?php include('../templates/footer.php') ?>
        </div>
    </div>

    <script>
        function toggleReasonInput(select) {
            var reasonInputGroup = document.getElementById('reasonInputGroup');
            var reasonInput = document.getElementById('reason');
            if (select.value === 'Other') {
                reasonInputGroup.style.display = 'block';
                reasonInput.required = true;
            } else {
                reasonInputGroup.style.display = 'none';
                reasonInput.required = false;
                reasonInput.value = select.value; // Set the reason to the selected value
            }
        }
    </script>
</body>

</html>