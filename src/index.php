<?php
session_start();
require 'db.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Get user role
    $query = "SELECT roles.role_name FROM users 
              JOIN roles ON users.role_id = roles.id 
              WHERE users.id = $1";
    $stmt = query_safe($conn, $query, [$_SESSION['user_id']]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC)['role_name'] ?? null;

    // Redirect based on role
    if ($role === 'Admin') {
        header('Location: admin_page.php');
    } else {
        header('Location: dashboard.php');
    }
} else {
    // Redirect to login page
    header('Location: login.php');
}
exit;
?>