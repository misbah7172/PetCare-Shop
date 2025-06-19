-- Admin Panel Database Setup
-- This file creates the necessary tables for the admin panel functionality

-- Users table (if not exists)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('user', 'admin', 'veterinarian') DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    profile_picture VARCHAR(255),
    subscription_status ENUM('none', 'active', 'expired') DEFAULT 'none',
    premium BOOLEAN DEFAULT FALSE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Pets table
CREATE TABLE IF NOT EXISTS pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    species VARCHAR(50) NOT NULL,
    breed VARCHAR(100),
    age INT,
    gender ENUM('male', 'female', 'unknown') DEFAULT 'unknown',
    size ENUM('small', 'medium', 'large', 'extra_large') DEFAULT 'medium',
    color VARCHAR(50),
    description TEXT,
    health_status TEXT,
    vaccination_status ENUM('up_to_date', 'partial', 'unknown') DEFAULT 'unknown',
    spayed_neutered BOOLEAN DEFAULT FALSE,
    good_with_kids BOOLEAN DEFAULT FALSE,
    good_with_pets BOOLEAN DEFAULT FALSE,
    energy_level ENUM('low', 'medium', 'high') DEFAULT 'medium',
    adoption_fee DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('available', 'pending', 'adopted', 'not_available') DEFAULT 'available',
    shelter_id INT,
    location VARCHAR(255),
    images JSON,
    video_url VARCHAR(255),
    special_needs TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Adoptions table
CREATE TABLE IF NOT EXISTS adoptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pet_id INT NOT NULL,
    pet_name VARCHAR(100),
    applicant_name VARCHAR(100),
    applicant_email VARCHAR(100),
    applicant_phone VARCHAR(20),
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approval_date TIMESTAMP NULL,
    notes TEXT,
    home_check_status ENUM('pending', 'passed', 'failed') DEFAULT 'pending',
    reference_check_status ENUM('pending', 'passed', 'failed') DEFAULT 'pending',
    adoption_fee_paid BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
);

-- Veterinarians table
CREATE TABLE IF NOT EXISTS veterinarians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    clinic_name VARCHAR(255) NOT NULL,
    license_number VARCHAR(100) UNIQUE NOT NULL,
    specialization VARCHAR(255),
    experience_years INT DEFAULT 0,
    education TEXT,
    phone VARCHAR(20),
    emergency_phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    services JSON,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    emergency_fee DECIMAL(10,2) DEFAULT 0.00,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    availability JSON,
    is_emergency_available BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'pending_verification') DEFAULT 'pending_verification',
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Shop Products table
CREATE TABLE IF NOT EXISTS shop_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('food', 'toys', 'accessories', 'health', 'grooming', 'other') NOT NULL,
    subcategory VARCHAR(100),
    brand VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NULL,
    stock_quantity INT DEFAULT 0,
    sku VARCHAR(100) UNIQUE,
    images JSON,
    specifications JSON,
    weight DECIMAL(8,2),
    dimensions VARCHAR(100),
    age_group ENUM('puppy', 'adult', 'senior', 'all') DEFAULT 'all',
    pet_size ENUM('small', 'medium', 'large', 'extra_large', 'all') DEFAULT 'all',
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    featured BOOLEAN DEFAULT FALSE,
    tags JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Support Tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('general', 'adoption', 'veterinary', 'shop', 'technical', 'billing') DEFAULT 'general',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'waiting_customer', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT NULL,
    attachments JSON,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    last_response_by ENUM('customer', 'support') DEFAULT 'customer',
    last_response_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Create default admin user if not exists
INSERT IGNORE INTO users (username, email, password_hash, first_name, last_name, role, status, email_verified) 
VALUES (
    'admin', 
    'admin@pawconnect.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'password'
    'Admin', 
    'User', 
    'admin', 
    'active', 
    TRUE
);

-- Create sample data for testing (optional)
INSERT IGNORE INTO pets (name, species, breed, age, gender, description, status) VALUES
('Buddy', 'Dog', 'Golden Retriever', 3, 'male', 'Friendly and energetic dog looking for a loving home.', 'available'),
('Whiskers', 'Cat', 'Persian', 2, 'female', 'Calm and affectionate cat, great with children.', 'available'),
('Max', 'Dog', 'German Shepherd', 5, 'male', 'Well-trained guard dog, needs experienced owner.', 'available');

INSERT IGNORE INTO shop_products (name, description, category, price, stock_quantity) VALUES
('Premium Dog Food', 'High-quality nutrition for adult dogs', 'food', 45.99, 100),
('Cat Toy Mouse', 'Interactive toy mouse for cats', 'toys', 12.99, 50),
('Dog Leash', 'Durable leather dog leash', 'accessories', 24.99, 30);
