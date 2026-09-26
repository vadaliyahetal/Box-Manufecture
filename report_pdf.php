<?php
session_start();
include '../includes/db.php';
require_once '../includes/stock_movement.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

function pdf_escape(string $text): string
{
    $text = str_replace("\\", "\\\\", $text);
    $text = str_replace("(", "\\(", $text);
    $text = str_replace(")", "\\)", $text);
    $text = preg_replace('/[^\x20-\x7E]/', ' ', $text);
    return $text;
}

function build_simple_pdf(array $pages): string
{
    $objects = [];
    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";

    $font_obj = 3;
    $objects[$font_obj] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

    $kids = [];
    $obj_id = 4;

    foreach ($pages as $page_lines) {
        $content = "BT\n/F1 10 Tf\n";
        $y = 770;
        foreach ($page_lines as $line) {
            $safe_line = pdf_escape($line);
            $content .= "1 0 0 1 40 " . $y . " Tm (" . $safe_line . ") Tj\n";
            $y -= 14;
        }
        $content .= "ET";

        $content_obj = $obj_id++;
        $page_obj = $obj_id++;

        $objects[$content_obj] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        $objects[$page_obj] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 " . $font_obj . " 0 R >> >> /Contents " . $content_obj . " 0 R >>";
        $kids[] = $page_obj . " 0 R";
    }

    $objects[2] = "<< /Type /Pages /Kids [" . implode(" ", $kids) . "] /Count " . count($kids) . " >>";
    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [];

    foreach ($objects as $id => $body) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
    }

    $xref_pos = strlen($pdf);
    $max_id = max(array_keys($objects));

    $pdf .= "xref\n0 " . ($max_id + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $max_id; $i++) {
        $offset = $offsets[$i] ?? 0;
        $pdf .= str_pad((string)$offset, 10, "0", STR_PAD_LEFT) . " 00000 n \n";
    }

    $pdf .= "trailer\n<< /Size " . ($max_id + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xref_pos . "\n%%EOF";

    return $pdf;
}

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
$status_rows = $status_stmt->get_result();
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
$payment_rows = $payment_stmt->get_result();
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

$lines = [];
$lines[] = "OM RUDRA BOX MANUFACTURING - DAILY REPORT";
$lines[] = "Report Date: " . date('d M Y', strtotime($selected_date));
$lines[] = "Generated At: " . date('d M Y h:i A');
$lines[] = str_repeat("-", 95);
$lines[] = "SUMMARY";
$lines[] = "Total Sales: Rs " . number_format((float)$summary['gross_sales'], 2);
$lines[] = "Paid Sales: Rs " . number_format((float)$summary['paid_sales'], 2);
$lines[] = "Orders Received: " . (int)$summary['orders_received'];
$lines[] = "Items Ordered: " . (int)$items_data['total_items'];
$lines[] = "Stock Updated (+): " . $stock_added . " units";
$lines[] = "Stock Reduced (-): " . $stock_reduced . " units";
$lines[] = "";
$lines[] = "ORDER STATUS BREAKDOWN";
if ($status_rows->num_rows > 0) {
    while ($row = $status_rows->fetch_assoc()) {
        $lines[] = "- " . $row['order_status'] . ": " . (int)$row['total'];
    }
} else {
    $lines[] = "- No orders for this date";
}

$lines[] = "";
$lines[] = "PAYMENT BREAKDOWN";
if ($payment_rows->num_rows > 0) {
    while ($row = $payment_rows->fetch_assoc()) {
        $lines[] = "- " . $row['payment_status'] . " / " . $row['payment_method'] . ": " . (int)$row['total'];
    }
} else {
    $lines[] = "- No payment data for this date";
}

$lines[] = "";
$lines[] = "ORDER DETAILS";
if ($order_details->num_rows > 0) {
    $lines[] = "ID      Customer                 Type     Items  Amount      Status       Payment";
    $lines[] = str_repeat("-", 95);
    while ($row = $order_details->fetch_assoc()) {
        $id = "#" . str_pad((string)$row['id'], 6, "0", STR_PAD_LEFT);
        $customer = substr($row['customer_name'], 0, 22);
        $type = substr($row['order_type'], 0, 8);
        $items = str_pad((string)(int)$row['total_items'], 5, " ", STR_PAD_LEFT);
        $amount = str_pad(number_format((float)$row['total_amount'], 2), 10, " ", STR_PAD_LEFT);
        $status = substr($row['order_status'], 0, 11);
        $payment = substr(($row['payment_method'] . "/" . $row['payment_status']), 0, 22);
        $lines[] = str_pad($id, 8) . " " .
                   str_pad($customer, 24) . " " .
                   str_pad($type, 8) . " " .
                   $items . "  " .
                   $amount . "  " .
                   str_pad($status, 11) . "  " .
                   $payment;
    }
} else {
    $lines[] = "No orders found for this date";
}

$lines[] = "";
$lines[] = "STOCK MOVEMENT DETAILS";
if ($stock_details->num_rows > 0) {
    $lines[] = "Time    Product                    Type     Qty   Reason";
    $lines[] = str_repeat("-", 95);
    while ($row = $stock_details->fetch_assoc()) {
        $time = date('H:i', strtotime($row['created_at']));
        $product = substr($row['product_name'], 0, 25);
        $type = $row['movement_type'] === 'IN' ? 'Stock In' : 'Stock Out';
        $qty = str_pad((string)(int)$row['quantity'], 4, " ", STR_PAD_LEFT);
        $reason = substr(($row['reason'] ?? '-'), 0, 45);
        $lines[] = str_pad($time, 6) . "  " .
                   str_pad($product, 25) . "  " .
                   str_pad($type, 8) . "  " .
                   $qty . "  " .
                   $reason;
    }
} else {
    $lines[] = "No stock updates found for this date";
}

$max_lines_per_page = 52;
$pages = array_chunk($lines, $max_lines_per_page);
$pdf_binary = build_simple_pdf($pages);

$filename = "daily_report_" . $selected_date . ".pdf";
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Content-Length: " . strlen($pdf_binary));
echo $pdf_binary;
exit();

