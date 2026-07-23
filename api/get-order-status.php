<?php
// API Endpoint to check live status of a single order
header('Content-Type: application/json');
require_once '../config.php';

$conn = getDBConnection();

if ($conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$stmt = $conn->prepare("SELECT id, table_number, status, notes, total_amount, created_at FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $row = $res->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'order' => $row
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
}

$stmt->close();
$conn->close();
?>
