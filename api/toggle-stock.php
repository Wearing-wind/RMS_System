<?php
// API Endpoint: Toggle Item Stock (In Stock / Sold Out)
require_once '../config.php';

header('Content-Type: application/json');

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? $_REQUEST['id'] ?? 0);
$status = sanitize($input['status'] ?? $_REQUEST['status'] ?? '');

if ($id > 0 && in_array($status, ['active', 'sold_out', 'inactive'])) {
    $stmt = $conn->prepare("UPDATE menu_items SET status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
}

$conn->close();
?>
