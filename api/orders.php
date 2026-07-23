<?php
// API - Get Orders for Kitchen Dashboard
header('Content-Type: application/json');

require_once '../config.php';

$conn = getDBConnection();

if ($conn === null) {
    echo json_encode(['orders' => [], 'error' => 'Database not connected']);
    exit;
}

$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get orders with their items
try {
    if ($status === 'all') {
        $result = $conn->query("SELECT * FROM orders WHERE status != 'cancelled' ORDER BY 
                CASE status 
                    WHEN 'new' THEN 1 
                    WHEN 'preparing' THEN 2 
                    WHEN 'ready' THEN 3 
                    WHEN 'completed' THEN 4 
                END, created_at DESC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC");
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }

    $orders = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Get order items
            $order_id = $row['id'];
            $items_stmt = $conn->prepare("
                SELECT oi.*, mi.name 
                FROM order_items oi 
                JOIN menu_items mi ON oi.menu_item_id = mi.id 
                WHERE oi.order_id = ?
            ");
            $items_stmt->bind_param("i", $order_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            $items = [];
            while ($item = $items_result->fetch_assoc()) {
                $items[] = $item;
            }
            $items_stmt->close();
            
            $row['items'] = $items;
            $orders[] = $row;
        }
    }

    echo json_encode(['orders' => $orders]);
} catch (Exception $e) {
    echo json_encode(['orders' => [], 'error' => $e->getMessage()]);
}

$conn->close();
?>
