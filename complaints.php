<?php
session_start();
include '../includes/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin'){
    header("Location: ../login.php");
    exit();
}

$message = "";

// Update Status
if (isset($_POST['update_status'])) {
    $comp_id = $_POST['comp_id'];
    $status = $_POST['status'];
    $conn->query("UPDATE complaints SET status = '$status' WHERE id = $comp_id");
    $message = "Complaint status updated.";
}

// Fetch Complaints
$complaints = $conn->query("SELECT c.*, u.name as user_name FROM complaints c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Complaint Management</h1>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Customer Complaints</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($complaints->num_rows > 0): ?>
                            <?php while($row = $complaints->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['user_name']; ?></td>
                                    <td><?php echo $row['subject']; ?></td>
                                    <td><?php echo $row['message']; ?></td>
                                    <td>
                                        <form method="POST" action="complaints.php" class="d-flex align-items-center">
                                            <input type="hidden" name="comp_id" value="<?php echo $row['id']; ?>">
                                            <select name="status" class="form-select form-select-sm me-2 status-select">
                                                <option value="Open" <?php echo ($row['status'] == 'Open') ? 'selected' : ''; ?>>Open</option>
                                                <option value="Resolved" <?php echo ($row['status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                                            </select>
                                    </td>
                                    <td><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                                    <td>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm"><i class="fas fa-save"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No complaints found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
