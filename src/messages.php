<?php
require 'includes/session_config.php';
require 'includes/auth.php';
require 'includes/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate conversation access
        $stmt = $pdo->prepare("SELECT id FROM user_management.conversations 
                             WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
        $stmt->execute([$input['conversation_id'], $user_id, $user_id]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Invalid conversation");
        }

        // Insert message
        $stmt = $pdo->prepare("INSERT INTO user_management.messages 
                              (conversation_id, sender_id, content) 
                              VALUES (?, ?, ?)");
        $stmt->execute([
            $input['conversation_id'],
            $user_id,
            $input['content']
        ]);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}