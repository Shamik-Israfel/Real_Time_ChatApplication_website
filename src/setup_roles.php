<?php
require 'db.php';

// These operations are no longer needed as they're already included in db.sql
// But we'll keep the file as a utility in case you need to re-run setup

// Check if roles already exist
$query = "SELECT COUNT(*) as count FROM user_management.roles";
$result = query_safe($conn, $query)->fetch(PDO::FETCH_ASSOC);

if ($result['count'] > 0) {
    echo "Roles already exist. Setup has already been completed.<br>";
    echo "If you need to reset the setup, please truncate the tables first.";
    exit;
}

try {
    // Insert roles
    $query = "INSERT INTO user_management.roles (role_name) VALUES ('Admin'), ('User'), ('Guest')";
    query_safe($conn, $query);

    // Insert permissions
    $query = "INSERT INTO user_management.permissions (permission_name) VALUES 
        ('manage_users'), ('edit_profile'), ('view_dashboard')";
    query_safe($conn, $query);

    // Assign permissions to roles - Admin
    $query = "INSERT INTO user_management.role_permissions (role_id, permission_id) 
        SELECT r.id, p.id FROM user_management.roles r, user_management.permissions p 
        WHERE r.role_name = 'Admin' AND p.permission_name IN ('manage_users', 'edit_profile', 'view_dashboard')";
    query_safe($conn, $query);

    // Assign permissions to roles - User
    $query = "INSERT INTO user_management.role_permissions (role_id, permission_id) 
        SELECT r.id, p.id FROM user_management.roles r, user_management.permissions p 
        WHERE r.role_name = 'User' AND p.permission_name IN ('edit_profile', 'view_dashboard')";
    query_safe($conn, $query);

    // Assign permissions to roles - Guest
    $query = "INSERT INTO user_management.role_permissions (role_id, permission_id) 
        SELECT r.id, p.id FROM user_management.roles r, user_management.permissions p 
        WHERE r.role_name = 'Guest' AND p.permission_name IN ('view_dashboard')";
    query_safe($conn, $query);

    echo "Roles and permissions setup complete!";
} catch (PDOException $e) {
    echo "Setup failed: " . $e->getMessage();
}
?>