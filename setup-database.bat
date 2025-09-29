@echo off
echo ===============================================
echo Restaurant Management - Database Setup Script
echo ===============================================
echo.

REM Check if Laragon MySQL is running
echo Checking if MySQL is running...
netstat -an | find "3306" >nul
if %errorlevel%==0 (
    echo ✓ MySQL is running on port 3306
) else (
    echo ✗ MySQL is not running!
    echo Please start Laragon and try again.
    pause
    exit /b 1
)

echo.
echo Creating database and importing schema...
echo.

REM Navigate to the project directory
cd /d "%~dp0"

REM Create database and import schema
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS restaurant_management;"
if %errorlevel%==0 (
    echo ✓ Database 'restaurant_management' created successfully
) else (
    echo ✗ Failed to create database
    pause
    exit /b 1
)

echo.
echo Importing database schema...
mysql -u root -p restaurant_management < database\restaurant_schema.sql
if %errorlevel%==0 (
    echo ✓ Database schema imported successfully
) else (
    echo ✗ Failed to import schema
    pause
    exit /b 1
)

echo.
echo ===============================================
echo Database setup completed successfully!
echo ===============================================
echo.
echo You can now:
echo 1. Test the connection: http://restaurant-management.test/admin/test-db-connection.php
echo 2. Access admin login: http://restaurant-management.test/admin/login.php
echo 3. Login credentials:
echo    Email: admin@restaurant.com
echo    Password: pass1234
echo.
pause