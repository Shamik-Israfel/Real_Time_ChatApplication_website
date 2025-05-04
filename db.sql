-- Create the schema if it doesn't exist
CREATE SCHEMA IF NOT EXISTS user_management;

-- Create roles table
CREATE TABLE IF NOT EXISTS user_management.roles (
    id SERIAL PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO user_management.roles (role_name) VALUES 
    ('Admin'),
    ('User'),
    ('Guest')
ON CONFLICT (role_name) DO NOTHING;

-- Create users table
CREATE TABLE IF NOT EXISTS user_management.users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INTEGER REFERENCES user_management.roles(id) DEFAULT 2, -- Default to 'User' role
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP WITH TIME ZONE
);

-- Create user_status table
CREATE TABLE IF NOT EXISTS user_management.user_status (
    user_id INTEGER PRIMARY KEY REFERENCES user_management.users(id) ON DELETE CASCADE,
    is_online BOOLEAN NOT NULL DEFAULT FALSE,
    last_seen TIMESTAMP WITH TIME ZONE,
    status_message VARCHAR(100)
);

-- Create conversations table
CREATE TABLE IF NOT EXISTS user_management.conversations (
    id SERIAL PRIMARY KEY,
    user1_id INTEGER NOT NULL REFERENCES user_management.users(id) ON DELETE CASCADE,
    user2_id INTEGER NOT NULL REFERENCES user_management.users(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_message_at TIMESTAMP WITH TIME ZONE,
    CONSTRAINT unique_conversation UNIQUE (user1_id, user2_id),
    CONSTRAINT ordered_users CHECK (user1_id < user2_id)
);

-- Create messages table
CREATE TABLE IF NOT EXISTS user_management.messages (
    id SERIAL PRIMARY KEY,
    conversation_id INTEGER NOT NULL REFERENCES user_management.conversations(id) ON DELETE CASCADE,
    sender_id INTEGER NOT NULL REFERENCES user_management.users(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    sent_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    is_unsent BOOLEAN NOT NULL DEFAULT FALSE,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    edited BOOLEAN NOT NULL DEFAULT FALSE,
    edited_at TIMESTAMP WITH TIME ZONE
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_messages_conversation ON user_management.messages(conversation_id);
CREATE INDEX IF NOT EXISTS idx_messages_sender ON user_management.messages(sender_id);
CREATE INDEX IF NOT EXISTS idx_messages_sent_at ON user_management.messages(sent_at);
CREATE INDEX IF NOT EXISTS idx_conversations_user1 ON user_management.conversations(user1_id);
CREATE INDEX IF NOT EXISTS idx_conversations_user2 ON user_management.conversations(user2_id);
CREATE INDEX IF NOT EXISTS idx_conversations_last_message ON user_management.conversations(last_message_at);

-- Create a function to update last_message_at in conversations
CREATE OR REPLACE FUNCTION user_management.update_conversation_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE user_management.conversations
    SET last_message_at = NEW.sent_at
    WHERE id = NEW.conversation_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger to update conversation timestamp when new message is inserted
DROP TRIGGER IF EXISTS trig_update_conversation_timestamp ON user_management.messages;
CREATE TRIGGER trig_update_conversation_timestamp
AFTER INSERT ON user_management.messages
FOR EACH ROW EXECUTE FUNCTION user_management.update_conversation_timestamp();

-- Create a view for easier access to conversations with user info
CREATE OR REPLACE VIEW user_management.conversation_view AS
SELECT 
    c.id,
    c.user1_id,
    u1.username AS user1_name,
    c.user2_id,
    u2.username AS user2_name,
    c.last_message_at,
    (SELECT COUNT(*) FROM user_management.messages m 
     WHERE m.conversation_id = c.id AND m.sender_id != u.id AND m.is_read = FALSE) AS unread_count
FROM 
    user_management.conversations c
JOIN 
    user_management.users u1 ON c.user1_id = u1.id
JOIN 
    user_management.users u2 ON c.user2_id = u2.id
JOIN 
    user_management.users u ON u.id IN (c.user1_id, c.user2_id);