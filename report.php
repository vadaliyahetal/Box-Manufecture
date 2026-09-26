<?php
include 'includes/header.php';
require_once '../includes/stock_movement.php';

ensure_stock_movement_table($conn);

$selected_date = $_GET['report_date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date) || strtotime($selected_date) === false) {
    $selected_date = date('Y-m-d');
}

$start_datetime = $selected_date . ' 00:00:00';
$end_datetime = date('Y-m-d H:i:s', strtotime($selected_date . ' +1 day'));

$summary_stmt = $conn->prepare("
    SELECT
        COUNT(*) AS orders_received,
        COALESCE(SUM(total_amount), 0) AS gross_sales,
        COALESCE(SUM(CASE WHEN payment_status = 'Completed' THEN total_amount ELSE 0 END), 0) AS paid_sales
    FROM orders
    WHERE created_at >= ? AND created_at < ?
");
$summary_stmt->bind_param("ss", $start_datetime, $end_datetime);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();
$summary_stmt->close();

$items_stmt = $conn->prepare("
    SELECT COALESCE(SUM(oi.quantity), 0) AS total_items
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.created_at >= ? AND o.created_at < ?
");
$items_stmt->bind_param("ss", $start_datetime, $end_datetime);
$items_stmt->execute();
$items_data = $items_stmt->get_result()->fetch_assoc();
$items_stmt->close();

$status_stmt = $conn->prepare("
    SELECT order_status, COUNT(*) AS total
    FROM orders
    WHERE created_at >= ? AND created_at < ?
    GROUP BY order_status
    ORDER BY total DESC, order_status ASC
");
$status_stmt->bind_param("ss", $start_datetime, $end_datetime);
$status_stmt->execute();
$status_breakdown = $status_stmt->get_result();
$status_stmt->close();

$payment_stmt = $conn->prepare("
    SELECT payment_status, payment_method, COUNT(*) AS total
    FROM orders
    WHERE created_at >= ? AND created_at < ?
    GROUP BY payment_status, payment_method
    ORDER BY total DESC, payment_status ASC, payment_method ASC
");
$payment_stmt->bind_param("ss", $start_datetime, $end_datetime);
$payment_stmt->execute();
$payment_breakdown = $payment_stmt->get_result();
$payment_stmt->close();

$stock_stmt = $conn->prepare("
    SELECT movement_type, COALESCE(SUM(quantity), 0) AS total_qty
    FROM stock_movements
    WHERE created_at >= ? AND created_at < ?
    GROUP BY movement_type
");
$stock_stmt->bind_param("ss", $start_datetime, $end_datetime);
$stock_stmt->execute();
$stock_result = $stock_stmt->get_result();

$stock_added = 0;
$stock_reduced = 0;
while ($row = $stock_result->fetch_assoc()) {
    if ($row['movement_type'] === 'IN') {
        $stock_added = (int)$row['total_qty'];
    } elseif ($row['movement_type'] === 'OUT') {
        $stock_reduced = (int)$row['total_qty'];
    }
}
$stock_stmt->close();

$orders_stmt = $conn->prepare("
    SELECT
        o.id,
        o.order_type,
        o.total_amount,
        o.order_status,
        o.payment_method,
        o.payment_status,
        o.invoice_status,
        o.created_at,
        u.name AS customer_name,
        COALESCE(SUM(oi.quantity), 0) AS total_items
    FROM orders o
    JOIN users u ON u.id = o.user_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.created_at >= ? AND o.created_at < ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders_stmt->bind_param("ss", $start_datetime, $end_datetime);
$orders_stmt->execute();
$order_details = $orders_stmt->get_result();
$orders_stmt->close();

$stock_details_stmt = $conn->prepare("
    SELECT
        sm.created_at,
        p.name AS product_name,
        sm.movement_type,
        sm.quantity,
        sm.reason,
        sm.reference_type,
        sm.reference_id
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    WHERE sm.created_at >= ? AND sm.created_at < ?
    ORDER BY sm.created_at DESC
");
$stock_details_stmt->bind_param("ss", $start_datetime, $end_datetime);
$stock_details_stmt->execute();
$stock_details = $stock_details_stmt->get_result();
$stock_details_stmt->close();
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Daily Report</h1>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="report.php" class="d-flex gap-2 align-items-center mb-0">
                <label for="report_date" class="form-label mb-0">Date</label>
                <input type="date" id="report_date" name="report_date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>">
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </form>
            <a href="report_pdf.php?report_date=<?php echo urlencode($selected_date); ?>" class="btn btn-success">
                <i class="fas fa-file-pdf me-1"></i> Download PDF
            </a>
        </div>
    </div>

    <div class="alert alert-info">
        Report Date: <strong><?php echo date('d M Y', strtotime($selected_date)); ?></strong>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-success border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Sales</div>
                    <div class="h5 mb-0 fw-bold">Rs <?php echo number_format((float)$summary['gross_sales'], 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-primary border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">Orders Received</div>
                    <div class="h5 mb-0 fw-bold"><?php echo (int)$summary['orders_received']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-warning border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">Stock Updated (+)</div>
                    <div class="h5 mb-0 fw-bold"><?php echo $stock_added; ?> units</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-danger border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs fw-bold text-danger text-uppercase mb-1">Stock Reduced (-)</div>
                    <div class="h5 mb-0 fw-bold"><?php echo $stock_reduced; ?> units</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Status Summary</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Status</th>
                                    <th>Total Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($status_breakdown->num_rows > 0): ?>
                                    <?php while ($row = $status_breakdown->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['order_status']); ?></td>
                                            <td><?php echo (int)$row['total']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center text-muted">No orders for this date.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Payment Summary</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Payment Status</th>
                                    <th>Method</th>
                                    <th>Total Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($payment_breakdown->num_rows > 0): ?>
                                    <?php while ($row = $payment_breakdown->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                                            <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                            <td><?php echo (int)$row['total']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted">No payment data for this date.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Order Details</h6>
            <small class="text-muted">Items Ordered: <?php echo (int)$items_data['total_items']; ?> | Paid Sales: Rs <?php echo number_format((float)$summary['paid_sales'], 2); ?></small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Order Status</th>
                            <th>Payment</th>
                            <th>Invoice</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($order_details->num_rows > 0): ?>
                            <?php while ($row = $order_details->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo str_pad((int)$row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['order_type']); ?></td>
                                    <td><?php echo (int)$row['total_items']; ?></td>
                                    <td>Rs <?php echo number_format((float)$row['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['order_status']); ?></td>
                                    <td><?php echo htmlspecialchars($row['payment_method']) . ' / ' . htmlspecialchars($row['payment_status']); ?></td>
                                    <td><?php echo htmlspecialchars($row['invoice_status']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center text-muted">No orders found for this date.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Stock Movement Details</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reason</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stock_details->num_rows > 0): ?>
                            <?php while ($row = $stock_details->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('h:i A', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><?php echo $row['movement_type'] === 'IN' ? 'Stock In' : 'Stock Out'; ?></td>
                                    <td><?php echo (int)$row['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($row['reason'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(($row['reference_type'] ?? '-') . (!empty($row['reference_id']) ? ' #' . $row['reference_id'] : '')); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">No stock updates found for this date.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
