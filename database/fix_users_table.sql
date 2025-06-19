-- Fix PawConnect Database Structure
-- Run this in phpMyAdmin to add missing columns to users table

USE pawconnect_db;

-- Add missing columns to users table
ALTER TABLE users 
ADD COLUMN first_name VARCHAR(50) AFTER password,
ADD COLUMN last_name VARCHAR(50) AFTER first_name,
ADD COLUMN phone VARCHAR(20) AFTER last_name,
ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active' AFTER role,
ADD COLUMN last_login TIMESTAMP NULL AFTER updated_at;

-- Verify the structure
SELECT 'Users table structure updated successfully' as Status;
DESCRIBE users;
