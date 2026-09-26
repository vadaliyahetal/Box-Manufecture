<?php

function ensure_stock_movement_table(mysqli $conn): bool
{
    static $is_ensured = false;

    if ($is_ensured) {
        return true;
    }

    $sql = "CREATE TABLE IF NOT EXISTS stock_movements (
        id INT(11) NOT NULL AUTO_INCREMENT,
        product_id INT(11) NOT NULL,
        movement_type ENUM('IN', 'OUT') NOT NULL,
        quantity INT(11) NOT NULL,
        reason VARCHAR(100) DEFAULT NULL,
        reference_type VARCHAR(50) DEFAULT NULL,
        reference_id INT(11) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_stock_movements_created_at (created_at),
        INDEX idx_stock_movements_product_id (product_id),
        INDEX idx_stock_movements_type (movement_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($sql)) {
        return false;
    }

    $is_ensured = true;
    return true;
}

function log_stock_movement(
    mysqli $conn,
    int $product_id,
    string $movement_type,
    int $quantity,
    string $reason = '',
    string $reference_type = '',
    ?int $reference_id = null
): bool {
    if ($quantity <= 0) {
        return false;
    }

    if (!ensure_stock_movement_table($conn)) {
        return false;
    }

    $stmt = $conn->prepare(
        "INSERT INTO stock_movements (product_id, movement_type, quantity, reason, reference_type, reference_id)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "isissi",
        $product_id,
        $movement_type,
        $quantity,
        $reason,
        $reference_type,
        $reference_id
    );

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

