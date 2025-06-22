-- Create users table if it doesn't exist (for Pet Corner functionality)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create pets table without foreign key constraint initially
DROP TABLE IF EXISTS pet_photos;
DROP TABLE IF EXISTS pet_activities;
DROP TABLE IF EXISTS pets;

CREATE TABLE pets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('dog', 'cat', 'bird', 'fish', 'rabbit', 'other') NOT NULL,
    breed VARCHAR(100),
    age VARCHAR(50),
    gender ENUM('male', 'female'),
    weight VARCHAR(50),
    description TEXT,
    photo_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create pet_activities table for tracking pet activities
CREATE TABLE pet_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pet_id INT NOT NULL,
    user_id INT NOT NULL,
    activity_type ENUM('feeding', 'exercise', 'vet_visit', 'grooming', 'photo_upload', 'other') NOT NULL,
    description TEXT,
    activity_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create pet_photos table for managing pet photo gallery
CREATE TABLE pet_photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pet_id INT NOT NULL,
    user_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    caption TEXT,
    is_favorite BOOLEAN DEFAULT FALSE,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a default user for testing if none exists
INSERT IGNORE INTO users (id, name, username, email, password, role) 
VALUES (1, 'Pet Corner User', 'petuser', 'pet@example.com', '$2y$10$YourHashedPasswordHere', 'user');
