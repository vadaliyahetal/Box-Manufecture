<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../includes/db.php';
require_once '../includes/stock_movement.php';

$message = "";

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM products WHERE id=$id");
    header("Location: products.php");
}

// Handle Add/Edit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $size = $_POST['size'];
    $stock = $_POST['stock'];

    // Image Upload
    $image = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image = "uploads/products/" . $image_name;
        }
    }
    else {
        // Keep old image if editing
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $old_prod = $conn->query("SELECT image FROM products WHERE id=" . $_POST['id'])->fetch_assoc();
            $image = $old_prod['image'];
        }
    }

    $is_edit = isset($_POST['id']) && !empty($_POST['id']);
    $existing_stock = null;

    if ($is_edit) {
        $stock_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $edit_id = (int)$_POST['id'];
        $stock_stmt->bind_param("i", $edit_id);
        $stock_stmt->execute();
        $stock_result = $stock_stmt->get_result()->fetch_assoc();
        $existing_stock = $stock_result ? (int)$stock_result['stock'] : null;
        $stock_stmt->close();
    }

    if ($is_edit) {
        // Edit
        $id = $_POST['id'];
        $sql = "UPDATE products SET name=?, category_id=?, description=?, price=?, size=?, stock=?, image=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisdsisi", $name, $category_id, $desc, $price, $size, $stock, $image, $id);
    }
    else {
        // Add
        $sql = "INSERT INTO products (name, category_id, description, price, size, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisdsis", $name, $category_id, $desc, $price, $size, $stock, $image);
    }

    if ($stmt->execute()) {
        ensure_stock_movement_table($conn);

        if ($is_edit && $existing_stock !== null) {
            $diff = (int)$stock - $existing_stock;
            if ($diff > 0) {
                log_stock_movement($conn, (int)$id, 'IN', $diff, 'Manual stock update', 'product_edit', (int)$id);
            }
            elseif ($diff < 0) {
                log_stock_movement($conn, (int)$id, 'OUT', abs($diff), 'Manual stock correction', 'product_edit', (int)$id);
            }
        }
        elseif (!$is_edit && (int)$stock > 0) {
            $new_product_id = (int)$conn->insert_id;
            log_stock_movement($conn, $new_product_id, 'IN', (int)$stock, 'Initial stock', 'product_create', $new_product_id);
        }

        $message = "Product saved successfully!";
    }
    else {
        $message = "Error: " . $conn->error;
    }
}

// Fetch for Edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM products WHERE id=$id");
    $edit_data = $result->fetch_assoc();
}

// Fetch All Products
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Product Management</h1>
        <a href="products.php?add=true" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Product</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php
endif; ?>

    <?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?php echo $edit_data ? 'Edit Product' : 'Add New Product'; ?></h6>
        </div>
        <div class="card-body">
            <form method="POST" action="products.php" enctype="multipart/form-data">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <?php
    endif; ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Product Name</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo $edit_data ? $edit_data['name'] : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Category</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php
    $cats = $conn->query("SELECT * FROM categories");
    while ($cat = $cats->fetch_assoc()) {
        $selected = ($edit_data && $edit_data['category_id'] == $cat['id']) ? 'selected' : '';
        echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
    }
?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo $edit_data ? $edit_data['price'] : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Size</label>
                        <input type="text" name="size" class="form-control" placeholder="e.g. 10x10x10 cm" value="<?php echo $edit_data ? $edit_data['size'] : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" class="form-control" required value="<?php echo $edit_data ? $edit_data['stock'] : '100'; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Product Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <?php if ($edit_data && $edit_data['image']): ?>
                            <small>Current Image: <img src="../<?php echo $edit_data['image']; ?>" width="50"></small>
                        <?php
    endif; ?>
                    </div>
                    <div class="col-12 mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo $edit_data ? $edit_data['description'] : ''; ?></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Product</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <?php
else: ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Products</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($products) && $products->num_rows > 0): ?>
                            <?php while ($row = $products->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if ($row['image']): ?>
                                            <img src="../<?php echo $row['image']; ?>" width="50" height="50" style="object-fit: cover;">
                                        <?php
            else: ?>
                                            <span class="text-muted">No Img</span>
                                        <?php
            endif; ?>
                                    </td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['category_name']; ?></td>
                                    <td>₹<?php echo $row['price']; ?></td>
                                    <td><?php echo $row['stock']; ?></td>
                                    <td>
                                        <a href="products.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i></a>
                                        <a href="products.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php
        endwhile; ?>
                        <?php
    else: ?>
                            <tr><td colspan="6" class="text-center">No products found</td></tr>
                        <?php
    endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
