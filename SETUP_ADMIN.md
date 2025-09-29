# Admin Dashboard Setup Instructions

## Prerequisites
- XAMPP with Apache and MySQL running
- PHP 8.x
- Web browser

## Setup Steps

### 1. Database Setup
1. Start XAMPP and ensure Apache and MySQL are running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database named `restaurant_management`
4. Import the database schema:
   - Go to the `restaurant_management` database
   - Click on "Import" tab
   - Select file: `database/restaurant_schema.sql`
   - Click "Go" to import

### 2. Test Database Connection
1. Navigate to: `http://localhost/Restaurant-Management/admin/test-login.php`
2. This will verify the database connection and admin user setup
3. If successful, you should see "✅ Password verification: SUCCESS"

### 3. Access the Application
1. **Landing Page**: `http://localhost/Restaurant-Management/`
2. **Admin Login**: `http://localhost/Restaurant-Management/admin/login.php`
3. **Admin Dashboard**: `http://localhost/Restaurant-Management/admin/dashboard.php`

### 4. Admin Login Credentials
- **Email**: admin@restaurant.com
- **Password**: pass1234

## Application Flow
1. Visit the landing page (`index.php`)
2. Click "Admin Login" button
3. Enter the admin credentials
4. Successfully login and access the dashboard

## File Structure Created
```
Restaurant-Management/
├── admin/
│   ├── login.php          # Admin login page
│   ├── dashboard.php      # Admin dashboard (main admin page)
│   ├── logout.php         # Logout functionality
│   └── test-login.php     # Database connection test
├── assets/
│   ├── css/
│   │   └── admin.css      # Admin theme styles
│   └── js/
│       └── admin.js       # Admin JavaScript functionality
├── includes/
│   └── config.php         # Database configuration & helper functions
├── database/
│   ├── restaurant_schema.sql    # Complete database schema
│   └── generate_password.php    # Password hash generator
├── index.php             # Landing page (created by Arfan)
└── styles/               # Landing page styles (created by Arfan)
```

## Features Implemented
- ✅ Professional admin login with restaurant theme
- ✅ Secure password hashing and verification
- ✅ Session management and authentication
- ✅ Responsive admin dashboard with statistics
- ✅ Integration with existing landing page
- ✅ Database connection and error handling
- ✅ CSRF protection and input sanitization

## Next Steps (for future development)
- Menu management (CRUD operations)
- Order management system
- Customer management
- Reports and analytics
- Additional admin features

## Troubleshooting
- If you get database connection errors, check if MySQL is running in XAMPP
- Make sure the database name is exactly `restaurant_management`
- Verify the database schema is properly imported
- Check file permissions if needed