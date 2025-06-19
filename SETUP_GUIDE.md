# PawConnect Setup Guide

## Prerequisites
1. XAMPP must be installed and running
2. Start Apache and MySQL services in XAMPP Control Panel

## Step 1: Start XAMPP Services
1. Open XAMPP Control Panel
2. Start Apache service
3. Start MySQL service

## Step 2: Access the Setup
1. Open your web browser
2. Go to: `http://localhost/pawconnect/src/setup_database.php`
3. This will create the database and all required tables

## Step 3: Test the Application
1. Go to: `http://localhost/pawconnect/public/index.html`
2. Register a new account
3. Login with your credentials

## Features Available:
- ✅ User Registration & Login
- ✅ Session Management
- ✅ User Authentication
- ✅ Admin Panel (for admin users)
- ✅ Database Configuration
- ✅ Error Handling
- ✅ Responsive Frontend

## File Structure:
```
pawconnect/
├── config/
│   └── database.php          # Database configuration
├── src/
│   ├── login.php             # Login backend
│   ├── register.php          # Registration backend
│   ├── logout.php            # Logout functionality
│   ├── check_auth.php        # Authentication check
│   ├── get_messages.php      # Error/success messages
│   ├── setup_database.php    # Database setup
│   └── utils/
│       └── auth.php          # Authentication utilities
├── public/
│   ├── index.html            # Landing page
│   ├── login.html            # Login page
│   ├── register.html         # Registration page
│   ├── home.html             # Main dashboard
│   └── assets/               # Images and static files
└── database/
    └── migrations/
        └── 001_unified_core.sql  # Database schema
```

## Default Admin Account
After running the setup, you can create an admin account by:
1. Register normally
2. Go to phpMyAdmin (http://localhost/phpmyadmin)
3. Select pawconnect_db database
4. Go to users table
5. Edit your user record and change role from 'user' to 'admin'

## Troubleshooting:
- If you get database connection errors, make sure MySQL is running in XAMPP
- If you get file not found errors, make sure you're accessing from the correct URL
- If sessions don't work, make sure your PHP session configuration is correct

## URLs to Access:
- Landing Page: http://localhost/pawconnect/public/index.html
- Login: http://localhost/pawconnect/public/login.html
- Register: http://localhost/pawconnect/public/register.html
- Database Setup: http://localhost/pawconnect/src/setup_database.php
