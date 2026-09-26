<?php
session_start();
include '../includes/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin'){
    header("Location: ../login.php");
    exit();
}

$message = "";

// Update Status and Assignment
if (isset($_POST['update_status'])) {
    $req_id = intval($_POST['req_id']);
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];
    $emp_id = !empty($_POST['assigned_employee_id']) ? intval($_POST['assigned_employee_id']) : null;
    
    $stmt = $conn->prepare("UPDATE custom_box_requests SET status = ?, payment_status = ?, assigned_employee_id = ? WHERE id = ?");
    $stmt->bind_param("ssii", $status, $payment_status, $emp_id, $req_id);
    
    if($stmt->execute()){
         $message = "Order status, payment status and assignment updated successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Fetch Requests
$requests = $conn->query("SELECT r.*, u.name as user_name FROM custom_box_requests r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");

// Fetch Employees for assignment
$employees = $conn->query("SELECT id, name FROM users WHERE role = 'employee'");
$emp_options = [];
while($emp = $employees->fetch_assoc()){
    $emp_options[$emp['id']] = $emp['name'];
}
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Custom Box Orders</h1>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Custom Orders</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" width="100%" cellspacing="0" style="font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Dimensions</th>
                            <th>Material/Quality</th>
                            <th>Design/Logo</th>
                            <th>Est. Price</th>
                            <th>Order Status</th>
                            <th>Payment Status</th>
                            <th>Assigned To</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($requests->num_rows > 0): ?>
                            <?php while($row = $requests->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo $row['user_name']; ?></td>
                                    <td><?php echo "{$row['length']}x{$row['width']}x{$row['height']} {$row['unit']}"; ?></td>
                                    <td><?php echo "{$row['material']} / {$row['quality']}"; ?></td>
                                    <td>
                                        <?php if($row['design_file']): ?>
                                            <div class="mb-1">
                                                <img src="../<?php echo $row['design_file']; ?>" class="rounded border shadow-sm" style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;" title="View Design" onclick="window.open(this.src)">
                                            </div>
                                        <?php endif; ?>
                                        <?php if($row['logo_file']): ?>
                                            <div>
                                                <img src="../<?php echo $row['logo_file']; ?>" class="rounded border shadow-sm" style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;" title="View Logo" onclick="window.open(this.src)">
                                            </div>
                                        <?php endif; ?>
                                        <?php if(!$row['design_file'] && !$row['logo_file']): ?>
                                            <span class="text-muted small">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>₹<?php echo number_format($row['calculated_price'], 2); ?></td>
                                    <td>
                                        <form method="POST" action="custom_requests.php" class="d-flex align-items-center">
                                            <input type="hidden" name="req_id" value="<?php echo $row['id']; ?>">
                                            <select name="status" class="form-select form-select-sm me-2" style="width: 140px;">
                                                <?php
                                                $req_statuses = ['Pending','Approved','Processing','Out for Delivery','Delivered','Cancelled','Rejected'];
                                                foreach($req_statuses as $rs):
                                                ?>
                                                    <option value="<?php echo $rs; ?>" <?php echo ($row['status'] == $rs) ? 'selected' : ''; ?>><?php echo $rs; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                    </td>
                                    <td>
                                        <select name="payment_status" class="form-select form-select-sm me-2" style="width: 120px;">
                                            <?php
                                            $pay_statuses = ['Pending','Completed','Failed','Refunded'];
                                            foreach($pay_statuses as $ps):
                                            ?>
                                                <option value="<?php echo $ps; ?>" <?php echo (($row['payment_status'] ?? 'Pending') == $ps) ? 'selected' : ''; ?>><?php echo $ps; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="assigned_employee_id" class="form-select form-select-sm" style="width: 140px;">
                                            <option value="">Unassigned</option>
                                            <?php foreach($emp_options as $eid => $ename): ?>
                                                <option value="<?php echo $eid; ?>" <?php echo ($row['assigned_employee_id'] == $eid) ? 'selected' : ''; ?>><?php echo $ename; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm"><i class="fas fa-save"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No requests found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
