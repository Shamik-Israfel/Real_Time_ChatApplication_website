<?php
session_start();
require 'db.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize all variables
$current_user_id = $_SESSION['user_id'];
$username = 'User';
$role = 'Guest';
$users = [];
$selected_user_id = null;
$conversation = null;
$messages = [];
$current_status = ['is_online' => false, 'last_seen' => null];
$unread_counts = [];

// Get user info
$user_query = "SELECT u.username, r.role_name FROM user_management.users u
              JOIN user_management.roles r ON u.role_id = r.id
              WHERE u.id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->execute([$current_user_id]);
if ($user_data = $user_stmt->fetch(PDO::FETCH_ASSOC)) {
    $username = $user_data['username'];
    $role = $user_data['role_name'];
}

// Update user status to online
try {
    $update_status = "INSERT INTO user_management.user_status (user_id, is_online, last_seen) 
                     VALUES (?, TRUE, CURRENT_TIMESTAMP)
                     ON CONFLICT (user_id) DO UPDATE 
                     SET is_online = TRUE, last_seen = CURRENT_TIMESTAMP";
    $conn->prepare($update_status)->execute([$current_user_id]);
} catch (PDOException $e) {
    error_log("Error updating user status: " . $e->getMessage());
}

// Get all users except current user
$users_query = "SELECT id, username FROM user_management.users WHERE id != ?";
$users_stmt = $conn->prepare($users_query);
$users_stmt->execute([$current_user_id]);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread message counts (with fallback if is_read column doesn't exist)
try {
    $unread_query = "SELECT 
        c.id as conversation_id,
        u.id as other_user_id,
        COUNT(m.id) as unread_count
    FROM user_management.conversations c
    JOIN user_management.users u ON 
        (c.user1_id = u.id AND c.user2_id = ?) OR 
        (c.user1_id = ? AND c.user2_id = u.id)
    LEFT JOIN user_management.messages m ON 
        m.conversation_id = c.id AND 
        m.sender_id = u.id AND
        (m.is_read = FALSE OR m.is_read IS NULL)
    WHERE u.id != ?
    GROUP BY c.id, u.id";
    
    $unread_stmt = $conn->prepare($unread_query);
    $unread_stmt->execute([$current_user_id, $current_user_id, $current_user_id]);
    while ($row = $unread_stmt->fetch(PDO::FETCH_ASSOC)) {
        $unread_counts[$row['other_user_id']] = $row['unread_count'];
    }
} catch (PDOException $e) {
    error_log("Error fetching unread counts: " . $e->getMessage());
    // Fallback - show all messages as read
    foreach ($users as $user) {
        $unread_counts[$user['id']] = 0;
    }
}

// Check if a conversation is selected
$selected_user_id = $_GET['user_id'] ?? null;
if ($selected_user_id && is_numeric($selected_user_id)) {
    // Get or create conversation
    try {
        $conversation_query = "SELECT id FROM user_management.conversations 
                             WHERE (user1_id = ? AND user2_id = ?) 
                             OR (user1_id = ? AND user2_id = ?)";
        $conversation_stmt = $conn->prepare($conversation_query);
        $conversation_stmt->execute([
            $current_user_id, $selected_user_id, 
            $selected_user_id, $current_user_id
        ]);
        $conversation = $conversation_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            $create_conversation = "INSERT INTO user_management.conversations (user1_id, user2_id) 
                                  VALUES (?, ?) RETURNING id";
            $create_stmt = $conn->prepare($create_conversation);
            $create_stmt->execute([
                min($current_user_id, $selected_user_id),
                max($current_user_id, $selected_user_id)
            ]);
            $conversation = $create_stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Get messages if conversation exists
        if ($conversation) {
            $messages_query = "SELECT m.*, u.username as sender_name 
                             FROM user_management.messages m
                             JOIN user_management.users u ON m.sender_id = u.id
                             WHERE m.conversation_id = ? AND m.is_unsent = FALSE
                             ORDER BY m.sent_at ASC";
            $messages_stmt = $conn->prepare($messages_query);
            $messages_stmt->execute([$conversation['id']]);
            $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Try to mark messages as read if column exists
            try {
                $mark_read_query = "UPDATE user_management.messages 
                                  SET is_read = TRUE 
                                  WHERE conversation_id = ? AND sender_id != ?";
                $conn->prepare($mark_read_query)->execute([$conversation['id'], $current_user_id]);
            } catch (PDOException $e) {
                error_log("Note: is_read column doesn't exist or error marking messages as read: " . $e->getMessage());
            }
            
            // Clear unread count for this conversation
            if (isset($unread_counts[$selected_user_id])) {
                $unread_counts[$selected_user_id] = 0;
            }
        }
    } catch (PDOException $e) {
        error_log("Error in conversation handling: " . $e->getMessage());
    }
}

// Get current user status
try {
    $status_query = "SELECT is_online, last_seen FROM user_management.user_status WHERE user_id = ?";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->execute([$current_user_id]);
    $current_status = $status_stmt->fetch(PDO::FETCH_ASSOC) ?: $current_status;
} catch (PDOException $e) {
    error_log("Error fetching user status: " . $e->getMessage());
}

// Check for dark mode preference
$dark_mode = $_COOKIE['dark_mode'] ?? 'false';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusChat Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #5865F2;
            --primary-dark: #4752C4;
            --primary-light: #EBEEFE;
            --accent: #ED4245;
            --text-primary: #060607;
            --text-secondary: #4E5058;
            --background: #FFFFFF;
            --background-secondary: #F2F3F5;
            --background-tertiary: #E3E5E8;
            --message-sent: #E3F2FD;
            --message-received: #FFFFFF;
            --online: #3BA55D;
            --offline: #747F8D;
            --typing: #FAA61A;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --unread-badge: #ED4245;
            --unread-badge-text: #FFFFFF;
        }

        .dark-mode {
            --text-primary: #FFFFFF;
            --text-secondary: #B9BBBE;
            --background: #36393F;
            --background-secondary: #2F3136;
            --background-tertiary: #202225;
            --message-sent: #0D4F7E;
            --message-received: #40444B;
            --shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--background-secondary);
            color: var(--text-primary);
            height: 100vh;
            display: flex;
            transition: background-color 0.3s, color 0.3s;
        }

        .app-container {
            display: flex;
            width: 100%;
            height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .sidebar {
            width: 360px;
            background-color: var(--background);
            border-right: 1px solid var(--background-tertiary);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .user-header {
            padding: 16px 20px;
            background-color: var(--background);
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--background-tertiary);
            position: relative;
        }

        .theme-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-secondary);
            font-size: 18px;
            transition: color 0.2s;
        }

        .theme-toggle:hover {
            color: var(--primary);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #9C84EF);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
            font-size: 18px;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
        }

        .user-status {
            font-size: 12px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            background-color: var(--online);
        }

        .search-container {
            padding: 12px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border-radius: 8px;
            border: none;
            background-color: var(--background-secondary);
            font-size: 14px;
            color: var(--text-primary);
            transition: all 0.2s;
        }

        .search-input:focus {
            outline: 2px solid var(--primary-light);
        }

        .search-icon {
            position: absolute;
            left: 24px;
            top: 22px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .contacts-list {
            flex: 1;
            overflow-y: auto;
        }

        .contact-item {
            display: flex;
            padding: 12px 16px;
            cursor: pointer;
            transition: background-color 0.2s;
            border-bottom: 1px solid var(--background-tertiary);
            position: relative;
        }

        .contact-item:hover {
            background-color: var(--background-secondary);
        }

        .contact-item.active {
            background-color: var(--primary-light);
        }

        .contact-item.unread {
            font-weight: 600;
        }

        .unread-badge {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background-color: var(--unread-badge);
            color: var(--unread-badge-text);
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
        }

        .contact-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF6B6B, #FFA3A3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
            font-size: 18px;
        }

        .contact-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
        }

        .contact-name {
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .contact-status {
            font-size: 13px;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--background-secondary);
            position: relative;
        }

        .chat-header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--background-tertiary);
            background-color: var(--background);
            z-index: 10;
        }

        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4CAF50, #8BC34A);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
            font-size: 18px;
        }

        .chat-info {
            flex: 1;
        }

        .chat-name {
            font-weight: 600;
        }

        .chat-status {
            font-size: 13px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
        }

        .chat-actions {
            display: flex;
            gap: 20px;
        }

        .chat-action {
            color: var(--text-secondary);
            cursor: pointer;
            transition: color 0.2s;
            font-size: 18px;
        }

        .chat-action:hover {
            color: var(--primary);
        }

        .messages-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: var(--background-secondary);
            background-image: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path fill="%23e3e5e8" d="M30 10L10 30v60h60l20-20V10H30zM30 0H0v30L30 0zm40 0L0 70v30h30l70-70V0H70z"/></svg>');
            background-size: 300px;
            background-repeat: repeat;
            display: flex;
            flex-direction: column;
        }

        .dark-mode .messages-container {
            background-image: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path fill="%23202225" d="M30 10L10 30v60h60l20-20V10H30zM30 0H0v30L30 0zm40 0L0 70v30h30l70-70V0H70z"/></svg>');
        }

        .message {
            max-width: 65%;
            margin-bottom: 16px;
            position: relative;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-sent {
            align-self: flex-end;
            background-color: var(--message-sent);
            border-radius: 18px 18px 0 18px;
            padding: 12px 16px;
            box-shadow: var(--shadow);
        }

        .message-received {
            align-self: flex-start;
            background-color: var(--message-received);
            border-radius: 18px 18px 18px 0;
            padding: 12px 16px;
            box-shadow: var(--shadow);
        }

        .message-content {
            font-size: 15px;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .message-meta {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 4px;
            font-size: 11px;
            color: var(--text-secondary);
        }

        .message-time {
            margin-left: 6px;
        }

        .message-edited {
            font-style: italic;
            font-size: 10px;
            color: var(--text-secondary);
            margin-left: 4px;
        }

        .message-actions {
            position: absolute;
            top: -12px;
            right: 0;
            background-color: var(--background);
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            display: none;
            z-index: 5;
            overflow: hidden;
        }

        .message:hover .message-actions {
            display: flex;
        }

        .message-action {
            padding: 8px 12px;
            cursor: pointer;
            font-size: 12px;
            color: var(--text-primary);
            transition: background-color 0.2s;
        }

        .message-action:hover {
            background-color: var(--background-secondary);
        }

        .message-action.delete {
            color: var(--accent);
        }

        .chat-input-container {
            padding: 16px;
            background-color: var(--background);
            border-top: 1px solid var(--background-tertiary);
            display: flex;
            align-items: center;
        }

        .input-actions {
            display: flex;
            gap: 12px;
            margin-right: 12px;
        }

        .input-action {
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 20px;
            transition: color 0.2s;
        }

        .input-action:hover {
            color: var(--primary);
        }

        .message-input {
            flex: 1;
            padding: 12px 16px;
            border-radius: 20px;
            border: none;
            background-color: var(--background-secondary);
            font-size: 15px;
            color: var(--text-primary);
            outline: none;
            resize: none;
            max-height: 120px;
            line-height: 1.4;
            transition: all 0.2s;
        }

        .message-input:focus {
            outline: 2px solid var(--primary-light);
        }

        .send-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 12px;
            transition: background-color 0.2s;
        }

        .send-button:hover {
            background-color: var(--primary-dark);
        }

        .send-button:disabled {
            background-color: var(--background-tertiary);
            cursor: not-allowed;
        }

        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 40px;
        }

        .empty-icon {
            font-size: 80px;
            color: var(--background-tertiary);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .empty-description {
            font-size: 16px;
            color: var(--text-secondary);
            max-width: 400px;
            line-height: 1.5;
        }

        .slang-warning {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--accent);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: none;
            z-index: 100;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateX(-50%) translateY(20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }

        @media (max-width: 1200px) {
            .sidebar {
                width: 320px;
            }
        }

        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: 50vh;
            }
            
            .chat-area {
                height: 50vh;
            }
        }
    </style>
</head>
<body class="<?php echo $dark_mode === 'true' ? 'dark-mode' : ''; ?>">
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="user-header">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    <div class="user-status">
                        <span class="status-indicator"></span>
                        <?php echo $current_status['is_online'] ? 'Online' : 'Offline'; ?>
                    </div>
                </div>
                <div class="theme-toggle" id="theme-toggle">
                    <?php echo $dark_mode === 'true' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>'; ?>
                </div>
            </div>

            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search conversations...">
            </div>

            <div class="contacts-list">
                <?php foreach ($users as $user): 
                    $user_status = ['is_online' => false, 'last_seen' => null];
                    try {
                        $status_stmt = $conn->prepare(
                            "SELECT is_online, last_seen FROM user_management.user_status WHERE user_id = ?"
                        );
                        $status_stmt->execute([$user['id']]);
                        $user_status = $status_stmt->fetch(PDO::FETCH_ASSOC) ?: $user_status;
                    } catch (PDOException $e) {
                        error_log("Error fetching user status: " . $e->getMessage());
                    }
                    
                    $unread_count = $unread_counts[$user['id']] ?? 0;
                ?>
                    <a href="chat.php?user_id=<?php echo $user['id']; ?>" class="contact-item <?php echo $selected_user_id == $user['id'] ? 'active' : ''; ?> <?php echo $unread_count > 0 ? 'unread' : ''; ?>">
                        <div class="contact-avatar" style="background: linear-gradient(135deg, <?php echo sprintf('#%06X', mt_rand(0, 0xFFFFFF)); ?>, <?php echo sprintf('#%06X', mt_rand(0, 0xFFFFFF)); ?>);">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                        <div class="contact-info">
                            <div class="contact-name"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="contact-status">
                                <?php if ($user_status['is_online']): ?>
                                    <span style="color: var(--online); margin-right: 5px;">●</span> Online
                                <?php else: ?>
                                    Last seen <?php echo $user_status['last_seen'] ? date('h:i A', strtotime($user_status['last_seen'])) : 'recently'; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($unread_count > 0): ?>
                            <div class="unread-badge"><?php echo $unread_count; ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($selected_user_id): 
                $selected_user = ['username' => 'Unknown'];
                $selected_status = ['is_online' => false, 'last_seen' => null];
                
                try {
                    $selected_user_stmt = $conn->prepare(
                        "SELECT username FROM user_management.users WHERE id = ?"
                    );
                    $selected_user_stmt->execute([$selected_user_id]);
                    $selected_user = $selected_user_stmt->fetch(PDO::FETCH_ASSOC) ?: $selected_user;
                    
                    $status_stmt = $conn->prepare(
                        "SELECT is_online, last_seen FROM user_management.user_status WHERE user_id = ?"
                    );
                    $status_stmt->execute([$selected_user_id]);
                    $selected_status = $status_stmt->fetch(PDO::FETCH_ASSOC) ?: $selected_status;
                } catch (PDOException $e) {
                    error_log("Error fetching selected user info: " . $e->getMessage());
                }
            ?>
                <div class="chat-header">
                    <div class="chat-avatar" style="background: linear-gradient(135deg, <?php echo sprintf('#%06X', mt_rand(0, 0xFFFFFF)); ?>, <?php echo sprintf('#%06X', mt_rand(0, 0xFFFFFF)); ?>);">
                        <?php echo strtoupper(substr($selected_user['username'], 0, 1)); ?>
                    </div>
                    <div class="chat-info">
                        <div class="chat-name"><?php echo htmlspecialchars($selected_user['username']); ?></div>
                        <div class="chat-status">
                            <?php if ($selected_status['is_online']): ?>
                                <span style="color: var(--online); margin-right: 5px;">●</span> Online
                            <?php else: ?>
                                Last seen <?php echo $selected_status['last_seen'] ? date('h:i A', strtotime($selected_status['last_seen'])) : 'recently'; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <i class="fas fa-phone-alt chat-action"></i>
                        <i class="fas fa-video chat-action"></i>
                        <i class="fas fa-ellipsis-v chat-action"></i>
                    </div>
                </div>

                <div class="messages-container" id="messages-container">
                    <?php foreach ($messages as $message): 
                        $is_sent = $message['sender_id'] == $current_user_id;
                        $message_class = $is_sent ? 'message-sent' : 'message-received';
                    ?>
                        <div class="message <?php echo $message_class; ?>" data-message-id="<?php echo $message['id']; ?>">
                            <?php if ($is_sent): ?>
                                <div class="message-actions">
                                    <?php 
                                        $message_time = strtotime($message['sent_at']);
                                        $current_time = time();
                                        $time_diff = $current_time - $message_time;
                                    ?>
                                    <?php if ($time_diff < 900 && !$message['is_unsent']): ?>
                                        <div class="message-action edit-message" data-message-id="<?php echo $message['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </div>
                                    <?php endif; ?>
                                    <div class="message-action delete unsend-message" data-message-id="<?php echo $message['id']; ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="message-content"><?php echo htmlspecialchars($message['content']); ?></div>
                            <div class="message-meta">
                                <?php if ($message['edited']): ?>
                                    <span class="message-edited">edited</span>
                                <?php endif; ?>
                                <span class="message-time">
                                    <?php echo date('h:i A', strtotime($message['sent_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="chat-input-container">
                    <div class="input-actions">
                        <i class="far fa-smile input-action" id="emoji-button"></i>
                        <i class="fas fa-paperclip input-action"></i>
                    </div>
                    <textarea class="message-input" id="message-input" placeholder="Type a message..." rows="1"></textarea>
                    <button class="send-button" id="send-button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="empty-chat">
                    <div class="empty-icon">
                        <i class="far fa-comment-dots"></i>
                    </div>
                    <div class="empty-title">Welcome to NexusChat</div>
                    <div class="empty-description">
                        Select a conversation to start messaging or create a new one.
                        Enjoy seamless communication with your contacts.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Slang Warning -->
    <div class="slang-warning" id="slang-warning">
        <i class="fas fa-exclamation-circle"></i> Your message contains restricted words and cannot be sent
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Auto-scroll to bottom of messages
            const messagesContainer = $('#messages-container');
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);

            // Auto-resize textarea
            $('#message-input').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Toggle dark/light mode
            $('#theme-toggle').on('click', function() {
                const isDark = $('body').hasClass('dark-mode');
                $('body').toggleClass('dark-mode');
                $(this).html(isDark ? '<i class="fas fa-moon"></i>' : '<i class="fas fa-sun"></i>');
                
                // Set cookie for 1 year
                document.cookie = `dark_mode=${!isDark}; path=/; max-age=${60*60*24*365}`;
            });

            // Slang words to filter
            const slangWords = ['law', 'law department', 'school of law and justice', 'love', 'attraction'];

            // Check for slang words
            function containsSlang(text) {
                const lowerText = text.toLowerCase();
                return slangWords.some(word => lowerText.includes(word.toLowerCase()));
            }

            // Show slang warning
            function showSlangWarning() {
                const warning = $('#slang-warning');
                warning.fadeIn();
                setTimeout(() => {
                    warning.fadeOut();
                }, 3000);
            }

            // Message form submission
            $('#send-button').on('click', function() {
                const messageInput = $('#message-input');
                const message = messageInput.val().trim();
                
                if (containsSlang(message)) {
                    showSlangWarning();
                    return;
                }
                
                if (message && <?php echo $selected_user_id ? 'true' : 'false'; ?>) {
                    // Optimistic UI update - add message immediately
                    const tempId = 'temp-' + Date.now();
                    const messageHtml = `
                        <div class="message message-sent" data-message-id="${tempId}">
                            <div class="message-content">${message}</div>
                            <div class="message-meta">
                                <span class="message-time">Sending...</span>
                            </div>
                        </div>
                    `;
                    
                    $('#messages-container').append(messageHtml);
                    messageInput.val('');
                    messageInput.css('height', 'auto');
                    messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
                    
                    // Send to server
                    $.post('send_message.php', {
                        conversation_id: <?php echo $conversation['id'] ?? 0; ?>,
                        sender_id: <?php echo $current_user_id; ?>,
                        content: message
                    }, function(response) {
                        if (response.success) {
                            // Replace temp message with real one
                            $(`[data-message-id="${tempId}"]`).replaceWith(`
                                <div class="message message-sent" data-message-id="${response.message.id}">
                                    <div class="message-actions">
                                        <div class="message-action edit-message" data-message-id="${response.message.id}">
                                            <i class="fas fa-edit"></i> Edit
                                        </div>
                                        <div class="message-action delete unsend-message" data-message-id="${response.message.id}">
                                            <i class="fas fa-trash"></i> Delete
                                        </div>
                                    </div>
                                    <div class="message-content">${message}</div>
                                    <div class="message-meta">
                                        <span class="message-time">
                                            ${new Date(response.message.sent_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                        </span>
                                    </div>
                                </div>
                            `);
                        } else {
                            // Remove temp message if failed
                            $(`[data-message-id="${tempId}"]`).remove();
                            alert('Failed to send message: ' + (response.error || 'Unknown error'));
                        }
                    }, 'json').fail(function(xhr) {
                        $(`[data-message-id="${tempId}"]`).remove();
                        console.error('Error sending message:', xhr.responseText);
                    });
                }
            });

            // Also send message on Enter key (but allow Shift+Enter for new lines)
            $('#message-input').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    $('#send-button').click();
                }
            });

            // Edit message
            $(document).on('click', '.edit-message', function() {
                const messageId = $(this).data('message-id');
                const messageDiv = $(this).closest('.message');
                const currentContent = messageDiv.find('.message-content').text();
                
                const newContent = prompt('Edit your message:', currentContent);
                if (newContent && newContent !== currentContent) {
                    if (containsSlang(newContent)) {
                        showSlangWarning();
                        return;
                    }
                    
                    // Optimistic UI update
                    messageDiv.find('.message-content').text(newContent);
                    if (!messageDiv.find('.message-edited').length) {
                        messageDiv.find('.message-meta').prepend('<span class="message-edited">edited</span>');
                    }
                    
                    $.post('edit_message.php', {
                        message_id: messageId,
                        content: newContent
                    }, function(response) {
                        if (!response.success) {
                            // Revert if failed
                            messageDiv.find('.message-content').text(currentContent);
                            messageDiv.find('.message-edited').remove();
                            alert('Failed to edit message: ' + (response.error || 'Unknown error'));
                        }
                    }, 'json').fail(function(xhr) {
                        messageDiv.find('.message-content').text(currentContent);
                        messageDiv.find('.message-edited').remove();
                        console.error('Error editing message:', xhr.responseText);
                    });
                }
            });

            // Delete/Unsend message
            $(document).on('click', '.unsend-message', function() {
                if (confirm('Are you sure you want to delete this message?')) {
                    const messageId = $(this).data('message-id');
                    const messageDiv = $(this).closest('.message');
                    
                    // Optimistic UI update
                    messageDiv.remove();
                    
                    $.post('unsend_message.php', {
                        message_id: messageId
                    }, function(response) {
                        if (!response.success) {
                            // TODO: Re-add message if failed
                            alert('Failed to delete message: ' + (response.error || 'Unknown error'));
                        }
                    }, 'json').fail(function(xhr) {
                        console.error('Error deleting message:', xhr.responseText);
                    });
                }
            });

            // Periodically check for new messages and unread counts
            if (<?php echo $selected_user_id ? 'true' : 'false'; ?>) {
                setInterval(function() {
                    const lastMessageId = $('.message').last().data('message-id') || 0;
                    
                    // Check for new messages
                    $.get('get_new_messages.php', {
                        conversation_id: <?php echo $conversation['id'] ?? 0; ?>,
                        last_message_id: lastMessageId
                    }, function(response) {
                        if (response.messages && response.messages.length > 0) {
                            response.messages.forEach(message => {
                                const isSent = message.sender_id == <?php echo $current_user_id; ?>;
                                const messageClass = isSent ? 'message-sent' : 'message-received';
                                
                                const messageHtml = `
                                    <div class="message ${messageClass}" data-message-id="${message.id}">
                                        ${!isSent ? '' : `
                                        <div class="message-actions">
                                            <div class="message-action edit-message" data-message-id="${message.id}">
                                                <i class="fas fa-edit"></i> Edit
                                            </div>
                                            <div class="message-action delete unsend-message" data-message-id="${message.id}">
                                                <i class="fas fa-trash"></i> Delete
                                            </div>
                                        </div>
                                        `}
                                        <div class="message-content">${message.content}</div>
                                        <div class="message-meta">
                                            ${message.edited ? '<span class="message-edited">edited</span>' : ''}
                                            <span class="message-time">
                                                ${new Date(message.sent_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                            </span>
                                        </div>
                                    </div>
                                `;
                                
                                $('#messages-container').append(messageHtml);
                            });
                            
                            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
                        }
                    }, 'json').fail(function(xhr) {
                        console.error('Error checking new messages:', xhr.responseText);
                    });

                    // Check for unread counts
                    $.get('get_unread_counts.php', function(response) {
                        if (response.unread_counts) {
                            // Update the UI for each conversation
                            Object.entries(response.unread_counts).forEach(([userId, count]) => {
                                const contactItem = $(`.contact-item[href*="user_id=${userId}"]`);
                                const badge = contactItem.find('.unread-badge');
                                
                                if (count > 0) {
                                    contactItem.addClass('unread');
                                    if (badge.length) {
                                        badge.text(count);
                                    } else {
                                        contactItem.append(`<div class="unread-badge">${count}</div>`);
                                    }
                                } else {
                                    contactItem.removeClass('unread');
                                    badge.remove();
                                }
                            });
                        }
                    }, 'json').fail(function(xhr) {
                        console.error('Error checking unread counts:', xhr.responseText);
                    });
                }, 2000); // Check every 2 seconds
            }

            // Update online status when leaving
            $(window).on('beforeunload', function() {
                $.post('update_status.php', { is_online: false });
            });
        });
    </script>
</body>
</html>