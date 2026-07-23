<?php
// API - Get Orders for Kitchen Dashboard and Order Status Tracker
header('Content-Type: application/json');
require_once '../config.php';

$conn = getDBConnection();

if ($conn === null) {
    echo json_encode(['orders' => [], 'error' => 'Database connection failed']);
    exit;
}

// 1. Single Order Request (e.g., api/orders.php?id=123)
if (isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($order = $result->fetch_assoc()) {
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
        
        $order['items'] = $items;
        echo json_encode(['order' => $order]);
    } else {
        echo json_encode(['error' => 'Order not found']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

// 2. Multiple Orders Request (for Kitchen Dashboard)
$status = isset($_GET['status']) ? $_GET['status'] : 'active';
$include_all = isset($_GET['include_all']) && $_GET['include_all'] == '1';

try {
    if ($status === 'cancelled') {
        $query = "SELECT * FROM orders WHERE status = 'cancelled' ORDER BY updated_at DESC";
        $result = $conn->query($query);
    } else if ($status === 'completed') {
        $query = "SELECT * FROM orders WHERE status = 'completed' ORDER BY updated_at DESC";
        $result = $conn->query($query);
    } else if ($status === 'all_history' || $include_all) {
        $query = "SELECT * FROM orders ORDER BY created_at DESC";
        $result = $conn->query($query);
    } else {
        // Default 'active' status: ONLY show 'new', 'preparing', 'ready' orders
        $query = "SELECT * FROM orders WHERE status IN ('new', 'preparing', 'ready') ORDER BY 
                CASE status 
                    WHEN 'new' THEN 1 
                    WHEN 'preparing' THEN 2 
                    WHEN 'ready' THEN 3 
                END, created_at DESC";
        $result = $conn->query($query);
    }

    $orders = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
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
