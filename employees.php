<?php
session_start();
include '../includes/db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin'){
    header("Location: ../login.php");
    exit();
}

$message = "";

// Handle Delete
if(isset($_GET['delete'])){
    $user_id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$user_id AND role='employee'");
    $message = "Employee removed successfully!";
}

// Handle Add/Edit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // In plain text as per user request
    $mobile = $_POST['mobile'];
    $role = 'employee';

    if(isset($_POST['id']) && !empty($_POST['id'])){
        // Edit
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, mobile=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $password, $mobile, $id);
    } else {
        // Add
        // Check if email exists
        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if($check->num_rows > 0){
            $message = "Error: Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, mobile, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $password, $mobile, $role);
        }
    }
    
    if(isset($stmt) && $stmt->execute()){
         $message = "Employee saved successfully!";
    }
}

// Fetch Employees
$employees = $conn->query("SELECT * FROM users WHERE role='employee' ORDER BY created_at DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Employee Management</h1>
    </div>

    <?php if($message): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Add Form -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Employee</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="employees.php">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Mobile Number</label>
                            <input type="text" name="mobile" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Employee</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Employee List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Since</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($employees->num_rows > 0): ?>
                                    <?php while($row = $employees->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo $row['name']; ?></td>
                                            <td><?php echo $row['email']; ?></td>
                                            <td><?php echo $row['mobile']; ?></td>
                                            <td><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <a href="employees.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">No employees found</td></tr>
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
