# Restaurant Management System

A comprehensive restaurant management system built with PHP, CSS, JavaScript, and MySQL database using XAMPP.

## Team Members
- **ASIF** - Team Lead & Backend Developer
- **APU** - Frontend Developer & UI/UX Designer  
- **ARFAN** - Database Developer & Integration Specialist

## Project Structure
```
Restaurant-Management/
├── assets/
│   ├── css/          # Stylesheets
│   ├── js/           # JavaScript files
│   └── images/       # Images and media files
├── includes/         # PHP include files (header, footer, config)
├── pages/            # Main application pages
├── admin/            # Admin panel files
├── database/         # Database scripts and configuration
└── README.md
```

## Setup Instructions

### Prerequisites
- XAMPP with Apache and MySQL running
- Git for version control
- Text editor or IDE (VS Code recommended)

### Installation
1. Clone the repository to your XAMPP htdocs directory
2. Start XAMPP and ensure Apache and MySQL are running
3. Import the database from `database/restaurant_db.sql`
4. Configure database connection in `includes/config.php`
5. Access the application at `http://localhost/Restaurant-Management/`

## Development Workflow

### Branch Strategy
- `main` - Production ready code
- `asif-dev` - ASIF's development branch
- `apu-dev` - APU's development branch  
- `arfan-dev` - ARFAN's development branch
- `develop` - Integration branch for testing features

### Collaboration Guidelines
1. Always work on your assigned branch
2. Regularly pull updates from main
3. Create pull requests for merging features
4. Test thoroughly before pushing changes
5. Follow PHP coding standards

## Features (To Be Implemented)
- [ ] User authentication and authorization
- [ ] Menu management system
- [ ] Order processing and tracking
- [ ] Customer management
- [ ] Inventory management
- [ ] Reporting and analytics
- [ ] Payment integration
- [ ] Table reservation system

## Technologies Used
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap
- **Backend**: PHP 8.x
- **Database**: MySQL 8.x
- **Server**: Apache (XAMPP)
- **Version Control**: Git & GitHub

## Contributing
1. Create your feature branch from `develop`
2. Make your changes
3. Test thoroughly
4. Submit a pull request
5. Wait for code review and approval

## License
This project is developed for educational purposes.