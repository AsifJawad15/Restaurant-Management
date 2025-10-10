-- Staff Sample Data for Existing Staff Table
-- ASIF - Backend & Database Developer
-- This file inserts sample staff data into your existing staff table

USE restaurant_management;

-- Note: Your existing staff table structure:
-- staff (id, user_id, employee_id, position, hire_date, salary, is_active, created_at)

-- Insert sample staff users into users table
-- Note: user_type should be 'admin' or 'customer' based on your enum, we'll use 'admin' for staff
INSERT INTO users (username, email, password_hash, first_name, last_name, phone, user_type, is_active) VALUES
('john.manager', 'john.manager@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Anderson', '555-0101', 'admin', 1),
('sarah.chef', 'sarah.chef@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Martinez', '555-0102', 'admin', 1),
('mike.chef', 'mike.chef@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael', 'Chen', '555-0103', 'admin', 1),
('emma.waiter', 'emma.waiter@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma', 'Johnson', '555-0104', 'admin', 1),
('david.waiter', 'david.waiter@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David', 'Williams', '555-0105', 'admin', 1),
('lisa.waiter', 'lisa.waiter@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa', 'Brown', '555-0106', 'admin', 1),
('robert.cashier', 'robert.cashier@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Robert', 'Taylor', '555-0107', 'admin', 1),
('maria.cleaner', 'maria.cleaner@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Garcia', '555-0108', 'admin', 1),
('james.waiter', 'james.waiter@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James', 'Wilson', '555-0109', 'admin', 1),
('sophia.chef', 'sophia.chef@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sophia', 'Rodriguez', '555-0110', 'admin', 1);

-- Get the starting user_id for staff members (last 10 inserted)
SET @staff_start_id = (SELECT LAST_INSERT_ID());

-- Insert staff records (linking to the users we just created)
-- Using existing staff table structure: employee_id, position, hire_date, salary, is_active
INSERT INTO staff (user_id, employee_id, position, hire_date, salary, is_active) VALUES
-- Manager
(@staff_start_id, 'EMP001', 'Manager', '2020-01-15', 65000.00, 1),

-- Chefs  
(@staff_start_id + 1, 'EMP002', 'Head Chef', '2020-03-01', 48000.00, 1),
(@staff_start_id + 2, 'EMP003', 'Sous Chef', '2021-06-15', 45000.00, 1),
(@staff_start_id + 9, 'EMP010', 'Line Cook', '2022-09-01', 42000.00, 1),

-- Waiters
(@staff_start_id + 3, 'EMP004', 'Waiter', '2021-02-10', 32000.00, 1),
(@staff_start_id + 4, 'EMP005', 'Waiter', '2021-08-20', 30000.00, 1),
(@staff_start_id + 5, 'EMP006', 'Waiter', '2022-01-05', 31000.00, 0),
(@staff_start_id + 8, 'EMP009', 'Waiter', '2023-03-12', 29000.00, 1),

-- Cashier
(@staff_start_id + 6, 'EMP007', 'Cashier', '2020-11-01', 35000.00, 1),

-- Cleaner
(@staff_start_id + 7, 'EMP008', 'Cleaner', '2021-04-15', 28000.00, 1);

-- Verify the data
SELECT 
    s.id,
    s.employee_id,
    CONCAT(u.first_name, ' ', u.last_name) as full_name,
    u.email,
    u.phone,
    s.position,
    s.salary,
    s.hire_date,
    CASE WHEN s.is_active = 1 THEN 'Active' ELSE 'Inactive' END as status
FROM users u
JOIN staff s ON u.id = s.user_id
ORDER BY s.position, u.first_name;

-- Summary statistics
SELECT 
    s.position,
    COUNT(*) as count,
    AVG(s.salary) as avg_salary,
    SUM(s.salary) as total_payroll
FROM staff s
GROUP BY s.position
ORDER BY total_payroll DESC;

SELECT 
    '✓ Staff Sample Data Inserted' as status,
    COUNT(*) as total_staff_members
FROM staff;
