-- PawConnect Simplified Database Schema
-- Only essential tables and columns for core functionality
USE pawconnect_db;

-- Check current structure and create clean version
CREATE TABLE IF NOT EXISTS `users_clean` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(20),
    `address` TEXT,
    `city` VARCHAR(50),
    `state` VARCHAR(50),
    `zip_code` VARCHAR(20),
    `country` VARCHAR(50) DEFAULT 'USA',
    `role` ENUM('user', 'admin', 'veterinarian') DEFAULT 'user',
    `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Simplified pets table
CREATE TABLE IF NOT EXISTS `pets_clean` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uploader_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `species` ENUM('dog', 'cat', 'bird', 'rabbit', 'other') NOT NULL,
    `breed` VARCHAR(100),
    `age_years` INT DEFAULT 0,
    `age_months` INT DEFAULT 0,
    `gender` ENUM('male', 'female', 'unknown') DEFAULT 'unknown',
    `size` ENUM('small', 'medium', 'large') DEFAULT 'medium',
    `color` VARCHAR(100),
    `description` TEXT,
    `health_status` TEXT,
    `adoption_status` ENUM('available', 'pending', 'adopted') DEFAULT 'available',
    `adoption_fee` DECIMAL(10,2) DEFAULT 0.00,
    `good_with_kids` BOOLEAN DEFAULT TRUE,
    `good_with_pets` BOOLEAN DEFAULT TRUE,
    `location` VARCHAR(255),
    `images` LONGTEXT, -- JSON array of image URLs
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`uploader_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_pets_uploader` (`uploader_id`),
    INDEX `idx_pets_status` (`adoption_status`),
    INDEX `idx_pets_species` (`species`)
);

-- Simplified products table  
CREATE TABLE IF NOT EXISTS `products_clean` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT NOT NULL,
    `category_id` INT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10,2) NOT NULL,
    `stock_quantity` INT DEFAULT 0,
    `images` LONGTEXT, -- JSON array of image URLs
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_products_owner` (`owner_id`),
    INDEX `idx_products_category` (`category_id`),
    INDEX `idx_products_status` (`status`)
);

-- Product categories (simplified)
CREATE TABLE IF NOT EXISTS `product_categories_clean` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Adoption applications (simplified)
CREATE TABLE IF NOT EXISTS `adoption_applications_clean` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pet_id` INT NOT NULL,
    `applicant_id` INT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `application_notes` TEXT,
    `admin_notes` TEXT,
    `conversation_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`pet_id`) REFERENCES `pets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`applicant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_adoption_pet` (`pet_id`),
    INDEX `idx_adoption_applicant` (`applicant_id`)
);

-- Community posts (simplified)
CREATE TABLE IF NOT EXISTS `community_posts_clean` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255),
    `content` TEXT NOT NULL,
    `images` LONGTEXT, -- JSON array of image URLs
    `likes_count` INT DEFAULT 0,
    `comments_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_community_user` (`user_id`),
    INDEX `idx_community_created` (`created_at`)
);

-- Community comments (simplified)
CREATE TABLE IF NOT EXISTS `community_comments_clean` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES `community_posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_comments_post` (`post_id`),
    INDEX `idx_comments_user` (`user_id`)
);

-- Community likes (simplified)
CREATE TABLE IF NOT EXISTS `community_likes_clean` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES `community_posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_like` (`post_id`, `user_id`),
    INDEX `idx_likes_post` (`post_id`),
    INDEX `idx_likes_user` (`user_id`)
);

-- Notifications (already clean)
-- Chat conversations (already clean)  
-- Chat messages (already clean)

-- Veterinarians (simplified)
CREATE TABLE IF NOT EXISTS `veterinarians_clean` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `license_number` VARCHAR(100),
    `clinic_name` VARCHAR(255),
    `clinic_address` TEXT,
    `clinic_phone` VARCHAR(20),
    `specialties` TEXT,
    `consultation_fee` DECIMAL(10,2),
    `rating` DECIMAL(3,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_vet_user` (`user_id`)
);

-- Vet appointments (simplified)
CREATE TABLE IF NOT EXISTS `vet_appointments_clean` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pet_id` INT,
    `pet_owner_id` INT NOT NULL,
    `veterinarian_id` INT NOT NULL,
    `appointment_date` DATETIME NOT NULL,
    `service_type` VARCHAR(100),
    `status` ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    `notes` TEXT,
    `cost` DECIMAL(10,2),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`pet_id`) REFERENCES `pets`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`pet_owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`veterinarian_id`) REFERENCES `veterinarians`(`id`) ON DELETE CASCADE,
    INDEX `idx_appointment_date` (`appointment_date`),
    INDEX `idx_appointment_owner` (`pet_owner_id`),
    INDEX `idx_appointment_vet` (`veterinarian_id`)
);

-- Insert default categories
INSERT IGNORE INTO `product_categories_clean` (`id`, `name`, `description`) VALUES
(1, 'Food & Treats', 'Pet food, treats, and dietary supplements'),
(2, 'Toys & Accessories', 'Toys, collars, leashes, and accessories'),
(3, 'Health & Care', 'Medicines, grooming supplies, and health products'),
(4, 'Housing & Bedding', 'Pet beds, crates, and housing supplies'),
(5, 'Other', 'Miscellaneous pet-related items');
