-- Enhanced schema additions for chat and improved adoption/shop features
USE pawconnect_db;

-- Chat system for adoption and shop communications
CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('adoption', 'shop', 'general') NOT NULL,
    related_id INT, -- pet_id for adoption, product_id for shop
    participant_1_id INT NOT NULL, -- buyer/adopter
    participant_2_id INT NOT NULL, -- seller/pet owner
    status ENUM('active', 'closed', 'archived') DEFAULT 'active',
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (participant_1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_2_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_conversation (type, related_id, participant_1_id, participant_2_id)
);

-- Chat messages
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'image', 'file', 'system') DEFAULT 'text',
    attachment_url VARCHAR(500) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Enhanced adoption applications with chat integration
ALTER TABLE adoption_applications ADD COLUMN conversation_id INT NULL;
ALTER TABLE adoption_applications ADD CONSTRAINT fk_adoption_conversation 
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE SET NULL;

-- Enhanced notifications system
ALTER TABLE notifications ADD COLUMN related_type VARCHAR(50) NULL;
ALTER TABLE notifications ADD COLUMN related_id INT NULL;
ALTER TABLE notifications ADD COLUMN sender_id INT NULL;
ALTER TABLE notifications ADD CONSTRAINT fk_notification_sender 
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL;

-- Product ownership tracking (for user-uploaded shop items)
ALTER TABLE products ADD COLUMN owner_id INT NULL;
ALTER TABLE products ADD CONSTRAINT fk_product_owner 
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE;

-- Pet upload tracking (for user-uploaded pets for adoption)
ALTER TABLE pets ADD COLUMN uploader_id INT NULL;
ALTER TABLE pets ADD CONSTRAINT fk_pet_uploader 
    FOREIGN KEY (uploader_id) REFERENCES users(id) ON DELETE CASCADE;

-- Shop orders with chat integration
ALTER TABLE orders ADD COLUMN conversation_id INT NULL;
ALTER TABLE orders ADD CONSTRAINT fk_order_conversation 
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE SET NULL;

-- Enhanced community posts reactions
CREATE TABLE IF NOT EXISTS community_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type ENUM('like', 'love', 'laugh', 'sad', 'angry') DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_post_reaction (user_id, post_id)
);

-- Indexes for performance
CREATE INDEX idx_chat_conversations_participants ON chat_conversations(participant_1_id, participant_2_id);
CREATE INDEX idx_chat_messages_conversation ON chat_messages(conversation_id);
CREATE INDEX idx_chat_messages_read ON chat_messages(is_read);
CREATE INDEX idx_notifications_related ON notifications(related_type, related_id);
CREATE INDEX idx_products_owner ON products(owner_id);
CREATE INDEX idx_pets_uploader ON pets(uploader_id);
CREATE INDEX idx_community_reactions_post ON community_reactions(post_id);
