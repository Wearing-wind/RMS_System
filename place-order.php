<?php
// Place Order - Process and save order to database
require_once 'config.php';

$conn = getDBConnection();

// Check database connection
if ($conn === null) {
    die("Database connection failed. Please check your configuration.");
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}

// Get form data
$table_number = isset($_POST['table_number']) ? intval($_POST['table_number']) : 0;
$customer_name = isset($_POST['customer_name']) ? sanitize($_POST['customer_name']) : '';
$notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';

// Validate table number
if ($table_number === 0) {
    $_SESSION['error'] = 'Invalid table number';
    header('Location: checkout.php');
    exit;
}

// Get cart from form submission
$cart_json = isset($_POST['cart_data']) ? $_POST['cart_data'] : '[]';
$cart = json_decode($cart_json, true);

if (empty($cart)) {
    $_SESSION['error'] = 'Your cart is empty';
    header('Location: cart.php?table=' . $table_number);
    exit;
}

// Calculate total
$total = 0;
foreach ($cart as $item) {
    $total += floatval($item['price']) * intval($item['quantity']);
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert order with total amount and payment status
    $stmt = $conn->prepare("INSERT INTO orders (table_number, customer_name, notes, status, total_amount, payment_status) VALUES (?, ?, ?, 'new', ?, 'pending')");
    $stmt->bind_param("issd", $table_number, $customer_name, $notes, $total);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();
    
    // Insert order items
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($cart as $item) {
        $item_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        $price = floatval($item['price']);
        
        $item_stmt->bind_param("iiid", $order_id, $item_id, $quantity, $price);
        $item_stmt->execute();
    }
    
    $item_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Get order details for confirmation
    $order_details = [
        'id' => $order_id,
        'table_number' => $table_number,
        'customer_name' => $customer_name,
        'notes' => $notes,
        'items' => $cart,
        'total' => $total,
        'payment_status' => 'pending'
    ];
    
    // Store order details in session for success page
    $_SESSION['last_order'] = $order_details;
    
    // Redirect to success page
    header('Location: order-success.php?order_id=' . $order_id);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = 'Failed to place order. Please try again.';
    header('Location: checkout.php?table=' . $table_number);
    exit;
}

$conn->close();
?>
