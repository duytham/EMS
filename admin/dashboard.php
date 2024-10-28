<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: /login.php");
    exit();
}

include "../config.php";

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
?>

<!DOCTYPE html>
<html lang="en">

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
                        <?php
                        // Create an array to group departments by parent department
                        $departmentHierarchy = [];
                        foreach ($departments as $department) {
                            $parentName = $department['ParentDepartmentName'] ?: 'No Parent Department';
                            $departmentHierarchy[$parentName][] = $department;
                        }

                        // Display departments based on the parent-child structure
                        foreach ($departmentHierarchy as $parentName => $subDepartments): ?>
                            <div class="col-xl-12 mb-4">
                                <h5 class="text-gray-800 font-weight-bold"><?php echo htmlspecialchars($parentName); ?></h5>
                                <div class="row">
                                    <?php foreach ($subDepartments as $department): ?>
                                        <div class="col-xl-4 col-md-6 mb-4">
                                            <a href="view_employee.php?department_id=<?php echo $department['DepartmentId']; ?>" style="text-decoration: none;">
                                                <div class="card border-left-warning shadow h-100 py-2">
                                                    <div class="card-body">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                                    <?php echo htmlspecialchars($department['DepartmentName']); ?>
                                                                </div>
                                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                    <?php echo htmlspecialchars($department['TotalEmployees']); ?>
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
                        <?php endforeach; ?>
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
</body>

</html>
