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
    $id = $_GET['delete'];
    $conn->query("DELETE FROM categories WHERE id=$id");
    header("Location: categories.php");
}

// Handle Add/Edit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    
    if(isset($_POST['id']) && !empty($_POST['id'])){
        // Edit
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $desc, $id);
    } else {
        // Add
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $desc);
    }
    
    if($stmt->execute()){
        $message = "Category saved successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Fetch for Edit
$edit_data = null;
if(isset($_GET['edit'])){
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM categories WHERE id=$id");
    $edit_data = $result->fetch_assoc();
}

// Fetch All
$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Category Management</h1>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Add/Edit Form -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo $edit_data ? 'Edit' : 'Add New'; ?> Category</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="categories.php">
                        <?php if($edit_data): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label>Category Name</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo $edit_data ? $edit_data['name'] : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $edit_data ? $edit_data['description'] : ''; ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><?php echo $edit_data ? 'Update' : 'Save'; ?></button>
                        <?php if($edit_data): ?>
                            <a href="categories.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Categories</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($categories->num_rows > 0): ?>
                                    <?php while($row = $categories->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo $row['name']; ?></td>
                                            <td><?php echo $row['description']; ?></td>
                                            <td>
                                                <a href="categories.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                                                <a href="categories.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">No categories found</td></tr>
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
