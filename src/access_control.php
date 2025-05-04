<?php
session_start();
require 'db.php'; // Include database connection

// Function to get user role
function getUserRole($user_id, $conn) {
    $query = "SELECT user_management.roles.role_name FROM user_management.users 
              JOIN user_management.roles ON user_management.users.role_id = user_management.roles.id 
              WHERE user_management.users.id = :user_id";
    
    return query_safe($conn, $query, [':user_id' => $user_id])->fetch(PDO::FETCH_ASSOC)['role_name'] ?? null;
}

// Function to check if a role has a permission
function hasPermission($role, $permission, $conn) {
    $query = "SELECT COUNT(*) as count FROM user_management.role_permissions 
              JOIN user_management.roles ON user_management.role_permissions.role_id = user_management.roles.id 
              JOIN user_management.permissions ON user_management.role_permissions.permission_id = user_management.permissions.id 
              WHERE user_management.roles.role_name = :role AND user_management.permissions.permission_name = :permission";
    
    $result = query_safe($conn, $query, [':role' => $role, ':permission' => $permission])->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
}

// Restrict access based on permission
function requirePermission($permission) {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        die("Access denied: Please log in.");
    }
    
    $role = getUserRole($_SESSION['user_id'], $conn);
    if (!hasPermission($role, $permission, $conn)) {
        die("Access denied: You do not have permission to view this page.");
    }
}

// Example: Restrict a page to only admins
// requirePermission('manage_users');
?>s