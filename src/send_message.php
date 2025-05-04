<?php
require 'access_control.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conversation_id = $_POST['conversation_id'] ?? null;
    $sender_id = $_POST['sender_id'] ?? null;
    $content = $_POST['content'] ?? '';
    
    if (!$conversation_id || !$sender_id || empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit;
    }
    
    try {
        // Insert message
        $query = "INSERT INTO user_management.messages (conversation_id, sender_id, content) 
                 VALUES (:conversation_id, :sender_id, :content) 
                 RETURNING id, sent_at";
        
        $stmt = query_safe($conn, $query, [
            ':conversation_id' => $conversation_id,
            ':sender_id' => $sender_id,
            ':content' => $content
        ]);
        
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => [
                'id' => $message['id'],
                'content' => $content,
                'sent_at' => $message['sent_at']
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>