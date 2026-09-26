<?php
session_start();
include '../includes/db.php';
// Check if user is logged in AND is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin'){
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Request ID");
}

$id = $_GET['id'];
$request = $conn->query("SELECT r.*, u.name, u.email, u.mobile FROM custom_box_requests r JOIN users u ON r.user_id = u.id WHERE r.id = $id")->fetch_assoc();

if (!$request) {
    die("Request not found");
}
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Custom Box Request #<?php echo $request['id']; ?></h1>
        <a href="custom_requests.php" class="btn btn-secondary btn-sm shadow-sm"><i class="fas fa-arrow-left fa-sm text-white-50"></i> Back Not List</a>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Customer Details</h6>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo $request['name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $request['email']; ?></p>
                    <p><strong>Mobile:</strong> <?php echo $request['mobile']; ?></p>
                    <p><strong>Date Requested:</strong> <?php echo date("F d, Y h:i A", strtotime($request['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Status & Pricing</h6>
                </div>
                <div class="card-body">
                    <h4 class="text-primary mb-3">Estimated Price: ₹<?php echo number_format($request['calculated_price'], 2); ?></h4>
                    
                    <form method="POST" action="custom_requests.php" class="d-flex align-items-center">
                        <input type="hidden" name="req_id" value="<?php echo $request['id']; ?>">
                        <label class="me-2 fw-bold">Status:</label>
                        <select name="status" class="form-select form-select-sm me-3" style="width: auto;">
                            <option value="Pending" <?php echo ($request['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo ($request['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo ($request['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                            <option value="Completed" <?php echo ($request['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Box Specifications</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>Length:</strong> <?php echo $request['length'] . ' ' . $request['unit']; ?></div>
                        <div class="col-md-3"><strong>Width:</strong> <?php echo $request['width'] . ' ' . $request['unit']; ?></div>
                        <div class="col-md-3"><strong>Height:</strong> <?php echo $request['height'] . ' ' . $request['unit']; ?></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6"><strong>Material Type:</strong> <?php echo ucfirst($request['material']); ?></div>
                        <div class="col-md-6"><strong>Quality / Ply:</strong> <?php echo ucfirst($request['quality']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Uploaded Files</h6>
                </div>
                <div class="card-body d-flex justify-content-around">
                    <?php if($request['design_file']): ?>
                        <div class="text-center">
                            <p class="mb-2"><strong>Custom Design</strong></p>
                            <a href="../<?php echo $request['design_file']; ?>" target="_blank" class="btn btn-info btn-icon-split">
                                <span class="icon text-white-50"><i class="fas fa-file-download"></i></span>
                                <span class="text">Download Design</span>
                            </a>
                            <p class="small text-muted mt-1">Price: ₹<?php echo $request['design_price']; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">No Design Uploaded</div>
                    <?php endif; ?>

                    <?php if($request['logo_file']): ?>
                        <div class="text-center">
                            <p class="mb-2"><strong>Logo</strong></p>
                            <a href="../<?php echo $request['logo_file']; ?>" target="_blank" class="btn btn-warning btn-icon-split">
                                <span class="icon text-white-50"><i class="fas fa-file-image"></i></span>
                                <span class="text">Download Logo</span>
                            </a>
                            <p class="small text-muted mt-1">Price: ₹<?php echo $request['logo_price']; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted">No Logo Uploaded</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
