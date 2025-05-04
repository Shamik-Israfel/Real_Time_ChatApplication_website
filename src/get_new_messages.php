<?php
require 'access_control.php';
require 'db.php';

header('Content-Type: application/json');

$conversation_id = $_GET['conversation_id'] ?? null;
$last_message_id = $_GET['last_message_id'] ?? 0;

if (!$conversation_id) {
    echo json_encode(['messages' => []]);
    exit;
}

try {
    $query = "SELECT m.*, u.username as sender_name 
             FROM user_management.messages m
             JOIN user_management.users u ON m.sender_id = u.id
             WHERE m.conversation_id = :conversation_id 
             AND m.id > :last_message_id 
             AND m.is_unsent = FALSE
             ORDER BY m.sent_at ASC";
    
    $stmt = query_safe($conn, $query, [
        ':conversation_id' => $conversation_id,
        ':last_message_id' => $last_message_id
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['messages' => $messages]);
} catch (PDOException $e) {
    echo json_encode(['messages' => [], 'error' => $e->getMessage()]);
}
?>