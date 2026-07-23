<?php
// Place Order - Process and save order to database
require_once 'config.php';

$conn = getDBConnection();

// Check database connection
if ($conn === null) {
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    die("Database connection failed. Please check your configuration.");
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}

// Check if request is JSON payload or form data
$is_ajax = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || 
           (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
           isset($_POST['ajax']);

if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $raw_input = file_get_contents('php://input');
    $input_data = json_decode($raw_input, true) ?: [];
    $table_number = sanitize($input_data['table_number'] ?? '1');
    $customer_name = sanitize($input_data['customer_name'] ?? '');
    $notes = sanitize($input_data['notes'] ?? '');
    $cart = $input_data['cart'] ?? [];
} else {
    $table_number = sanitize($_POST['table_number'] ?? '1');
    $customer_name = sanitize($_POST['customer_name'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $cart_json = isset($_POST['cart_data']) ? $_POST['cart_data'] : '[]';
    $cart = json_decode($cart_json, true) ?: [];
}

// Validate table number
if (empty($table_number) || $table_number === '0') {
    $table_number = '1';
}

if (empty($cart)) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
        exit;
    }
    $_SESSION['error'] = 'Your cart is empty';
    header('Location: cart.php?table=' . urlencode($table_number));
    exit;
}

// Calculate total & format item customization note
$total = 0;
$formatted_cart = [];
foreach ($cart as $item) {
    $item_price = floatval($item['price']);
    $item_qty = intval($item['quantity']);
    $total += $item_price * $item_qty;
    
    // Customization text
    $custom_text = '';
    if (!empty($item['customizations'])) {
        $c = $item['customizations'];
        if (!empty($c['spice_level'])) {
            $custom_text .= ' (' . ucfirst($c['spice_level']) . ')';
        }
        if (!empty($c['extras']) && is_array($c['extras'])) {
            $extra_names = array_column($c['extras'], 'name');
            $custom_text .= ' [+' . implode(', ', $extra_names) . ']';
        }
    }
    
    $item['displayName'] = $item['name'] . $custom_text;
    $formatted_cart[] = $item;
}

// Start transaction
$conn->begin_transaction();

try {
    // Check if total_amount and payment_status columns exist in orders table
    $stmt = $conn->prepare("INSERT INTO orders (table_number, customer_name, notes, status, total_amount, payment_status) VALUES (?, ?, ?, 'new', ?, 'pending')");
    
    if (!$stmt) {
        // Fallback query if total_amount column is somehow missing
        $stmt = $conn->prepare("INSERT INTO orders (table_number, customer_name, notes, status) VALUES (?, ?, ?, 'new')");
        if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        $stmt->bind_param("sss", $table_number, $customer_name, $notes);
    } else {
        $stmt->bind_param("sssd", $table_number, $customer_name, $notes, $total);
    }

    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();
    
    // Insert order items
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
    if (!$item_stmt) {
        throw new Exception("Failed to prepare order items insert: " . $conn->error);
    }

    foreach ($cart as $item) {
        $item_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        $price = floatval($item['price']);
        
        $item_stmt->bind_param("iiid", $order_id, $item_id, $quantity, $price);
        $item_stmt->execute();
    }
    
    $item_stmt->close();
    $conn->commit();
    
    $order_details = [
        'id' => $order_id,
        'table_number' => $table_number,
        'customer_name' => $customer_name,
        'notes' => $notes,
        'items' => $formatted_cart,
        'total' => $total,
        'payment_status' => 'pending'
    ];
    
    $_SESSION['last_order'] = $order_details;
    
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'order_id' => $order_id, 'order' => $order_details]);
        exit;
    }
    
    header('Location: order-success.php?order_id=' . $order_id);
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
        exit;
    }
    $_SESSION['error'] = 'Failed to place order: ' . $e->getMessage();
    header('Location: checkout.php?table=' . urlencode($table_number));
    exit;
}

$conn->close();
?>
