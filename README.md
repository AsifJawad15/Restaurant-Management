# Restaurant Management System (RMS)

A comprehensive restaurant management system built with PHP, CSS, JavaScript, and MySQL database using XAMPP, following Information System Design principles and Agile Scrum methodology.

## Project Overview

This project implements a **Restaurant Management System** with focus on **Admin Features** including owner login and dynamic menu management using PHP and MySQL. The system follows Agile Scrum methodology with 15-day sprints.

### **Current Developer Focus**
- **ASIF** - Admin Features Developer (Owner Login, Menu Management CRUD)

### **Project Scope & Methodology**
- **Scope**: Dynamic RMS with owner login, menu item management (add/update/delete), using XAMPP for local development
- **Methodology**: Agile Scrum with 15-day sprints, Kanban for task management, GitHub for version control
- **Demo Credentials**: aj@gmail.com / 1234

## Project Structure
```
Restaurant-Management/
├── assets/
│   ├── css/          # Stylesheets (Bootstrap + Custom)
│   ├── js/           # JavaScript files (Main + Admin)
│   └── images/       # Images and media files
├── includes/         # PHP include files (header, footer, config)
├── pages/            # Main application pages
├── admin/            # Admin panel files (Login, Dashboard, Menu CRUD)
├── database/         # Database scripts and configuration
├── tests/            # Unit testing files
└── README.md
```

## Functional Requirements

### **Admin Features (Current Focus)**
- [x] **Project Setup**: GitHub repository, branches, initial structure
- [ ] **Owner Login**: Authentication system with demo credentials (aj@gmail.com/1234)
- [ ] **Menu Management CRUD**: 
  - Create new food items with name, description, price, category
  - Read/Display all menu items in admin dashboard
  - Update existing food items and prices
  - Delete menu items from the system
- [ ] **Admin Dashboard**: Interface for managing all admin functions

### **System Requirements**
- **Database**: MySQL tables for Users, MenuItems, Categories
- **Authentication**: PHP session-based login system
- **Validation**: Input validation and sanitization
- **Responsive Design**: Bootstrap framework for mobile compatibility

### **Non-Functional Requirements**
- **Security**: SQL injection prevention, XSS protection
- **Performance**: Optimized database queries
- **Maintainability**: Modular code structure, clear documentation
- **Testing**: Unit tests for all admin functions

## Setup Instructions

### Prerequisites
- XAMPP with Apache and MySQL running
- Git for version control
- Text editor or IDE (VS Code recommended)
- PHP 8.x and MySQL 8.x

### Installation
1. Clone the repository to your XAMPP htdocs directory
2. Start XAMPP and ensure Apache and MySQL are running
3. Create database `restaurant_management` in phpMyAdmin
4. Import the database from `database/restaurant_db.sql`
5. Configure database connection in `includes/config.php`
6. Access the application at `http://localhost/Restaurant-Management/`
7. Admin login: `http://localhost/Restaurant-Management/admin/login.php`

## Development Workflow & Timeline (15-Day Sprint)

### **Days 1-2: Planning & Requirements** ✅
- [x] Define admin features and user stories
- [x] Prepare GitHub repository and branches
- [x] Initial project structure setup

### **Days 3-5: System Design**
- [ ] Develop Data Flow Diagrams (DFD Level 0, 1)
- [ ] Design database schema (ERD)
- [ ] Create UI wireframes for admin dashboard
- [ ] Define design patterns (MVC, Factory, Singleton)

### **Days 6-11: Implementation (ASIF - Admin Features)**
- [ ] Implement owner login system with PHP sessions
- [ ] Create admin dashboard interface
- [ ] Build menu management CRUD operations
- [ ] Add input validation and security measures

### **Days 12-13: Testing**
- [ ] Unit testing for login functionality
- [ ] Unit testing for menu CRUD operations
- [ ] Integration testing
- [ ] Security testing (SQL injection, XSS)

### **Day 14: Deployment**
- [ ] Final bug fixes and optimization
- [ ] Documentation completion
- [ ] GitHub repository finalization

### **Day 15: Review**
- [ ] Project demonstration
- [ ] Code review and retrospective

## Branch Strategy & Version Control

### **Current Branches**
- `main` - Production ready code (stable version)
- `asif-admin` - ASIF's admin features development
- `develop` - Integration branch for testing features

### **Git Workflow**
1. Work on `asif-admin` branch for all admin features
2. Daily commits with clear messages
3. Regular merges to `develop` for testing
4. Final merge to `main` after testing completion
5. Follow conventional commit messages

### **Collaboration Guidelines**
1. Work exclusively on `asif-admin` branch
2. Commit frequently with descriptive messages
3. Test thoroughly before merging
4. Follow PHP coding standards (PSR-12)
5. Document all functions and classes

## Features Implementation Status

### **Admin Features (ASIF)**
- [ ] **Authentication System**
  - [ ] Login form with validation
  - [ ] Session management
  - [ ] Password hashing and security
  - [ ] Logout functionality

- [ ] **Menu Management CRUD**
  - [ ] Add new menu items (Create)
  - [ ] Display menu items list (Read)
  - [ ] Edit existing items (Update)
  - [ ] Delete menu items (Delete)
  - [ ] Category management
  - [ ] Price and discount management

- [ ] **Admin Dashboard**
  - [ ] Dashboard overview with statistics
  - [ ] Navigation menu
  - [ ] Quick access to all admin functions
  - [ ] Responsive design implementation

## Testing Strategy

### **Unit Testing**
- [ ] Login system testing
- [ ] Menu CRUD operation testing
- [ ] Database connection testing
- [ ] Input validation testing
- [ ] Security testing (SQL injection, XSS)

### **Testing Framework**
- PHPUnit for backend testing
- Custom validation scripts
- Security vulnerability testing

## Technologies Used
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 8.x (following MVC pattern)
- **Database**: MySQL 8.x
- **Server**: Apache (XAMPP)
- **Version Control**: Git & GitHub
- **Testing**: PHPUnit for unit testing
- **Methodology**: Agile Scrum (15-day sprints)

## Database Schema (Admin Focus)

### **Tables Required for Admin Features**
```sql
-- Users table for admin authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table for menu organization
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Menu items table for CRUD operations
CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INT,
    image_url VARCHAR(500),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

## Project Deliverables

### **Documentation**
- [ ] Admin user manual
- [ ] Database schema documentation
- [ ] API documentation for admin functions
- [ ] System design documents (DFD, ERD)
- [ ] Unit test documentation

### **Code Deliverables**
- [ ] Complete admin login system
- [ ] Menu management CRUD operations
- [ ] Admin dashboard interface
- [ ] Database setup scripts
- [ ] Unit test suite

## Development Guidelines

### **Coding Standards**
- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment all functions and complex logic
- Implement proper error handling
- Sanitize all user inputs

### **Security Best Practices**
- Use prepared statements for database queries
- Implement CSRF protection
- Validate and sanitize all inputs
- Use password hashing (password_hash/password_verify)
- Implement proper session management

## Contributing
1. Work on the `asif-admin` branch
2. Follow the coding standards
3. Write unit tests for new features
4. Test thoroughly before committing
5. Use descriptive commit messages
6. Document all changes

## License
This project is developed for educational purposes as part of Information System Design coursework.