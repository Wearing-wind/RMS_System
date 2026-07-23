<?php
// Admin Login Process - Simple Version
session_start();

// Start session and set admin directly for testing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple check - accept admin/admin123
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_username'] = 'admin';
        $_SESSION['admin_full_name'] = 'Administrator';
        
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['error'] = 'Invalid username or password. Try admin / admin123';
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?>
