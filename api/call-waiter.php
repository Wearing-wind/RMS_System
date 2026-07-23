<?php
// Call Waiter API Endpoint
header('Content-Type: application/json');
require_once '../config.php';

$conn = getDBConnection();

if ($conn === null) {
    echo json_encode(['success' => false, 'message' => 'Database not connected.']);
    exit;
}

// Ensure waiter_calls table exists
$result = $conn->query("SHOW TABLES LIKE 'waiter_calls'");
if (!$result || $result->num_rows == 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS waiter_calls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_number VARCHAR(10) NOT NULL,
        status ENUM('pending', 'served') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
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
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $table_number = sanitize($input['table_number'] ?? $_POST['table_number'] ?? $_GET['table_number'] ?? '');
    
    if (empty($table_number)) {
        echo json_encode(['success' => false, 'message' => 'Table number is required']);
        exit;
    }
    
    // Check if there's already a pending call from this table within last 2 minutes
    $check_stmt = $conn->prepare("SELECT id FROM waiter_calls WHERE table_number = ? AND status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE) LIMIT 1");
    $check_stmt->bind_param("s", $table_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result && $check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Waiter already called for Table ' . $table_number . '. Staff notified!']);
        $check_stmt->close();
        $conn->close();
        exit;
    }
    if ($check_stmt) $check_stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO waiter_calls (table_number) VALUES (?)");
    $stmt->bind_param("s", $table_number);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '🔔 Waiter call sent for Table ' . $table_number . '! Staff on the way.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error sending waiter call']);
    }
    
    $stmt->close();
} else {
    // Get all pending waiter calls for KDS / Admin
    $result = $conn->query("SELECT * FROM waiter_calls WHERE status = 'pending' ORDER BY created_at DESC");
    $calls = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $calls[] = $row;
        }
    }
    echo json_encode(['success' => true, 'calls' => $calls]);
}

$conn->close();
?>
