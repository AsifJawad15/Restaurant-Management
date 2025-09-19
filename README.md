# Restaurant Management System (RMS)

A comprehensive restaurant management system built with PHP, CSS, JavaScript, and MySQL database using XAMPP, following Information System Design principles and Agile Scrum ### **User Stories**: Broken down by developer assignments
  - "As an admin, I want to manage menu items through a secure backend interface" (ASIF)
  - "As a customer, I want to browse menu items and add them to my cart and favourites" (APU)
  - "As a manager, I want to view order history and customer ratings for business insights" (ARFAN)odology.

## Project Overview

This project implements a **Restaurant Management System** with dynamic functionalities including owner login, menu item management, and customer-facing menu with ordering functionality. The system uses XAMPP for local development, integrating PHP and SQL for dynamic interactions.

## Team Roles and Responsibilities

### **Scrum Master**
- Responsible for maintaining sprint schedule, managing daily standups, and overseeing Jira task allocation

### **Developer Team**
- **ASIF** - **Backend, Database & Admin Features Developer**
  - Responsible for backend development, database operations, and admin functionality
  - Admin login system with demo credentials (aj@gmail.com/1234)
  - Menu management CRUD operations using PHP and MySQL
  - Database design and optimization
  - Server-side security and validation

- **APU** - **Frontend & Customer Features Developer**
  - Focus on frontend development and customer-facing features
  - Customer order management and cart functionality
  - Favourites system and customer UI/UX
  - Responsive design with Bootstrap and JavaScript
  - Customer menu browsing and interaction

- **ARFAN** - **Management & Analytics Features Developer**
  - Implement management features, order history, and rating systems
  - Order history and tracking functionality
  - Rating and review system development
  - Management dashboards and analytics
  - System integration and reporting features

### **Project Scope & Methodology**
- **Scope**: Dynamic RMS with backend API, customer frontend with order/cart/favourites functionality, and management features with history and analytics
- **Methodology**: Agile Scrum with 15-day sprints, Kanban for task management in Jira, GitHub for version control with specialized branches
- **Demo Credentials**: aj@gmail.com / 1234

## Functional Requirements

### **Backend & Admin Features (ASIF)**
- [ ] **Database Design**: Complete database schema with relationships
- [ ] **Admin Authentication**: Login system with demo credentials (aj@gmail.com/1234)
- [ ] **Menu Management CRUD**: 
  - Create new food items with name, description, price, category
  - Read/Display all menu items with filtering
  - Update existing food items and pricing
  - Delete menu items with validation
  - Category management system
- [ ] **Backend API**: RESTful APIs for frontend consumption
- [ ] **Security**: Input validation, SQL injection prevention, session management

### **Frontend & Customer Features (APU)**
- [ ] **Customer Interface**: Responsive design with Bootstrap
- [ ] **Menu Browsing**: Category-wise menu display with search/filter
- [ ] **Shopping Cart**: 
  - Add/remove items from cart
  - Quantity management
  - Cart persistence across sessions
  - Total calculation with discounts
- [ ] **Order Management**: Complete order placement workflow
- [ ] **Favourites System**: Save and manage favourite menu items
- [ ] **Customer Dashboard**: Order tracking and profile management

### **Management & Analytics Features (ARFAN)**
- [ ] **Order History**: Complete order tracking and history display
- [ ] **Rating & Review System**: 
  - Customer rating functionality
  - Review management and display
  - Rating analytics and reporting
- [ ] **Management Dashboard**: 
  - Order analytics and reporting
  - Sales tracking and statistics
  - Customer behavior analytics
- [ ] **System Integration**: Connecting all modules seamlessly

## Project Structure
```
Restaurant-Management/
├── assets/
│   ├── css/          # Stylesheets (Bootstrap + Custom)
│   ├── js/           # JavaScript files (Main, Admin, Customer)
│   └── images/       # Images and media files
├── includes/         # PHP include files (header, footer, config)
├── pages/            # Customer-facing pages (menu, cart, orders)
├── admin/            # Admin panel files (Login, Dashboard, Menu CRUD)
├── database/         # Database scripts and configuration
├── tests/            # Unit testing files
└── README.md
```

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
- [x] Define features, collect user stories, prepare Jira backlog
- [x] Prepare GitHub repository and branch structure
- [x] Initial project setup and team role assignment
- [ ] Prepare initial DFD and ER diagrams

### **Days 3-5: System Design**
- [ ] Develop detailed DFDs (Level 0, 1) - All team members
- [ ] Create UI wireframes for admin dashboard - ASIF
- [ ] Design customer interface wireframes - APU  
- [ ] Plan optional features architecture - ARFAN
- [ ] Define database schema and relationships
- [ ] Select design patterns (MVC, Factory, Singleton)

### **Days 6-11: Implementation**
**ASIF - Backend & Admin Features:**
- [ ] Design and implement complete database schema
- [ ] Create admin authentication system with PHP sessions
- [ ] Build RESTful API endpoints for all operations
- [ ] Implement menu management CRUD operations
- [ ] Add comprehensive input validation and security measures

**APU - Frontend & Customer Features:**
- [ ] Develop responsive customer interface with Bootstrap
- [ ] Implement dynamic menu browsing with AJAX
- [ ] Create shopping cart functionality with local storage
- [ ] Build order placement workflow and confirmation system
- [ ] Develop favourites system for customers

**ARFAN - Management & Analytics:**
- [ ] Implement comprehensive order history system
- [ ] Create rating and review functionality
- [ ] Build management dashboard with analytics
- [ ] Develop reporting features and statistics
- [ ] Ensure seamless integration between all modules

### **Days 12-13: Testing**
- [ ] Unit testing for all individual features
- [ ] Integration testing between modules
- [ ] Security testing (SQL injection, XSS)
- [ ] Cross-browser and device compatibility testing

### **Day 14: Deployment**
- [ ] Final bug fixes and optimization
- [ ] Documentation completion
- [ ] GitHub repository finalization
- [ ] Prepare for demo deployment

### **Day 15: Review**
- [ ] Project demonstration to stakeholders
- [ ] Code review and team retrospective
- [ ] Final documentation and project delivery

## Branch Strategy & Version Control

### **Branch Structure**
- `main` - Production ready code (stable version)
- `develop` - Integration branch for testing features
- `asif-backend` - ASIF's backend, database & admin development
- `apu-frontend` - APU's frontend, customer features & cart development
- `arfan-management` - ARFAN's management, history & rating development

### **Git Workflow**
1. **Daily commits** to respective developer branches
2. **Merge requests** to `develop` branch for integration testing
3. Regular **conflict resolution** and code reviews
4. Final merge to `main` branch after complete testing
5. Follow conventional commit messages

### **Collaboration Guidelines**
1. **ASIF** works on `asif-backend` branch for all backend and admin features
2. **APU** works on `apu-frontend` branch for all customer-facing features
3. **ARFAN** works on `arfan-management` branch for management and analytics
4. Regular pulls from `develop` to stay updated with team progress
5. Test thoroughly before pushing changes
6. Follow PHP coding standards (PSR-12) and JavaScript best practices
7. Document all functions and API endpoints

## Agile & Jira Management

### **Project Structure in Jira**
- **Epics**: Owner Login, Menu Management, Customer Ordering, Optional Features
- **User Stories**: Broken down by developer assignments
  - "As an owner, I want to be able to add a new food item with price and description" (ASIF)
  - "As a customer, I want to browse the menu and add items to my cart" (APU)
  - "As a customer, I want to view my order history" (ARFAN)

### **Kanban Board**
- **To-Do**: Backlog items ready for development
- **In Progress**: Currently being worked on
- **Code Review**: Ready for team review
- **Testing**: Under testing phase
- **Done**: Completed and integrated

## Features Implementation Status

### **Backend & Admin Features (ASIF)**
- [ ] **Database Design**
  - [ ] Complete schema design with relationships
  - [ ] Database optimization and indexing
  - [ ] Data migration scripts

- [ ] **Authentication System**
  - [ ] Admin login with session management
  - [ ] Password hashing and security
  - [ ] Role-based access control

- [ ] **Menu Management API**
  - [ ] CRUD operations for menu items
  - [ ] Category management system
  - [ ] Price and discount management
  - [ ] Image upload functionality

- [ ] **Backend Security**
  - [ ] Input validation and sanitization
  - [ ] SQL injection prevention
  - [ ] API authentication and authorization

### **Frontend & Customer Features (APU)**
- [ ] **Customer Interface**
  - [ ] Responsive menu browsing
  - [ ] Advanced search and filtering
  - [ ] Category-wise navigation

- [ ] **Shopping Cart System**
  - [ ] Add/remove items functionality
  - [ ] Quantity management
  - [ ] Cart persistence
  - [ ] Price calculation with discounts

- [ ] **Order Management**
  - [ ] Complete order workflow
  - [ ] Order confirmation system
  - [ ] Real-time order tracking

- [ ] **Favourites System**
  - [ ] Save favourite items
  - [ ] Manage favourites list
  - [ ] Quick reorder from favourites

### **Management & Analytics Features (ARFAN)**
- [ ] **Order History System**
  - [ ] Complete order tracking
  - [ ] Order status management
  - [ ] Historical data analysis

- [ ] **Rating & Review System**
  - [ ] Customer rating interface
  - [ ] Review submission and display
  - [ ] Rating analytics and insights

- [ ] **Management Dashboard**
  - [ ] Sales analytics and reporting
  - [ ] Customer behavior insights
  - [ ] Performance metrics
  - [ ] Business intelligence features

## Testing Strategy

### **Unit Testing**
- **ASIF**: Backend API testing, Database operations, Admin authentication, Menu CRUD operations
- **APU**: Frontend functionality, Cart operations, Order placement, Customer interface
- **ARFAN**: Order history, Rating system, Management dashboard, Analytics features
- **All**: Integration testing, Security testing, Performance testing

### **Integration Testing**
- Module integration testing
- Cross-feature functionality testing
- End-to-end user journey testing

### **Testing Framework**
- PHPUnit for backend testing
- JavaScript testing for frontend functionality
- Manual testing for UI/UX validation
- Security vulnerability testing

## Technologies Used
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 8.x (following MVC pattern)
- **Database**: MySQL 8.x
- **Server**: Apache (XAMPP)
- **Version Control**: Git & GitHub
- **Testing**: PHPUnit for unit testing
- **Methodology**: Agile Scrum (15-day sprints)

## Database Schema (Complete System)

### **Tables for All Features**
```sql
-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
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
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Orders table for customer orders
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id)
);

-- Order items for order details
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    menu_item_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- Favourites table for customer preferences (APU's feature)
CREATE TABLE favourites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    menu_item_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id),
    UNIQUE KEY unique_favourite (customer_id, menu_item_id)
);

-- Cart table for shopping cart functionality (APU's feature)
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    menu_item_id INT,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- Table reservations (ARFAN's feature)
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    table_number INT,
    reservation_date DATE,
    reservation_time TIME,
    party_size INT,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id)
);

-- Reviews and ratings (ARFAN's feature)
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    menu_item_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);
```

## Project Deliverables

### **Documentation**
- [ ] **ASIF**: Admin user manual and API documentation
- [ ] **APU**: Customer user manual and frontend documentation  
- [ ] **ARFAN**: Optional features documentation and integration guide
- [ ] **All**: System design documents (DFD, ERD), database schema documentation

### **Code Deliverables**
- [ ] **ASIF**: Complete backend API system, Admin authentication, Menu management CRUD, Database schema and operations
- [ ] **APU**: Customer frontend interface, Shopping cart system, Order placement functionality, Favourites management
- [ ] **ARFAN**: Order history and tracking, Rating/review system, Management dashboard, Analytics and reporting features
- [ ] **All**: Database setup scripts, Unit test suites, Integration documentation, API documentation

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

### **Team Collaboration**
- **ASIF**: Focus on secure backend development, database optimization, and robust API design
- **APU**: Ensure responsive design, intuitive customer experience, and smooth cart/order functionality
- **ARFAN**: Integrate management features seamlessly and provide valuable business insights through analytics
- **All**: Regular code reviews, consistent coding standards, and collaborative problem-solving

## Contributing
1. Work on your assigned branch (`asif-backend`, `apu-frontend`, `arfan-management`)
2. Follow the coding standards and security guidelines
3. Write unit tests for new features
4. Test thoroughly before committing
5. Use descriptive commit messages following conventional commits
6. Create pull requests for feature integration to `develop` branch
7. Participate in code reviews and team discussions
8. Document APIs and complex functionality

## License
This project is developed for educational purposes as part of Information System Design coursework following Agile Scrum methodology.