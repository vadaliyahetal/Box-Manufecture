<?php
session_start();
include '../includes/db.php';
// Check login
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin'){
    header("Location: ../login.php");
    exit();
}

$message = "";

// Delete
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $conn->query("DELETE FROM schedules WHERE id=$id");
    $message = "Schedule deleted.";
}

// Add
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emp_id = $_POST['employee_id'];
    $date = $_POST['work_date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $notes = $_POST['notes'];

    $conn->query("INSERT INTO schedules (employee_id, work_date, start_time, end_time, notes) VALUES ($emp_id, '$date', '$start', '$end', '$notes')");
    $message = "Schedule assigned successfully.";
}

// Fetch Schedules
$schedules = $conn->query("SELECT s.*, u.name as employee_name FROM schedules s JOIN users u ON s.employee_id = u.id ORDER BY s.work_date DESC");

// Fetch Employees
$employees = $conn->query("SELECT * FROM users WHERE role = 'employee'");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Employee Schedules</h1>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Add Form -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Assign Schedule</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label>Employee</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">Select Employee</option>
                                <?php while($emp = $employees->fetch_assoc()): ?>
                                    <option value="<?php echo $emp['id']; ?>"><?php echo $emp['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Date</label>
                            <input type="date" name="work_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label>Start Time</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label>End Time</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Shift Notes</label>
                            <textarea name="notes" class="form-control" placeholder="e.g. Morning Shift, Inventory Check"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Assign Schedule</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Schedule List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($schedules->num_rows > 0): ?>
                                    <?php while($row = $schedules->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['employee_name']; ?></td>
                                            <td><?php echo date("D, d M Y", strtotime($row['work_date'])); ?></td>
                                            <td><?php echo date("h:i A", strtotime($row['start_time'])) . ' - ' . date("h:i A", strtotime($row['end_time'])); ?></td>
                                            <td><?php echo $row['notes']; ?></td>
                                            <td>
                                                <a href="schedules.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove schedule?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center">No schedules found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
