<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require 'db.php';

function verify_permission($permission) {
    global $conn;
    
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT p.permission_name 
                           FROM user_management.role_permissions rp
                           JOIN user_management.roles r ON rp.role_id = r.id
                           JOIN user_management.permissions p ON rp.permission_id = p.id
                           WHERE r.id = (SELECT role_id FROM user_management.users WHERE id = ?)
                           AND p.permission_name = ?");
    $stmt->execute([$_SESSION['user_id'], $permission]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        die('Access denied');
    }
}

// Middleware for API routes
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false && empty($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}
?>