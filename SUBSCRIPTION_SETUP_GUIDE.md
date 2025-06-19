# PawConnect Subscription Validation System Setup Guide

## Overview
The subscription validation system automatically manages user subscription status, ensuring that expired subscriptions are properly handled and users are notified appropriately.

## Features
- **Automatic Expiration Detection**: Checks for expired subscriptions daily
- **User Badge Management**: Automatically changes user badges from premium to normal when subscriptions expire
- **Email Notifications**: Sends expiration and renewal reminder emails
- **Admin Dashboard**: Complete subscription management interface
- **Statistics & Analytics**: Real-time subscription statistics and reports

## Database Requirements

### Required Tables
Make sure your database has the following tables:

```sql
-- Users table (should already exist)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    subscription_badge ENUM('normal', 'bronze', 'silver', 'gold', 'platinum') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_type ENUM('bronze', 'silver', 'gold', 'platinum') NOT NULL,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System logs table
CREATE TABLE IF NOT EXISTS system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    action VARCHAR(100) NOT NULL,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Installation Steps

### 1. Database Configuration
Update the database configuration in `subscription_validator.php`:

```php
$config = [
    'host' => 'localhost',
    'dbname' => 'pawconnect_db',
    'username' => 'root',
    'password' => ''
];
```

### 2. Email Configuration (Optional)
To enable email notifications, uncomment the mail() functions in `subscription_validator.php` and configure your email server.

### 3. Cron Job Setup

#### For Windows (Task Scheduler):
1. Open Task Scheduler
2. Create a new Basic Task
3. Set trigger to Daily
4. Set action to start a program
5. Program: `C:\xampp\php\php.exe`
6. Arguments: `C:\xampp\htdocs\pawconnect\subscription_validator.php`
7. Set to run daily at 2:00 AM

#### For Linux/Mac (Cron):
Add this line to your crontab (`crontab -e`):
```bash
# Run subscription validation daily at 2:00 AM
0 2 * * * /usr/bin/php /path/to/pawconnect/subscription_validator.php
```

### 4. Manual Testing
Test the system manually by visiting:
- `http://localhost/pawconnect/subscription_validator.php?action=validate`
- `http://localhost/pawconnect/subscription_validator.php?action=stats`
- `http://localhost/pawconnect/subscription_validator.php?action=expiring`

## Admin Panel Usage

### Accessing the Admin Panel
1. Navigate to `http://localhost/pawconnect/admin_dashboard.html`
2. Click on "Manage Subscriptions" or use the navigation menu
3. Or directly access: `http://localhost/pawconnect/subscription_management.html`

### Available Actions

#### 1. Validate Expired Subscriptions
- Automatically finds and updates expired subscriptions
- Changes user badges from premium to normal
- Sends expiration notifications
- Logs all actions

#### 2. Send Renewal Reminders
- Sends reminders to users with subscriptions expiring within 3 days
- Creates in-app notifications
- Sends email reminders (if configured)

#### 3. View Statistics
- Real-time subscription statistics
- Plan distribution analysis
- Active vs expired subscription counts

#### 4. View Expiring Subscriptions
- Lists all subscriptions expiring within 7 days
- Shows days remaining for each subscription
- Helps with proactive renewal campaigns

## API Endpoints

### Validation Endpoints
- `GET subscription_validator.php?action=validate` - Validate expired subscriptions
- `GET subscription_validator.php?action=stats` - Get subscription statistics
- `GET subscription_validator.php?action=expiring&days=7` - Get expiring subscriptions
- `GET subscription_validator.php?action=reminders&days=3` - Send renewal reminders

### Response Format
All endpoints return JSON responses:
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {...}
}
```

## Subscription Lifecycle

### 1. Active Subscription
- User has premium badge
- Access to premium features
- Subscription status: "active"

### 2. Expiring Soon (7 days before)
- System sends renewal reminders
- User receives notifications
- Admin can see in expiring list

### 3. Expired Subscription
- System automatically detects expiration
- User badge changes to "normal"
- Subscription status: "expired"
- User receives expiration notification

### 4. Renewal
- User can renew through subscription plans page
- New subscription period starts
- User badge restored to premium level

## Monitoring and Maintenance

### Daily Tasks
- Check admin dashboard for new expired subscriptions
- Review renewal reminder statistics
- Monitor email delivery (if configured)

### Weekly Tasks
- Review subscription statistics
- Analyze renewal rates
- Check system logs for errors

### Monthly Tasks
- Review overall subscription performance
- Update subscription plans if needed
- Backup subscription data

## Troubleshooting

### Common Issues

#### 1. Database Connection Errors
- Check database configuration in `subscription_validator.php`
- Ensure MySQL service is running
- Verify database credentials

#### 2. Cron Job Not Running
- Check cron job syntax
- Verify file paths are correct
- Check system logs for errors

#### 3. Email Notifications Not Sending
- Ensure email server is configured
- Check PHP mail() function is enabled
- Verify email addresses are valid

#### 4. Admin Panel Not Loading
- Check file permissions
- Verify all required files exist
- Check browser console for JavaScript errors

### Debug Mode
Enable debug mode by adding `?debug=1` to any API endpoint:
```
http://localhost/pawconnect/subscription_validator.php?action=validate&debug=1
```

## Security Considerations

### File Permissions
- Ensure `subscription_validator.php` is not publicly accessible
- Set appropriate file permissions (644 for files, 755 for directories)

### Database Security
- Use strong database passwords
- Limit database user permissions
- Regularly backup subscription data

### API Security
- Consider adding authentication to admin endpoints
- Implement rate limiting for API calls
- Log all admin actions for audit purposes

## Performance Optimization

### Database Indexing
Add indexes to improve query performance:
```sql
CREATE INDEX idx_subscriptions_status_date ON subscriptions(status, end_date);
CREATE INDEX idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
```

### Caching
Consider implementing caching for frequently accessed data:
- Subscription statistics
- User badge information
- Plan distribution data

## Support

For technical support or questions about the subscription validation system:
1. Check the system logs for error messages
2. Review the troubleshooting section above
3. Test individual API endpoints manually
4. Verify database table structure and data

## Future Enhancements

Potential improvements for the subscription system:
- Automatic renewal processing
- Payment gateway integration
- Advanced analytics and reporting
- Multi-language support
- Mobile app integration
- Webhook notifications
- Subscription tier upgrades/downgrades 