<?php
// Call Waiter API
header('Content-Type: application/json');
require_once '../config.php';

$conn = getDBConnection();

if ($conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database not connected. Please run setup first.']);
    exit;
}

// Check if waiter_calls table exists, if not create it
$result = $conn->query("SHOW TABLES LIKE 'waiter_calls'");
if ($result->num_rows == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS waiter_calls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_number VARCHAR(10) NOT NULL,
        status ENUM('pending', 'served') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createTable);
}

// Handle serve action
if (isset($_GET['action']) && $_GET['action'] === 'serve' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE waiter_calls SET status = 'served' WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Call marked as served']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating call']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_number = sanitize($_POST['table_number'] ?? '');
    
    if (empty($table_number)) {
        echo json_encode(['success' => false, 'message' => 'Table number is required']);
        exit;
    }
    
    // Check if there's already a pending call from this table within last 5 minutes
    $check_stmt = $conn->prepare("SELECT id FROM waiter_calls WHERE table_number = ? AND status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1");
    $check_stmt->bind_param("s", $table_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Waiter already called for this table. Please wait.']);
        $check_stmt->close();
        $conn->close();
        exit;
    }
    $check_stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO waiter_calls (table_number) VALUES (?)");
    $stmt->bind_param("s", $table_number);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Waiter has been notified!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error calling waiter']);
    }
    
    $stmt->close();
} else {
    // Get all pending waiter calls (for admin/kitchen)
    $result = $conn->query("SELECT * FROM waiter_calls WHERE status = 'pending' ORDER BY created_at DESC");
    $calls = [];
    while ($row = $result->fetch_assoc()) {
        $calls[] = $row;
    }
    echo json_encode(['success' => true, 'calls' => $calls]);
}

$conn->close();
?>
