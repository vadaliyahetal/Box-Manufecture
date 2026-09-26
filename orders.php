<?php
session_start();
include '../includes/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin'){
    header("Location: ../login.php");
    exit();
}

$message = "";
$msg_type = "success";

// Update Status
if (isset($_POST['update_status'])) {
    $order_id     = intval($_POST['order_id']);
    $status       = $_POST['order_status'];
    $inv_status   = $_POST['invoice_status'];
    $pay_status   = $_POST['payment_status'];
    $emp_id       = !empty($_POST['assigned_employee_id']) ? intval($_POST['assigned_employee_id']) : null;

    if ($status == 'Refunded') {
        $sql = "UPDATE orders SET order_status = ?, invoice_status = ?, payment_status = ?, assigned_employee_id = ?, refund_status = 'Refunded' WHERE id = ?";
    } else {
        $sql = "UPDATE orders SET order_status = ?, invoice_status = ?, payment_status = ?, assigned_employee_id = ? WHERE id = ?";
    }

    $stmt = $conn->prepare($sql);
    if ($emp_id === null) {
        $stmt->bind_param("ssssi", $status, $inv_status, $pay_status, $emp_id, $order_id);
    } else {
        $stmt->bind_param("sssii", $status, $inv_status, $pay_status, $emp_id, $order_id);
    }

    if ($stmt->execute()) {
        $message = "Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " updated successfully!";
    } else {
        $message  = "Error updating order: " . $conn->error;
        $msg_type = "danger";
    }
    $stmt->close();
}

// Fetch Orders
$orders = $conn->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");

// Fetch Employees for assignment
$employees = $conn->query("SELECT id, name FROM users WHERE role = 'employee'");
$emp_options = [];
while ($emp = $employees->fetch_assoc()) {
    $emp_options[$emp['id']] = $emp['name'];
}
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Order Management</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Orders</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Order Status</th>
                            <th>Invoice Status</th>
                            <th>Payment</th>
                            <th>Assign Employee</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders->num_rows > 0): ?>
                            <?php while ($row = $orders->fetch_assoc()): ?>
                                <tr>
                                    <!-- Order ID -->
                                    <td><strong>#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>

                                    <!-- Customer Name -->
                                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>

                                    <!-- Amount -->
                                    <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>

                                    <!-- Each row has its OWN self-contained form -->
                                    <td colspan="4">
                                        <form method="POST" action="orders.php" class="row g-2 align-items-center">
                                            <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">

                                            <!-- Order Status Dropdown -->
                                            <div class="col-auto">
                                                <label class="form-label mb-0 small text-muted">Order Status</label>
                                                <select name="order_status" class="form-select form-select-sm" style="min-width:140px;">
                                                    <?php
                                                    $statuses = ['Pending','Processing','Success','Delivered','Cancelled','Return Requested','Refunded'];
                                                    foreach ($statuses as $s):
                                                    ?>
                                                        <option value="<?php echo $s; ?>" <?php echo ($row['order_status'] == $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Invoice Status Dropdown -->
                                            <div class="col-auto">
                                                <label class="form-label mb-0 small text-muted">Invoice Status</label>
                                                <select name="invoice_status" class="form-select form-select-sm" style="min-width:130px;">
                                                    <?php
                                                    $inv_statuses = ['Pending','Generated','Sent','Paid'];
                                                    foreach ($inv_statuses as $iv):
                                                    ?>
                                                        <option value="<?php echo $iv; ?>" <?php echo ($row['invoice_status'] == $iv) ? 'selected' : ''; ?>><?php echo $iv; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Payment Status Dropdown -->
                                            <div class="col-auto">
                                                <label class="form-label mb-0 small text-muted">Payment Status</label>
                                                <select name="payment_status" class="form-select form-select-sm" style="min-width:130px;">
                                                    <?php
                                                    $pay_statuses = ['Pending','Completed','Failed','Refunded'];
                                                    foreach ($pay_statuses as $ps):
                                                    ?>
                                                        <option value="<?php echo $ps; ?>" <?php echo ($row['payment_status'] == $ps) ? 'selected' : ''; ?>><?php echo $ps; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Assign Employee Dropdown -->
                                            <div class="col-auto">
                                                <label class="form-label mb-0 small text-muted">Assign To</label>
                                                <select name="assigned_employee_id" class="form-select form-select-sm" style="min-width:140px;">
                                                    <option value="">Unassigned</option>
                                                    <?php foreach ($emp_options as $eid => $ename): ?>
                                                        <option value="<?php echo $eid; ?>" <?php echo ($row['assigned_employee_id'] == $eid) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($ename); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Save Button -->
                                            <div class="col-auto" style="margin-top:18px;">
                                                <button type="submit" name="update_status" class="btn btn-primary btn-sm" title="Save Changes">
                                                    <i class="fas fa-save me-1"></i> Save
                                                </button>
                                            </div>
                                        </form>
                                    </td>

                                    <!-- Payment Info Display (read-only summary) -->
                                    <td>
                                        <div class="small">
                                            <?php
                                            $psBadge = match($row['payment_status']) {
                                                'Completed' => 'success',
                                                'Pending'   => 'warning text-dark',
                                                'Failed'    => 'danger',
                                                'Refunded'  => 'secondary',
                                                default     => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $psBadge; ?>">
                                                <?php echo $row['payment_status']; ?>
                                            </span><br>
                                            <span class="text-muted"><?php echo $row['payment_method']; ?></span>
                                            <?php if (!empty($row['refund_amount']) && $row['refund_amount'] > 0): ?>
                                                <div class="text-danger mt-1 fw-bold small">
                                                    Refund: ₹<?php echo number_format($row['refund_amount'], 2); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-nowrap">
                                        <!-- View Order Details -->
                                        <a href="../order_details.php?id=<?php echo $row['id']; ?>"
                                           class="btn btn-info btn-sm mb-1" target="_blank" title="View Details">
                                            <i class="fas fa-eye"></i> Details
                                        </a>
                                        <!-- Download Invoice PDF -->
                                        <a href="../fpdf_invoice.php?id=<?php echo $row['id']; ?>"
                                           class="btn btn-success btn-sm mb-1" target="_blank" title="Download Invoice PDF">
                                            <i class="fas fa-file-invoice"></i> Invoice PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
