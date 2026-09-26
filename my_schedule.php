<?php
session_start();
include '../includes/db.php';
// Check login
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee'){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$schedules = $conn->query("SELECT * FROM schedules WHERE employee_id = $user_id AND work_date >= CURDATE() ORDER BY work_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Employee Panel</title>
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
        .schedule-table-card {
            background: #fff; border-radius: 16px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: none; overflow: hidden;
        }
        .table thead th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .table tbody td { vertical-align: middle; padding: 15px 12px; }
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
            <li>
                <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            </li>
            <li>
                <a href="assigned_orders.php"><i class="fas fa-tasks"></i> My Assigned Orders</a>
            </li>
            <li class="active">
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
            <div class="fw-bold text-primary">My Work Schedule</div>
            <div class="text-muted small">
                <i class="far fa-calendar me-1"></i> Full List
            </div>
        </div>

        <div class="schedule-table-card">
            <?php if($schedules->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Day</th>
                                <th>Shift Time</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $schedules->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?php echo date("d M Y", strtotime($row['work_date'])); ?></div>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?php echo date("l", strtotime($row['work_date'])); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo date("h:i A", strtotime($row['start_time'])); ?> - <?php echo date("h:i A", strtotime($row['end_time'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-dark small"><?php echo $row['notes'] ?: '<span class="text-muted italic">Regular shift</span>'; ?></div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-5 text-center">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-calendar-times fa-2x text-muted"></i>
                    </div>
                    <h5>No schedules found</h5>
                    <p class="text-muted">You don't have any upcoming shifts assigned currently.</p>
                </div>
            <?php endif; ?>
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
