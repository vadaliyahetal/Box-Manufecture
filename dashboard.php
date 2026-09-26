<?php
session_start();
include '../includes/db.php';
// Check if user is logged in AND is employee
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee'){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get Stats
$assigned_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE assigned_employee_id = $user_id AND order_status != 'Delivered'")->fetch_assoc()['count'];
$custom_count = $conn->query("SELECT COUNT(*) as count FROM custom_box_requests WHERE assigned_employee_id = $user_id AND status != 'Delivered'")->fetch_assoc()['count'];
$completed_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE assigned_employee_id = $user_id AND order_status = 'Delivered'")->fetch_assoc()['count'];

// Get Schedule (Next 3 days)
$schedules = $conn->query("SELECT * FROM schedules WHERE employee_id = $user_id AND work_date >= CURDATE() ORDER BY work_date ASC LIMIT 3");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Employee Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
        .wrapper { display: flex; width: 100%; align-items: stretch; }
        #sidebar { 
            min-width: 260px; max-width: 260px; 
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); 
            color: #fff; transition: all 0.3s; min-height: 100vh; 
            box-shadow: 3px 0 15px rgba(0,0,0,0.15);
        }
        #sidebar .sidebar-header { 
            padding: 28px 20px 18px; 
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        #sidebar .sidebar-header h3 { font-size: 1.2rem; font-weight: 700; color: #60a5fa; margin: 0; }
        #sidebar .sidebar-header p { color: #94a3b8; font-size: 0.85rem; margin: 6px 0 0; }
        #sidebar ul.components { padding: 16px 0; }
        #sidebar ul li a { 
            padding: 12px 20px; font-size: 0.95em; display: flex; align-items: center;
            color: #cbd5e1; text-decoration: none; transition: all 0.2s; gap: 10px;
            border-left: 3px solid transparent;
        }
        #sidebar ul li a:hover, #sidebar ul li.active > a { 
            color: #fff; background: rgba(96,165,250,0.15); border-left-color: #60a5fa; 
        }
        #content { width: 100%; padding: 24px; min-height: 100vh; }
        .top-navbar {
            background: #fff; border-radius: 12px; padding: 12px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 24px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .stat-card {
            background: #fff; border-radius: 16px; padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: none;
            height: 100%; transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; margin-bottom: 15px;
        }
        .schedule-card {
            background: #fff; border-radius: 16px; padding: 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: none;
            overflow: hidden;
        }
        .schedule-header {
            padding: 15px 20px; background: #f8fafc;
            border-bottom: 1px solid #e2e8f0; font-weight: 600;
        }
        .schedule-item {
            padding: 15px 20px; border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
        }
        .schedule-item:last-child { border-bottom: none; }
        #sidebar.active { margin-left: -260px; }
    </style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-hard-hat me-2"></i>Employee Panel</h3>
            <p><i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        </div>

        <ul class="list-unstyled components">
            <li class="active">
                <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            </li>
            <li>
                <a href="assigned_orders.php"><i class="fas fa-tasks"></i> My Assigned Orders</a>
            </li>
            <li>
                <a href="my_schedule.php"><i class="fas fa-calendar-alt"></i> My Schedule</a>
            </li>
            <li>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <div class="top-navbar">
            <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-bars"></i>
            </button>
            <div class="fw-bold text-primary">Employee Dashboard</div>
            <div class="text-muted small">
                <i class="far fa-clock me-1"></i> <?php echo date("d M Y"); ?>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="text-muted small fw-bold text-uppercase">Standard Orders</div>
                    <div class="h3 fw-bold mb-0 text-dark"><?php echo $assigned_count; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-purple bg-opacity-10 text-purple" style="color: #7c3aed; background: rgba(124, 58, 237, 0.1);">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="text-muted small fw-bold text-uppercase">Custom Requests</div>
                    <div class="h3 fw-bold mb-0 text-dark"><?php echo $custom_count; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="text-muted small fw-bold text-uppercase">Completed Tasks</div>
                    <div class="h3 fw-bold mb-0 text-dark"><?php echo $completed_count; ?></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="schedule-card mb-4">
                    <div class="schedule-header d-flex justify-content-between align-items-center">
                        <div><i class="fas fa-calendar-day me-2 text-primary"></i> My Upcoming Schedule</div>
                        <a href="my_schedule.php" class="btn btn-sm btn-link text-decoration-none p-0">View All</a>
                    </div>
                    <?php if($schedules->num_rows > 0): ?>
                        <?php while($row = $schedules->fetch_assoc()): ?>
                            <div class="schedule-item">
                                <div>
                                    <div class="fw-bold"><?php echo date("l, d M", strtotime($row['work_date'])); ?></div>
                                    <small class="text-muted"><?php echo $row['notes'] ?: 'Regular Shift'; ?></small>
                                </div>
                                <div class="badge bg-info text-dark rounded-pill px-3 py-2">
                                    <?php echo date("h:i A", strtotime($row['start_time'])); ?> - <?php echo date("h:i A", strtotime($row['end_time'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-calendar-times fa-2x mb-2"></i>
                            <p class="mb-0">No upcoming shifts assigned.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-body p-4 bg-primary text-white">
                        <h5 class="fw-bold mb-3">Quick Actions</h5>
                        <a href="assigned_orders.php" class="btn btn-light btn-sm w-100 mb-2 py-2 fw-600">
                            <i class="fas fa-list me-2"></i> Process Orders
                        </a>
                        <a href="my_schedule.php" class="btn btn-outline-light btn-sm w-100 py-2">
                            <i class="fas fa-calendar-alt me-2"></i> Weekly Plan
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
        });
    });
</script>
</body>
</html>
