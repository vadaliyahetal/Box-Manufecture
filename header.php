<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Use correct relative path - header is IN includes folder, so db.php is same directory
require_once __DIR__ . '/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Box Manufacturing Management - Om Rudra</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<?php
// Calculate Cart Count
$cart_count = 0;
if(isset($_SESSION['user_id'])){
    $u_id = $_SESSION['user_id'];
    $c_res = $conn->query("SELECT SUM(quantity) as count FROM cart WHERE user_id = $u_id");
    if($c_res && $row = $c_res->fetch_assoc()){ $cart_count = $row['count'] ?? 0; }
} else {
    if(isset($_SESSION['cart'])){
        $cart_count = array_sum($_SESSION['cart']); 
    }
}

// Calculate Wishlist Count
$wish_count = 0;
if(isset($_SESSION['user_id'])){
    $u_id = $_SESSION['user_id'];
    $w_res = $conn->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = $u_id");
    if($w_res && $row = $w_res->fetch_assoc()){ $wish_count = $row['count'] ?? 0; }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fas fa-boxes me-2 fs-4 text-warning"></i>
            <span class="fw-bold">Om Rudra Boxes</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="custom_box.php">Custom Box</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="consultations.php">Consultation</a></li>
            </ul>
            
            <!-- Search Box -->
            <form class="d-flex mx-3" action="products.php" method="GET">
                <div class="input-group">
                    <input class="form-control" type="search" name="search" placeholder="Search products..." aria-label="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-outline-warning" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        <i class="fa fa-shopping-cart"></i> Cart <span class="badge bg-secondary"><?php echo $cart_count; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="wishlist.php">
                        <i class="fa fa-heart"></i> Wishlist <span class="badge bg-secondary"><?php echo $wish_count; ?></span>
                    </a>
                </li>
                 <?php
                // Re-open session if needed to check login state properly or use existing
                if(session_status() == PHP_SESSION_NONE) session_start();
                if(isset($_SESSION['user_id'])): 
                    // Auto-fix missing session keys if user_id exist
                    if(!isset($_SESSION['user_name']) || !isset($_SESSION['user_role'])) {
                        $u_id = $_SESSION['user_id'];
                        $u_stmt = $conn->prepare("SELECT name, role FROM users WHERE id = ?");
                        $u_stmt->bind_param("i", $u_id);
                        $u_stmt->execute();
                        $u_res = $u_stmt->get_result();
                        if($u_row = $u_res->fetch_assoc()) {
                            $_SESSION['user_name'] = $u_row['name'];
                            $_SESSION['user_role'] = $u_row['role'];
                        }
                    }
                ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                            <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="admin/dashboard.php">Admin Panel</a></li>
                            <?php endif; ?>
                             <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'employee'): ?>
                                <li><a class="dropdown-item" href="employee/dashboard.php">Employee Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
