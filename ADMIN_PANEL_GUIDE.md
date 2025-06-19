# PawConnect Admin Panel

A comprehensive admin panel for managing all aspects of the PawConnect pet adoption and care platform.

## Overview

The Admin Panel provides administrators with complete control over the platform, including user management, pet listings, adoptions, veterinary services, products, and support tickets.

## Features

### 🏠 Dashboard Overview
- Real-time statistics and metrics
- Recent activity monitoring
- Quick access to all management sections
- System health indicators

### 👥 User Management
- View, create, edit, and delete users
- Role-based access control (user, admin, veterinarian)
- User status management (active, inactive, banned)
- Search and filter capabilities
- Bulk actions for user management

### 🐾 Pet Management
- Complete pet profile management
- Adoption status tracking
- Pet health records
- Image and media management
- Advanced search and filtering

### 🏠 Adoption Management
- Application review and approval
- Adoption process tracking
- Communication with adopters
- Background check management
- Adoption fee tracking

### 🏥 Veterinary Management
- Vet clinic registration and verification
- Service management
- Appointment oversight
- Rating and review monitoring

### 🛍️ Product Management
- Shop inventory management
- Category and pricing control
- Stock level monitoring
- Product analytics

### 🎫 Support Ticket System
- Customer support management
- Ticket assignment and tracking
- Priority and status management
- Response tracking

### 📊 Reports & Analytics
- User engagement metrics
- Revenue tracking
- Adoption success rates
- Popular products analysis

## Access Control

### Admin Role
- Users with `role = 'admin'` in the database have full access
- Admin status is verified through session management
- All admin actions are logged and tracked

### Security Features
- Session-based authentication
- CSRF protection
- Role-based access control
- API endpoint protection
- Input validation and sanitization

## File Structure

```
├── public/
│   ├── admin_panel.html         # Main admin interface
│   ├── admin_test.html          # Testing page
│   └── js/
│       └── admin-panel.js       # Admin panel JavaScript
├── src/
│   ├── api/
│   │   └── admin.php           # Admin API endpoints
│   ├── session_manager.php     # Session and auth management
│   └── setup_admin.php         # Database setup script
└── database/
    └── admin_panel_setup.sql   # Database schema
```

## Setup Instructions

### 1. Database Setup
Run the admin setup script to create necessary tables:
```bash
php src/setup_admin.php
```

Or manually execute the SQL file:
```sql
source database/admin_panel_setup.sql
```

### 2. Create Admin User
The setup script creates a default admin user:
- **Username:** admin
- **Email:** admin@pawconnect.com
- **Password:** password
- **Role:** admin

⚠️ **Important:** Change the default password after first login!

### 3. Access the Admin Panel

1. Navigate to the dashboard: `/public/dashboard.html`
2. Login with admin credentials
3. Click the "Admin Panel" button (visible only to admins)
4. Start managing your platform!

## API Endpoints

### Statistics
- `GET /src/api/admin.php?action=stats`
- Returns platform statistics and metrics

### User Management
- `GET /src/api/admin.php?action=users` - List users
- `POST /src/api/admin.php?action=users` - Create user
- `PUT /src/api/admin.php?action=users` - Update user
- `DELETE /src/api/admin.php?action=users` - Delete user

### Pet Management
- `GET /src/api/admin.php?action=pets` - List pets
- `POST /src/api/admin.php?action=pets` - Create pet
- `PUT /src/api/admin.php?action=pets` - Update pet
- `DELETE /src/api/admin.php?action=pets` - Delete pet

### Additional Endpoints
- Adoptions: `/src/api/admin.php?action=adoptions`
- Veterinarians: `/src/api/admin.php?action=vets`
- Products: `/src/api/admin.php?action=products`
- Support: `/src/api/admin.php?action=support`
- Reports: `/src/api/admin.php?action=reports`

## Database Tables

### Core Tables
- `users` - User accounts and profiles
- `pets` - Pet listings and information
- `adoptions` - Adoption applications and tracking
- `veterinarians` - Vet clinic information
- `shop_products` - Product inventory
- `support_tickets` - Customer support tickets

### Key Columns
- All tables include `created_at` and `updated_at` timestamps
- Status tracking for applications and processes
- Role-based access through user roles
- JSON fields for flexible data storage

## Customization

### Adding New Features
1. Add API endpoints in `src/api/admin.php`
2. Update the admin panel UI in `admin_panel.html`
3. Add JavaScript functions in `js/admin-panel.js`
4. Update database schema if needed

### Styling
- Global styles in `css/global.css`
- Admin-specific styles in `admin_panel.html`
- Responsive design for mobile and desktop

## Security Considerations

### Authentication
- Session-based authentication required
- Admin role verification on all endpoints
- Automatic session timeout

### Data Protection
- Input validation and sanitization
- Prepared statements for SQL queries
- CSRF token protection
- XSS prevention

### Access Control
- Role-based permissions
- API endpoint protection
- Admin-only functionality isolation

## Troubleshooting

### Common Issues

**Admin Panel not visible:**
- Check user role in database: `SELECT role FROM users WHERE id = ?`
- Verify session: `console.log(sessionManager.user.role)`
- Clear browser cache and login again

**API Access Denied:**
- Ensure logged in as admin
- Check session manager endpoint: `/src/session_manager.php?action=check_admin`
- Verify database connection

**Database Errors:**
- Run setup script: `php src/setup_admin.php`
- Check database configuration in `config/database.php`
- Verify table existence and structure

### Testing
Use the admin test page at `/public/admin_test.html` to:
- Verify admin login
- Test API endpoints
- Check session status
- Debug issues

## Support

For technical support or feature requests:
1. Check the troubleshooting section
2. Review error logs
3. Test with the admin test page
4. Contact the development team

## Version History

- **v1.0** - Initial admin panel implementation
- Full CRUD operations for all entities
- Role-based access control
- Dashboard with statistics
- Responsive design

---

*PawConnect Admin Panel - Empowering administrators to manage pet adoption and care services effectively.*
