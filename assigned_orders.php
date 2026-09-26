<?php
session_start();
include '../includes/db.php';
// Check if user is logged in AND is employee
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'employee'){
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$msg_type = "success";

// Update Status Handler for any type of order
if(isset($_POST['update_status'])){
    $id = intval($_POST['item_id'] ?? $_POST['order_id'] ?? 0);
    $type = $_POST['item_type'] ?? 'order'; // default to order if not specified (standard orders)
    $status = $_POST['order_status'];
    $payment_status = $_POST['payment_status'];

    if($type == 'order'){
        $stmt = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ? AND assigned_employee_id = ?");
        $stmt->bind_param("ssii", $status, $payment_status, $id, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE custom_box_requests SET status = ?, payment_status = ? WHERE id = ? AND assigned_employee_id = ?");
        $stmt->bind_param("ssii", $status, $payment_status, $id, $user_id);
    }

    if($stmt->execute()){
        $message = "Item updated successfully.";
        $msg_type = "success";
    } else {
        $message = "Error updating item: " . $conn->error;
        $msg_type = "danger";
    }
}

// Fetch Assigned Standard Orders (store product orders)
$orders = $conn->query("SELECT o.*, u.name as customer_name, u.mobile, u.address 
    FROM orders o JOIN users u ON o.user_id = u.id 
    WHERE o.assigned_employee_id = $user_id AND (o.order_type IS NULL OR o.order_type != 'Custom')
    ORDER BY o.created_at ASC");

$custom_items = $conn->query("SELECT 
    r.*, 
    u.name as customer_name, u.mobile, u.address,
    o.id as official_order_id,
    o.order_status as official_order_status,
    o.payment_status as official_payment_status,
    o.total_amount as official_total_amount,
    o.created_at as official_created_at
    FROM custom_box_requests r 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN orders o ON r.id = o.custom_request_id
    WHERE r.assigned_employee_id = $user_id OR o.assigned_employee_id = $user_id
    ORDER BY r.created_at ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Orders - Employee Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
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
        .section-title {
            font-size: 1.3rem; font-weight: 700; color: #1e293b;
            display: flex; align-items: center; gap: 10px; margin-bottom: 20px;
        }
        .section-title .icon-badge {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
        }
        .order-card {
            background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            border: none; transition: transform 0.2s, box-shadow 0.2s; overflow: hidden;
            margin-bottom: 20px;
        }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .card-header-custom {
            padding: 14px 18px; display: flex; align-items: center;
            justify-content: space-between; border-bottom: 1px solid #f1f5f9;
        }
        .card-header-custom.order-type { background: linear-gradient(135deg,#eff6ff,#dbeafe); }
        .card-header-custom.custom-type { background: linear-gradient(135deg,#ecfdf5,#d1fae5); }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
        .info-item { background: #f8fafc; border-radius: 8px; padding: 10px 12px; }
        .info-item .label { font-size: 0.72rem; text-transform: uppercase; color: #94a3b8; font-weight: 600; letter-spacing: 0.5px; }
        .info-item .value { font-size: 0.9rem; font-weight: 600; color: #1e293b; margin-top: 2px; }
        .info-item.full { grid-column: 1 / -1; }
        .payment-badge { 
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600;
        }
        .payment-badge.completed { background: #dcfce7; color: #16a34a; }
        .payment-badge.pending { background: #fef9c3; color: #ca8a04; }
        .form-section { 
            background: #f8fafc; border-radius: 10px; padding: 14px; 
            border: 1px solid #e2e8f0; margin-top: 10px; 
        }
        .form-section label { font-size: 0.78rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .btn-update { 
            background: linear-gradient(135deg,#3b82f6,#2563eb); color: #fff;
            border: none; border-radius: 8px; padding: 8px 18px; font-weight: 600;
            font-size: 0.85rem; transition: all 0.2s; white-space: nowrap;
        }
        .btn-update:hover { background: linear-gradient(135deg,#2563eb,#1d4ed8); color: #fff; transform: translateY(-1px); }
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .invoice-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 9px 14px; border-radius: 8px; font-size: 0.83rem; font-weight: 600;
            text-decoration: none; border: 1.5px solid #e2e8f0; color: #475569;
            background: #fff; transition: all 0.2s; margin-top: 10px;
        }
        .invoice-btn:hover { background: #f1f5f9; color: #1e293b; border-color: #cbd5e1; }
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
            <li class="active">
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
                <i class="fas fa-bars"></i> Toggle Sidebar
            </button>
            <div class="text-muted small">
                <i class="far fa-clock me-1"></i> <?php echo date("d M Y, h:i A"); ?>
            </div>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show shadow-sm rounded-3" role="alert">
                <i class="fas fa-<?php echo ($msg_type == 'success') ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ===== STANDARD ORDERS ===== -->
        <div class="section-title">
            <div class="icon-badge bg-primary bg-opacity-10 text-primary">
                <i class="fas fa-shopping-bag"></i>
            </div>
            Standard Orders
            <?php if($orders->num_rows > 0): ?>
                <span class="badge bg-primary rounded-pill ms-1"><?php echo $orders->num_rows; ?></span>
            <?php endif; ?>
        </div>

        <?php if($orders->num_rows > 0): ?>
            <div class="row g-3">
                <?php while($row = $orders->fetch_assoc()): ?>
                    <div class="col-xl-6 col-lg-12">
                        <div class="order-card">
                            <div class="card-header-custom order-type">
                                <div>
                                    <h6 class="mb-0 fw-bold text-primary">Order #<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></h6>
                                    <small class="text-muted"><?php echo date("d M Y, h:i A", strtotime($row['created_at'])); ?></small>
                                </div>
                                <?php
                                $statusColors = [
                                    'Pending' => 'warning',
                                    'Processing' => 'info',
                                    'Out for Delivery' => 'primary',
                                    'Delivered' => 'success',
                                    'Cancelled' => 'danger'
                                ];
                                $sc = $statusColors[$row['order_status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $sc; ?> badge-status"><?php echo $row['order_status']; ?></span>
                            </div>

                            <div class="p-3">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-user me-1"></i>Customer</div>
                                        <div class="value"><?php echo htmlspecialchars($row['customer_name']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-phone me-1"></i>Mobile</div>
                                        <div class="value"><?php echo htmlspecialchars($row['mobile']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-rupee-sign me-1"></i>Total Amount</div>
                                        <div class="value">₹<?php echo number_format($row['total_amount'], 2); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-credit-card me-1"></i>Payment Status</div>
                                        <div class="value">
                                            <span class="payment-badge <?php echo ($row['payment_status'] == 'Completed') ? 'completed' : 'pending'; ?>">
                                                <i class="fas fa-<?php echo ($row['payment_status'] == 'Completed') ? 'check-circle' : 'clock'; ?>"></i>
                                                <?php echo $row['payment_status']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-item full">
                                        <div class="label"><i class="fas fa-map-marker-alt me-1"></i>Delivery Address</div>
                                        <div class="value"><?php echo htmlspecialchars($row['address']); ?></div>
                                    </div>
                                </div>

                                <!-- Update Form -->
                                <div class="form-section">
                                    <form method="POST" action="" class="row g-2 align-items-end">
                                        <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">

                                        <div class="col-sm-4">
                                            <label>Order Status</label>
                                            <select name="order_status" class="form-select form-select-sm">
                                                <?php foreach(['Processing','Out for Delivery','Delivered','Cancelled'] as $s): ?>
                                                    <option value="<?php echo $s; ?>" <?php echo ($row['order_status'] == $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-sm-4">
                                            <label>Payment Status</label>
                                            <select name="payment_status" class="form-select form-select-sm">
                                                <option value="Pending" <?php echo ($row['payment_status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Completed" <?php echo ($row['payment_status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                                <option value="Failed" <?php echo ($row['payment_status'] == 'Failed') ? 'selected' : ''; ?>>Failed</option>
                                                <option value="Refunded" <?php echo ($row['payment_status'] == 'Refunded') ? 'selected' : ''; ?>>Refunded</option>
                                            </select>
                                        </div>

                                        <div class="col-sm-4">
                                            <button class="btn btn-update w-100" type="submit" name="update_status">
                                                <i class="fas fa-save me-1"></i> Update
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <a href="../fpdf_invoice.php?id=<?php echo $row['id']; ?>" target="_blank" class="invoice-btn">
                                    <i class="fas fa-file-invoice-dollar"></i> View Invoice PDF
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center border-0 shadow-sm rounded-3 py-4">
                <i class="fas fa-inbox fa-2x mb-2 d-block text-info"></i>
                No standard orders assigned to you yet.
            </div>
        <?php endif; ?>

        <hr class="my-4" style="border-color:#e2e8f0;">

        <!-- ===== CUSTOM BOX ORDERS (Unified Section) ===== -->
        <div class="section-title">
            <div class="icon-badge text-white" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);">
                <i class="fas fa-cube"></i>
            </div>
            Custom Box Orders
            <?php if($custom_items->num_rows > 0): ?>
                <span class="badge rounded-pill ms-1" style="background:#7c3aed;"><?php echo $custom_items->num_rows; ?></span>
            <?php endif; ?>
        </div>

        <?php if($custom_items->num_rows > 0): ?>
            <div class="row g-3">
                <?php while($item = $custom_items->fetch_assoc()): 
                    // Determine if we show it as an Order or a Request
                    $is_official = !empty($item['official_order_id']);
                    $display_id = $is_official ? $item['official_order_id'] : $item['id'];
                    $display_id_formatted = str_pad($display_id, 6, '0', STR_PAD_LEFT);
                    $display_status = $is_official ? $item['official_order_status'] : $item['status'];
                    $display_payment = $is_official ? $item['official_payment_status'] : $item['payment_status'];
                    $display_amount = $is_official ? $item['official_total_amount'] : $item['calculated_price'];
                    $display_date = $is_official ? $item['official_created_at'] : $item['created_at'];
                ?>
                    <div class="col-xl-6 col-lg-12">
                        <div class="order-card" style="border-top: 3px solid #7c3aed;">
                            <div class="card-header-custom" style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);">
                                <div>
                                    <h6 class="mb-0 fw-bold" style="color:#7c3aed;">
                                        <?php echo $is_official ? "Order" : "Request"; ?> #<?php echo $display_id_formatted; ?>
                                    </h6>
                                    <small class="text-muted"><?php echo date("d M Y, h:i A", strtotime($display_date)); ?></small>
                                </div>
                                <?php
                                $coColors = [
                                    'Pending'         => 'warning',
                                    'Processing'      => 'info',
                                    'Out for Delivery'=> 'primary',
                                    'Delivered'       => 'success',
                                    'Cancelled'       => 'danger',
                                    'Approved'        => 'success'
                                ];
                                $coSc = $coColors[$display_status] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $coSc; ?> badge-status"><?php echo $display_status; ?></span>
                            </div>

                            <div class="p-3">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-user me-1"></i>Customer</div>
                                        <div class="value"><?php echo htmlspecialchars($item['customer_name']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-phone me-1"></i>Mobile</div>
                                        <div class="value"><?php echo htmlspecialchars($item['mobile']); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-rupee-sign me-1"></i>Total Amount</div>
                                        <div class="value">₹<?php echo number_format($display_amount, 2); ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-credit-card me-1"></i>Payment Status</div>
                                        <div class="value">
                                            <?php $coPsBadge = ($display_payment == 'Completed') ? 'completed' : 'pending'; ?>
                                            <span class="payment-badge <?php echo $coPsBadge; ?>">
                                                <i class="fas fa-<?php echo ($display_payment == 'Completed') ? 'check-circle' : 'clock'; ?>"></i>
                                                <?php echo $display_payment; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Custom Request Details (Always show in unified view) -->
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-ruler-combined me-1"></i>Dimensions</div>
                                        <div class="value"><?php echo "{$item['length']}×{$item['width']}×{$item['height']} {$item['unit']}"; ?></div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label"><i class="fas fa-layer-group me-1"></i>Material</div>
                                        <div class="value"><?php echo ucfirst($item['material']); ?> / <?php echo ucfirst($item['quality']); ?></div>
                                    </div>

                                    <div class="info-item full">
                                        <div class="label"><i class="fas fa-map-marker-alt me-1"></i>Address</div>
                                        <div class="value"><?php echo htmlspecialchars($item['address']); ?></div>
                                    </div>
                                </div>

                                <!-- Files -->
                                <?php if($item['design_file'] || $item['logo_file']): ?>
                                    <div class="d-flex gap-3 mb-3 mt-2">
                                        <?php if($item['design_file']): ?>
                                            <div class="text-center">
                                                <img src="../<?php echo $item['design_file']; ?>" class="img-fluid rounded border shadow-sm" style="max-height: 70px; max-width: 70px; object-fit: cover; cursor: pointer;" onclick="window.open(this.src)">
                                                <div class="text-muted small" style="font-size:0.65rem;">Design</div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if($item['logo_file']): ?>
                                            <div class="text-center">
                                                <img src="../<?php echo $item['logo_file']; ?>" class="img-fluid rounded border shadow-sm" style="max-height: 70px; max-width: 70px; object-fit: cover; cursor: pointer;" onclick="window.open(this.src)">
                                                <div class="text-muted small" style="font-size:0.65rem;">Logo</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Update Form -->
                                <div class="form-section" style="border-color:#ddd6fe; background:#faf5ff;">
                                    <div class="mb-2" style="font-size:0.75rem; color:#7c3aed; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">
                                        <i class="fas fa-edit me-1"></i> Update Order
                                    </div>
                                    <form method="POST" action="" class="row g-2 align-items-end">
                                        <input type="hidden" name="item_id" value="<?php echo $is_official ? $item['official_order_id'] : $item['id']; ?>">
                                        <input type="hidden" name="item_type" value="<?php echo $is_official ? 'order' : 'request'; ?>">

                                        <div class="col-sm-4">
                                            <label>Order Status</label>
                                            <select name="order_status" class="form-select form-select-sm">
                                                <?php foreach(['Pending','Processing','Out for Delivery','Delivered','Cancelled'] as $s): ?>
                                                    <option value="<?php echo $s; ?>" <?php echo ($display_status == $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-sm-4">
                                            <label>Payment Status</label>
                                            <select name="payment_status" class="form-select form-select-sm">
                                                <option value="Pending"   <?php echo ($display_payment == 'Pending')   ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Completed" <?php echo ($display_payment == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                                <option value="Failed"    <?php echo ($display_payment == 'Failed')    ? 'selected' : ''; ?>>Failed</option>
                                                <option value="Refunded"  <?php echo ($display_payment == 'Refunded')  ? 'selected' : ''; ?>>Refunded</option>
                                            </select>
                                        </div>

                                        <div class="col-sm-4">
                                            <button class="btn btn-update w-100" type="submit" name="update_status"
                                                style="background:linear-gradient(135deg,#7c3aed,#4f46e5);">
                                                <i class="fas fa-save me-1"></i> Update
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <?php if($is_official): ?>
                                <a href="../fpdf_invoice.php?id=<?php echo $item['official_order_id']; ?>" target="_blank" class="invoice-btn">
                                    <i class="fas fa-file-invoice-dollar"></i> View Invoice PDF
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center border-0 shadow-sm rounded-3 py-4">
                <i class="fas fa-cube fa-2x mb-2 d-block" style="color:#7c3aed;"></i>
                No custom box orders assigned to you yet.
            </div>
        <?php endif; ?>


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
