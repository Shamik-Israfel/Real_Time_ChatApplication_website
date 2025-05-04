<?php
require 'access_control.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = $_POST['message_id'] ?? null;
    
    if (!$message_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit;
    }
    
    try {
        // Check if message can still be unsent (within 15 minutes)
        $check_query = "SELECT sent_at FROM user_management.messages 
                       WHERE id = :message_id AND sender_id = :user_id 
                       AND is_unsent = FALSE";
        
        $check_stmt = query_safe($conn, $check_query, [
            ':message_id' => $message_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        $message = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$message) {
            echo json_encode(['success' => false, 'error' => 'Message not found or cannot be unsent']);
            exit;
        }
        
        $sent_time = strtotime($message['sent_at']);
        $current_time = time();
        
        if (($current_time - $sent_time) > 900) { // 15 minutes in seconds
            echo json_encode(['success' => false, 'error' => 'Unsend time has expired']);
            exit;
        }
        
        // Update message
        $update_query = "UPDATE user_management.messages 
                        SET is_unsent = TRUE 
                        WHERE id = :message_id";
        
        query_safe($conn, $update_query, [':message_id' => $message_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>