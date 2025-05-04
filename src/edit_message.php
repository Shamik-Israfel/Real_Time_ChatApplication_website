<?php
require 'access_control.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = $_POST['message_id'] ?? null;
    $content = $_POST['content'] ?? '';
    
    if (!$message_id || empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit;
    }
    
    try {
        // Check if message can still be edited (within 15 minutes)
        $check_query = "SELECT sent_at FROM user_management.messages 
                       WHERE id = :message_id AND sender_id = :user_id 
                       AND is_unsent = FALSE";
        
        $check_stmt = query_safe($conn, $check_query, [
            ':message_id' => $message_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        $message = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$message) {
            echo json_encode(['success' => false, 'error' => 'Message not found or cannot be edited']);
            exit;
        }
        
        $sent_time = strtotime($message['sent_at']);
        $current_time = time();
        
        if (($current_time - $sent_time) > 900) { // 15 minutes in seconds
            echo json_encode(['success' => false, 'error' => 'Edit time has expired']);
            exit;
        }
        
        // Update message
        $update_query = "UPDATE user_management.messages 
                        SET content = :content, edited = TRUE, edited_at = CURRENT_TIMESTAMP 
                        WHERE id = :message_id 
                        RETURNING edited_at";
        
        $update_stmt = query_safe($conn, $update_query, [
            ':content' => $content,
            ':message_id' => $message_id
        ]);
        
        $result = $update_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'edited_at' => $result['edited_at']
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>