<?php
require 'access_control.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_online = $_POST['is_online'] ?? false;
    
    try {
        $query = "INSERT INTO user_management.user_status (user_id, is_online, last_seen) 
                 VALUES (:user_id, :is_online, CURRENT_TIMESTAMP)
                 ON CONFLICT (user_id) DO UPDATE 
                 SET is_online = :is_online, last_seen = CURRENT_TIMESTAMP";
        
        query_safe($conn, $query, [
            ':user_id' => $_SESSION['user_id'],
            ':is_online' => $is_online
        ]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>