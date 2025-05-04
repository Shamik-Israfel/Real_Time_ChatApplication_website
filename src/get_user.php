<?php
session_start();
require 'db.php';

// Check if user is admin
$query = "SELECT user_management.roles.role_name FROM user_management.users 
          JOIN user_management.roles ON user_management.users.role_id = user_management.roles.id 
          WHERE user_management.users.id = :user_id";
$params = [':user_id' => $_SESSION['user_id']];

$result = query_safe($conn, $query, $params);
$user = $result->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role_name'] !== 'Admin') {
    http_response_code(403);
    exit('Unauthorized');
}

// Get all users
$query = "SELECT user_management.users.username, user_management.roles.role_name as role, 
          user_management.users.email
          FROM user_management.users 
          JOIN user_management.roles ON user_management.users.role_id = user_management.roles.id 
          ORDER BY user_management.users.id DESC";

$result = query_safe($conn, $query);

$users = array();
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $users[] = array(
        'username' => htmlspecialchars($row['username']),
        'role' => htmlspecialchars($row['role']),
        'email' => htmlspecialchars($row['email'])
        // Note: 'created_at' field isn't in the schema from db.sql, so it's replaced with email
    );
}

header('Content-Type: application/json');
echo json_encode($users);
?>