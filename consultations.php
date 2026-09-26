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
    $cons_id = $_POST['cons_id'];
    $status = $_POST['status'];
    $conn->query("UPDATE consultations SET status = '$status' WHERE id = $cons_id");
    $message = "Consultation status updated.";
}

// Fetch Consultations
$consultations = $conn->query("SELECT c.*, u.name as user_name, u.mobile FROM consultations c JOIN users u ON c.user_id = u.id ORDER BY c.scheduled_date ASC");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Consultation Management</h1>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Scheduled Visits</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Mobile</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($consultations->num_rows > 0): ?>
                            <?php while($row = $consultations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['user_name']; ?></td>
                                    <td><?php echo $row['mobile']; ?></td>
                                    <td><?php echo $row['scheduled_date']; ?></td>
                                    <td><?php echo $row['scheduled_time']; ?></td>
                                    <td><?php echo $row['notes']; ?></td>
                                    <td>
                                        <form method="POST" action="consultations.php" class="d-flex">
                                            <input type="hidden" name="cons_id" value="<?php echo $row['id']; ?>">
                                            <select name="status" class="form-select form-select-sm me-2">
                                                <option value="Scheduled" <?php echo ($row['status'] == 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                                <option value="Completed" <?php echo ($row['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                                <option value="Cancelled" <?php echo ($row['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                    </td>
                                    <td>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm"><i class="fas fa-save"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No consultations scheduled</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
