<?php
// API - Update Order Status / Payment Method (Cash or QR)
header('Content-Type: application/json');
require_once '../config.php';

$conn = getDBConnection();

if ($conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$order_id = isset($input['order_id']) ? intval($input['order_id']) : 0;
$status = isset($input['status']) ? sanitize($input['status']) : '';
$reason = isset($input['reason']) ? sanitize($input['reason']) : '';
$payment_method = isset($input['payment_method']) ? sanitize($input['payment_method']) : '';

if ($order_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Handle payment method update (e.g., Cash or QR)
if (!empty($payment_method)) {
    $stmt = $conn->prepare("UPDATE orders SET payment_method = ? WHERE id = ?");
    $stmt->bind_param("si", $payment_method, $order_id);
    $stmt->execute();
    $stmt->close();

    if ($payment_method === 'cash') {
        // Automatically create a waiter call for cash payment collection
        $tbl_res = $conn->query("SELECT table_number FROM orders WHERE id = $order_id");
        if ($tbl_row = $tbl_res->fetch_assoc()) {
            $t_num = $tbl_row['table_number'];
            $w_stmt = $conn->prepare("INSERT INTO waiter_calls (table_number) VALUES (?)");
            $w_stmt->bind_param("s", $t_num);
            @$w_stmt->execute();
            @$w_stmt->close();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Payment method updated']);
    $conn->close();
    exit;
}

$valid_statuses = ['new', 'preparing', 'ready', 'completed', 'cancelled'];

if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

if ($status === 'cancelled' && !empty($reason)) {
    $stmt = $conn->prepare("UPDATE orders SET status = ?, notes = CONCAT(IFNULL(notes, ''), ' [REJECTED: ', ?, ']') WHERE id = ?");
    $stmt->bind_param("ssi", $status, $reason, $order_id);
} else {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
}

$stmt->close();
$conn->close();
?>
