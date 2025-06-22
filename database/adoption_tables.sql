-- Create adoption and chat tables for PawConnect
-- This adds adoption functionality to the existing pets system

-- Create adoption_posts table
CREATE TABLE IF NOT EXISTS adoption_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pet_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    adoption_fee DECIMAL(10, 2) DEFAULT 0.00,
    location VARCHAR(255),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(255),
    status ENUM('available', 'pending', 'adopted') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pet_id (pet_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);

-- Create adoption_requests table
CREATE TABLE IF NOT EXISTS adoption_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    adoption_post_id INT NOT NULL,
    requester_user_id INT NOT NULL,
    owner_user_id INT NOT NULL,
    message TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_post_id (adoption_post_id),
    INDEX idx_requester (requester_user_id),
    INDEX idx_owner (owner_user_id)
);

-- Create chat_conversations table
CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    adoption_request_id INT NOT NULL,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_conversation (adoption_request_id),
    INDEX idx_users (user1_id, user2_id)
);

-- Create chat_messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation (conversation_id),
    INDEX idx_sender (sender_user_id),
    INDEX idx_created (created_at)
);

-- Add adoption status to pets table if not exists
ALTER TABLE pets ADD COLUMN IF NOT EXISTS is_for_adoption BOOLEAN DEFAULT FALSE;
ALTER TABLE pets ADD COLUMN IF NOT EXISTS adoption_post_id INT NULL;
