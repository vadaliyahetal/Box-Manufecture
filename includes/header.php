<?php
session_start();
// Check if user is logged in AND is employee
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee'){
    header("Location: ../login.php");
    exit();
}
include '../includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - Om Rudra Boxes</title>
    <!-- Same styles as Admin/Standard -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .emp-sidebar { height: 100vh; position: fixed; top: 0; left: 0; width: 220px; background-color: #343a40; padding-top: 20px; }
        .emp-sidebar a { padding: 15px; text-decoration: none; font-size: 16px; color: #ccc; display: block; }
        .emp-sidebar a:hover, .emp-sidebar a.active { color: #fff; background-color: #495057; }
        .main-content { margin-left: 220px; padding: 20px; }
        @media (max-width: 768px) { .emp-sidebar { width: 100%; height: auto; position: relative; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<div class="emp-sidebar">
    <h4 class="text-white text-center mb-4 py-2 border-bottom border-gray">Employee Panel</h4>
    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-tasks me-2"></i> My Tasks</a>
    <a href="assigned_orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'assigned_orders.php' ? 'active' : ''; ?>"><i class="fas fa-box me-2"></i> Assigned Orders</a>
    <a href="my_schedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_schedule.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt me-2"></i> My Schedule</a>

    <a href="../logout.php" class="text-danger mt-5"><i class="fas fa-power-off me-2"></i> Logout</a>
</div>

<div class="main-content">
    <nav class="navbar navbar-light bg-white mb-4 shadow-sm rounded">
        <div class="container-fluid">
            <span class="navbar-brand h5 text-primary">Welcome, <?php echo $_SESSION['user_name']; ?></span>
        </div>
    </nav>
