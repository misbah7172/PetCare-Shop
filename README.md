# PawConnect - Pet Adoption Platform

## 🐾 Overview
PawConnect is a comprehensive pet adoption and management platform that allows users to add their pets, mark them for adoption, browse available pets, and connect with other pet owners.

## ✨ Features

### 🏠 Dashboard
- Real-time statistics (Total pets, adoptions, users, requests)
- Recent activity feed
- Quick action buttons
- Clean, modern interface

### 🐕 Pet Corner
- Add new pets with photos
- Mark pets for adoption
- Manage your pet collection
- View pet details and status

### 💕 Adoption Feed
- Browse all available pets for adoption
- Filter by type, age, gender
- Search functionality
- Contact adoption owners

### 🔧 Technical Features
- Simple, direct database access (no complex APIs)
- Responsive design
- Real-time data updates
- Clean codebase
- Easy to maintain

## 🚀 Getting Started

### Prerequisites
- XAMPP or similar local server
- PHP 7.4 or higher
- MySQL database

### Installation
1. Copy the project to your `xampp/htdocs/` directory
2. Start Apache and MySQL in XAMPP
3. Visit `http://localhost/pawconnect/public/`
4. The system will automatically redirect to the dashboard

### Database Setup
The system will automatically create the necessary database tables when first accessed. The main tables include:
- `users` - User accounts
- `pets` - Pet information
- `adoption_posts` - Adoption listings
- `adoption_requests` - Adoption inquiries

## 📁 Project Structure

```
pawconnect/
├── public/                 # Main application files
│   ├── index.html         # Entry point (redirects to dashboard)
│   ├── dashboard.html     # Main dashboard
│   ├── pet_corner.html    # Pet management
│   ├── pet_adoption_feed.html # Adoption browsing
│   ├── add_pet.php        # Pet addition backend
│   ├── get_pets.php       # Retrieve user pets
│   ├── get_adoption_posts.php # Retrieve adoption posts
│   ├── get_stats.php      # Dashboard statistics
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   └── assets/            # Images and media
├── config/
│   └── database.php       # Database configuration
└── src/                   # Backend utilities (mostly legacy)
```

## 🔄 How It Works

### Adding a Pet
1. Go to Pet Corner
2. Fill out the pet form
3. Optionally mark for adoption
4. Submit - pet is added to database and (if marked) posted for adoption

### Browsing Adoptions
1. Go to Adoption Feed
2. Browse available pets
3. Use filters to narrow search
4. Contact owners for adoption

### Dashboard
- Shows real-time statistics
- Displays recent activity
- Provides quick navigation

## 🛠️ Technical Details

### Backend (PHP)
- **add_pet.php**: Handles pet addition and adoption posting
- **get_pets.php**: Returns user's pets as JSON
- **get_adoption_posts.php**: Returns adoption posts as JSON
- **get_stats.php**: Returns dashboard statistics as JSON

### Frontend (JavaScript)
- **pet-corner.js**: Handles pet form submission and display
- **adoption-feed.js**: Manages adoption post loading and filtering
- **dashboard.js**: Loads and displays statistics and recent activity

### Database Schema
- Direct SQL queries for simplicity
- No complex ORM or API layers
- Clean, normalized structure

## 🔒 Security
- Session management for user authentication
- Input validation and sanitization
- Error handling and logging
- Safe file upload handling

## 📱 Responsive Design
- Works on desktop, tablet, and mobile
- Clean, modern UI with gradients and shadows
- Font Awesome icons
- Smooth animations and transitions

## 🎯 Key URLs
- **Dashboard**: `/public/dashboard.html`
- **Pet Corner**: `/public/pet_corner.html`
- **Adoption Feed**: `/public/pet_adoption_feed.html`
- **API Endpoints**: `/public/get_*.php`

## 🔧 Maintenance
The system is designed for easy maintenance:
- Simple PHP files with clear purpose
- Direct database queries
- Minimal dependencies
- Clean, commented code

## 📞 Support
For issues or questions about the PawConnect platform, check the browser console for error messages and ensure your database connection is properly configured.
