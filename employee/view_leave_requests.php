<?php
session_start();
include '../config.php';

// Lấy User ID từ session
$userId = $_SESSION['user_id'];

// Lấy danh sách đơn nghỉ phép của người dùng
$stmt = $conn->prepare("SELECT Id, LeaveDateStart, LeaveDateEnd, Reason, Status FROM LeaveRequest WHERE UserId = ?");
$stmt->execute([$userId]);
$leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee - EDMS - View your Leave Request</title>
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
                    <h1 class="h3 mb-4 text-gray-800">View Leave Request</h1>

                    <!-- Hiển thị thông báo nếu có -->
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-info">
                            <?= $_SESSION['message'] ?>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <!-- Bảng danh sách đơn nghỉ phép -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Leave Request</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Leave Start Date</th>
                                            <th>Leave End Date</th>
                                            <th>Reason</th>
                                            <th>status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leaveRequests as $request): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($request['LeaveDateStart']) ?></td>
                                                <td><?= htmlspecialchars($request['LeaveDateEnd']) ?></td>
                                                <td><?= htmlspecialchars($request['Reason']) ?></td>
                                                <td>
                                                    <?php if ($request['Status'] == 'Pending'): ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php elseif ($request['Status'] == 'Approved'): ?>
                                                        <span class="badge badge-success">Approved</span>
                                                    <?php elseif ($request['Status'] == 'Rejected'): ?>
                                                        <span class="badge badge-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($request['Status'] == 'Pending'): ?>
                                                        <a href="edit_leave_request.php?id=<?= htmlspecialchars($request['Id']) ?>" class="btn btn-warning btn-sm">Edit</a>
                                                        <form action="delete_leave_request.php" method="POST" style="display:inline;">
                                                            <input type="hidden" name="leave_id" value="<?= htmlspecialchars($request['Id']) ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure to delete this leave request?')">Delete</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>
</body>

</html>