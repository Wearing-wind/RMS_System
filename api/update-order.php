<?php
// API - Update Order Status
header('Content-Type: application/json');

require_once '../config.php';

$conn = getDBConnection();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$order_id = isset($input['order_id']) ? intval($input['order_id']) : 0;
$status = isset($input['status']) ? sanitize($input['status']) : '';

$valid_statuses = ['new', 'preparing', 'ready', 'completed', 'cancelled'];

if ($order_id === 0 || !in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order']);
}

$stmt->close();
$conn->close();
?>
