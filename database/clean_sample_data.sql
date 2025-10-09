-- Clean Sample Data for Restaurant Management System
-- This script safely adds sample data without ID conflicts
-- Run this script to populate database with test data for reports and analytics

USE restaurant_management;

-- Disable foreign key checks temporarily for clean insertion
SET FOREIGN_KEY_CHECKS = 0;

-- Clear ALL existing sample data (including old orders)
DELETE FROM reviews;
DELETE FROM reservations;
DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM customer_profiles WHERE user_id NOT IN (2, 4);
DELETE FROM users WHERE id NOT IN (2, 4) AND user_type = 'customer';

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Reset auto increment for users (start fresh after existing users)
-- Note: This will be set to max(id) + 1 automatically

-- =============================================
-- INSERT NEW CUSTOMERS
-- =============================================

INSERT INTO `users` (`username`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `user_type`, `is_active`) VALUES
('john_doe', 'john@example.com', '$2y$10$ndJy97oVQfKlTfxYCOAZJ.wz9sQlxjnXGqkgUui0vjJav.LnjSL4C', 'John', 'Doe', '01712345671', 'customer', 1),
('jane_smith', 'jane@example.com', '$2y$10$ndJy97oVQfKlTfxYCOAZJ.wz9sQlxjnXGqkgUui0vjJav.LnjSL4C', 'Jane', 'Smith', '01712345672', 'customer', 1),
('mike_wilson', 'mike@example.com', '$2y$10$ndJy97oVQfKlTfxYCOAZJ.wz9sQlxjnXGqkgUui0vjJav.LnjSL4C', 'Mike', 'Wilson', '01712345673', 'customer', 1),
('sarah_jones', 'sarah@example.com', '$2y$10$ndJy97oVQfKlTfxYCOAZJ.wz9sQlxjnXGqkgUui0vjJav.LnjSL4C', 'Sarah', 'Jones', '01712345674', 'customer', 1),
('david_brown', 'david@example.com', '$2y$10$ndJy97oVQfKlTfxYCOAZJ.wz9sQlxjnXGqkgUui0vjJav.LnjSL4C', 'David', 'Brown', '01712345675', 'customer', 1),
('emily_davis', 'emily@example.com', '$2y$10$ndJy97oVQfKlTfxYCOAZJ.wz9sQlxjnXGqkgUui0vjJav.LnjSL4C', 'Emily', 'Davis', '01712345676', 'customer', 1),
('robert_miller', 'robert@example.com', '$2y$10$ndJy97oVQfKlTfxYCOAZJ.wz9sQlxjnXGqkgUui0vjJav.LnjSL4C', 'Robert', 'Miller', '01712345677', 'customer', 1),
('lisa_garcia', 'lisa@example.com', '$2y$10$ndJy97oVQfKlTfxYCOAZJ.wz9sQlxjnXGqkgUui0vjJav.LnjSL4C', 'Lisa', 'Garcia', '01712345678', 'customer', 1);

-- Get the user IDs that were just created
SET @john_id = (SELECT id FROM users WHERE email = 'john@example.com');
SET @jane_id = (SELECT id FROM users WHERE email = 'jane@example.com');
SET @mike_id = (SELECT id FROM users WHERE email = 'mike@example.com');
SET @sarah_id = (SELECT id FROM users WHERE email = 'sarah@example.com');
SET @david_id = (SELECT id FROM users WHERE email = 'david@example.com');
SET @emily_id = (SELECT id FROM users WHERE email = 'emily@example.com');
SET @robert_id = (SELECT id FROM users WHERE email = 'robert@example.com');
SET @lisa_id = (SELECT id FROM users WHERE email = 'lisa@example.com');
SET @asif_id = 2; -- Existing customer

-- =============================================
-- INSERT CUSTOMER PROFILES
-- =============================================

INSERT INTO `customer_profiles` (`user_id`, `address`, `city`, `state`, `zip_code`, `loyalty_points`, `loyalty_tier`, `total_spent`) VALUES
(@john_id, '123 Main St', 'Dhaka', 'Bangladesh', '1200', 350, 'bronze', 1250.00),
(@jane_id, '456 Park Ave', 'Chittagong', 'Bangladesh', '4000', 650, 'silver', 2500.00),
(@mike_id, '789 Oak Rd', 'Khulna', 'Bangladesh', '9100', 1200, 'gold', 4800.00),
(@sarah_id, '321 Elm St', 'Dhaka', 'Bangladesh', '1205', 450, 'bronze', 1800.00),
(@david_id, '654 Pine Ln', 'Sylhet', 'Bangladesh', '3100', 2100, 'platinum', 8500.00),
(@emily_id, '987 Maple Dr', 'Rajshahi', 'Bangladesh', '6000', 800, 'silver', 3200.00),
(@robert_id, '147 Cedar Way', 'Dhaka', 'Bangladesh', '1210', 1500, 'gold', 6000.00),
(@lisa_id, '258 Birch Ct', 'Chittagong', 'Bangladesh', '4100', 950, 'silver', 3800.00);

-- Update existing customer's loyalty data
UPDATE `customer_profiles` SET loyalty_tier = 'bronze', total_spent = 240.00 WHERE user_id = @asif_id;

-- =============================================
-- INSERT ORDERS (35 orders spread across 30 days)
-- =============================================

INSERT INTO `orders` (`customer_id`, `table_id`, `order_type`, `status`, `total_amount`, `tax_amount`, `discount_amount`, `final_amount`, `payment_status`, `payment_method`, `created_at`, `updated_at`) VALUES
-- Orders from 30 days ago
(@asif_id, 1, 'dine_in', 'completed', 45.97, 3.91, 2.30, 47.58, 'paid', 'credit_card', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
(@john_id, 2, 'dine_in', 'completed', 55.96, 4.76, 0.00, 60.72, 'paid', 'cash', DATE_SUB(NOW(), INTERVAL 29 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY)),
(@jane_id, NULL, 'delivery', 'completed', 72.95, 6.20, 7.30, 71.85, 'paid', 'digital_wallet', DATE_SUB(NOW(), INTERVAL 28 DAY), DATE_SUB(NOW(), INTERVAL 28 DAY)),

-- Orders from 25 days ago
(@mike_id, 3, 'dine_in', 'completed', 89.94, 7.64, 13.49, 84.09, 'paid', 'credit_card', DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY)),
(@sarah_id, NULL, 'takeout', 'completed', 34.97, 2.97, 0.00, 37.94, 'paid', 'cash', DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY)),

-- Orders from 20 days ago
(@david_id, 4, 'dine_in', 'completed', 124.91, 10.62, 24.98, 110.55, 'paid', 'credit_card', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY)),
(@emily_id, NULL, 'delivery', 'completed', 67.96, 5.78, 6.80, 67.94, 'paid', 'digital_wallet', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY)),
(@robert_id, 5, 'dine_in', 'completed', 98.93, 8.41, 14.84, 92.50, 'paid', 'debit_card', DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY)),

-- Orders from 15 days ago
(@lisa_id, NULL, 'takeout', 'completed', 44.97, 3.82, 4.50, 44.29, 'paid', 'cash', DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY)),
(@asif_id, 6, 'dine_in', 'completed', 56.97, 4.84, 2.85, 58.96, 'paid', 'credit_card', DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY)),
(@john_id, NULL, 'delivery', 'completed', 81.95, 6.97, 0.00, 88.92, 'paid', 'digital_wallet', DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY)),

-- Orders from 10 days ago
(@jane_id, 1, 'dine_in', 'completed', 112.92, 9.60, 11.29, 111.23, 'paid', 'credit_card', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
(@mike_id, NULL, 'takeout', 'completed', 65.96, 5.61, 9.89, 61.68, 'paid', 'cash', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
(@sarah_id, 2, 'dine_in', 'completed', 78.95, 6.71, 0.00, 85.66, 'paid', 'debit_card', DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 9 DAY)),

-- Orders from 7 days ago
(@david_id, NULL, 'delivery', 'completed', 145.89, 12.40, 29.18, 129.11, 'paid', 'credit_card', DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)),
(@emily_id, 3, 'dine_in', 'completed', 92.94, 7.90, 9.29, 91.55, 'paid', 'digital_wallet', DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY)),
(@robert_id, NULL, 'takeout', 'completed', 52.97, 4.50, 7.95, 49.52, 'paid', 'cash', DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),

-- Orders from 5 days ago
(@lisa_id, 4, 'dine_in', 'completed', 87.94, 7.47, 8.79, 86.62, 'paid', 'credit_card', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(@asif_id, NULL, 'delivery', 'completed', 69.96, 5.95, 3.50, 72.41, 'paid', 'digital_wallet', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(@john_id, 5, 'dine_in', 'completed', 118.91, 10.11, 0.00, 129.02, 'paid', 'debit_card', DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),

-- Orders from 3 days ago
(@jane_id, NULL, 'takeout', 'completed', 76.96, 6.54, 7.70, 75.80, 'paid', 'cash', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(@mike_id, 6, 'dine_in', 'completed', 134.90, 11.47, 20.24, 126.13, 'paid', 'credit_card', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(@sarah_id, NULL, 'delivery', 'completed', 58.97, 5.01, 0.00, 63.98, 'paid', 'digital_wallet', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Orders from yesterday
(@david_id, 1, 'dine_in', 'completed', 156.88, 13.33, 31.38, 138.83, 'paid', 'credit_card', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@emily_id, NULL, 'takeout', 'completed', 83.95, 7.14, 8.40, 82.69, 'paid', 'cash', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@robert_id, 2, 'dine_in', 'completed', 102.93, 8.75, 15.44, 96.24, 'paid', 'debit_card', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Orders from today (with various statuses)
(@lisa_id, NULL, 'delivery', 'completed', 91.95, 7.82, 9.20, 90.57, 'paid', 'digital_wallet', NOW() - INTERVAL 8 HOUR, NOW() - INTERVAL 8 HOUR),
(@asif_id, 3, 'dine_in', 'preparing', 67.96, 5.78, 3.40, 70.34, 'pending', 'credit_card', NOW() - INTERVAL 4 HOUR, NOW() - INTERVAL 4 HOUR),
(@john_id, NULL, 'takeout', 'ready', 45.97, 3.91, 0.00, 49.88, 'paid', 'cash', NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 2 HOUR),
(@jane_id, 4, 'dine_in', 'confirmed', 124.91, 10.62, 12.49, 123.04, 'pending', 'credit_card', NOW() - INTERVAL 1 HOUR, NOW() - INTERVAL 1 HOUR),
(@mike_id, NULL, 'delivery', 'pending', 78.95, 6.71, 0.00, 85.66, 'pending', 'cash', NOW() - INTERVAL 30 MINUTE, NOW() - INTERVAL 30 MINUTE),

-- Additional orders for more data points
(@david_id, 5, 'dine_in', 'completed', 95.93, 8.15, 19.19, 84.89, 'paid', 'credit_card', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY)),
(@emily_id, NULL, 'takeout', 'completed', 67.95, 5.78, 6.80, 66.93, 'paid', 'cash', DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),
(@robert_id, 6, 'dine_in', 'completed', 143.89, 12.23, 21.58, 134.54, 'paid', 'debit_card', DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
(@lisa_id, NULL, 'delivery', 'completed', 88.94, 7.56, 8.89, 87.61, 'paid', 'digital_wallet', DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY));

-- =============================================
-- INSERT ORDER ITEMS
-- =============================================
-- We'll use a procedure to get the correct order IDs

-- Get order IDs created (they will be sequential from the inserts)
-- Since we cleared all orders, these will start from the first auto-increment value
SET @order_start_id = (SELECT MIN(id) FROM orders);

-- Order items for each order
-- Order 1
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 0, 1, 1, 8.99, 8.99),
(@order_start_id + 0, 2, 1, 12.99, 12.99),
(@order_start_id + 0, 4, 1, 24.99, 24.99);

-- Order 2
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 1, 3, 2, 18.99, 37.98),
(@order_start_id + 1, 1, 2, 8.99, 17.98);

-- Order 3
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 2, 4, 2, 24.99, 49.98),
(@order_start_id + 2, 2, 1, 12.99, 12.99),
(@order_start_id + 2, 6, 2, 3.99, 7.98);

-- Order 4
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 3, 4, 3, 24.99, 74.97),
(@order_start_id + 3, 5, 2, 6.99, 13.98);

-- Order 5
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 4, 3, 1, 18.99, 18.99),
(@order_start_id + 4, 1, 1, 8.99, 8.99),
(@order_start_id + 4, 5, 1, 6.99, 6.99);

-- Order 6
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 5, 4, 5, 24.99, 124.95);

-- Order 7
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 6, 3, 2, 18.99, 37.98),
(@order_start_id + 6, 2, 1, 12.99, 12.99),
(@order_start_id + 6, 6, 4, 3.99, 15.96);

-- Order 8
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 7, 4, 3, 24.99, 74.97),
(@order_start_id + 7, 1, 2, 8.99, 17.98),
(@order_start_id + 7, 5, 1, 6.99, 6.99);

-- Order 9
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 8, 2, 2, 12.99, 25.98),
(@order_start_id + 8, 3, 1, 18.99, 18.99);

-- Order 10
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 9, 3, 1, 18.99, 18.99),
(@order_start_id + 9, 4, 1, 24.99, 24.99),
(@order_start_id + 9, 2, 1, 12.99, 12.99);

-- Order 11
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 10, 4, 2, 24.99, 49.98),
(@order_start_id + 10, 3, 1, 18.99, 18.99),
(@order_start_id + 10, 6, 3, 3.99, 11.97);

-- Order 12
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 11, 4, 4, 24.99, 99.96),
(@order_start_id + 11, 2, 1, 12.99, 12.99);

-- Order 13
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 12, 3, 2, 18.99, 37.98),
(@order_start_id + 12, 4, 1, 24.99, 24.99),
(@order_start_id + 12, 6, 1, 3.99, 3.99);

-- Order 14
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 13, 4, 3, 24.99, 74.97),
(@order_start_id + 13, 6, 1, 3.99, 3.99);

-- Order 15
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 14, 4, 5, 24.99, 124.95),
(@order_start_id + 14, 3, 1, 18.99, 18.99),
(@order_start_id + 14, 5, 1, 6.99, 6.99);

-- Order 16
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 15, 4, 3, 24.99, 74.97),
(@order_start_id + 15, 3, 1, 18.99, 18.99);

-- Order 17
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 16, 1, 3, 8.99, 26.97),
(@order_start_id + 16, 2, 2, 12.99, 25.98);

-- Order 18
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 17, 4, 4, 24.99, 99.96),
(@order_start_id + 17, 3, 1, 18.99, 18.99);

-- Order 19
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 18, 2, 4, 12.99, 51.96),
(@order_start_id + 18, 5, 3, 6.99, 20.97),
(@order_start_id + 18, 6, 2, 3.99, 7.98);

-- Order 20
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 19, 3, 2, 18.99, 37.98),
(@order_start_id + 19, 6, 2, 3.99, 7.98);

-- Order 21
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 20, 4, 5, 24.99, 124.95),
(@order_start_id + 20, 6, 2, 3.99, 7.98);

-- Order 22
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 21, 3, 3, 18.99, 56.97),
(@order_start_id + 21, 1, 2, 8.99, 17.98);

-- Order 23
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 22, 4, 3, 24.99, 74.97),
(@order_start_id + 22, 2, 1, 12.99, 12.99);

-- Order 24
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 23, 3, 2, 18.99, 37.98),
(@order_start_id + 23, 4, 1, 24.99, 24.99),
(@order_start_id + 23, 5, 1, 6.99, 6.99);

-- Order 25
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 24, 2, 3, 12.99, 38.97),
(@order_start_id + 24, 3, 2, 18.99, 37.98);

-- Order 26
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 25, 4, 4, 24.99, 99.96),
(@order_start_id + 25, 1, 3, 8.99, 26.97),
(@order_start_id + 25, 6, 2, 3.99, 7.98);

-- Order 27
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 26, 3, 2, 18.99, 37.98),
(@order_start_id + 26, 2, 1, 12.99, 12.99),
(@order_start_id + 26, 6, 2, 3.99, 7.98);

-- Order 28
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 27, 4, 6, 24.99, 149.94),
(@order_start_id + 27, 5, 1, 6.99, 6.99);

-- Order 29
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 28, 3, 3, 18.99, 56.97),
(@order_start_id + 28, 4, 1, 24.99, 24.99);

-- Order 30
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 29, 4, 3, 24.99, 74.97),
(@order_start_id + 29, 2, 1, 12.99, 12.99),
(@order_start_id + 29, 5, 2, 6.99, 13.98);

-- Order 31
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 30, 4, 3, 24.99, 74.97),
(@order_start_id + 30, 6, 4, 3.99, 15.96);

-- Order 32
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 31, 3, 2, 18.99, 37.98),
(@order_start_id + 31, 4, 1, 24.99, 24.99),
(@order_start_id + 31, 1, 1, 8.99, 8.99);

-- Order 33
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 32, 2, 2, 12.99, 25.98),
(@order_start_id + 32, 3, 1, 18.99, 18.99);

-- Order 34
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 33, 4, 5, 24.99, 124.95);

-- Order 35
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `unit_price`, `total_price`) VALUES
(@order_start_id + 34, 3, 3, 18.99, 56.97),
(@order_start_id + 34, 6, 2, 3.99, 7.98);

-- =============================================
-- INSERT RESERVATIONS
-- =============================================

INSERT INTO `reservations` (`customer_id`, `table_id`, `reservation_date`, `reservation_time`, `party_size`, `status`, `special_requests`, `created_at`) VALUES
(@asif_id, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00', 2, 'confirmed', 'Window seat preferred', NOW()),
(@john_id, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '19:30:00', 4, 'confirmed', NULL, NOW()),
(@jane_id, 6, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '20:00:00', 8, 'pending', 'Birthday celebration', NOW()),
(@mike_id, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', 6, 'confirmed', NULL, NOW()),
(@david_id, 4, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '12:30:00', 2, 'pending', 'Quiet area please', NOW()),
(@robert_id, 5, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '19:00:00', 4, 'confirmed', 'Anniversary dinner', NOW());

-- =============================================
-- INSERT REVIEWS
-- =============================================

INSERT INTO `reviews` (`customer_id`, `order_id`, `menu_item_id`, `rating`, `comment`, `is_verified`, `created_at`) VALUES
(@asif_id, @order_start_id + 0, 4, 5, 'The beef steak was cooked to perfection! Absolutely delicious.', 1, DATE_SUB(NOW(), INTERVAL 29 DAY)),
(@john_id, @order_start_id + 1, 3, 4, 'Salmon was fresh and well-seasoned. Good portion size.', 1, DATE_SUB(NOW(), INTERVAL 28 DAY)),
(@jane_id, @order_start_id + 2, 4, 5, 'Best steak in town! Will definitely order again.', 1, DATE_SUB(NOW(), INTERVAL 27 DAY)),
(@mike_id, @order_start_id + 3, 4, 5, 'Amazing quality and taste. Worth every penny.', 1, DATE_SUB(NOW(), INTERVAL 24 DAY)),
(@sarah_id, @order_start_id + 4, 3, 4, 'Great salmon dish. Could use a bit more herbs.', 1, DATE_SUB(NOW(), INTERVAL 24 DAY)),
(@david_id, @order_start_id + 5, 4, 5, 'Exceptional! The meat was tender and juicy.', 1, DATE_SUB(NOW(), INTERVAL 19 DAY)),
(@emily_id, @order_start_id + 6, 3, 5, 'Perfectly grilled salmon. Highly recommend!', 1, DATE_SUB(NOW(), INTERVAL 19 DAY)),
(@robert_id, @order_start_id + 7, 4, 4, 'Very good steak. Nice atmosphere too.', 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),
(@lisa_id, @order_start_id + 8, 2, 5, 'Buffalo wings were spicy and crispy. Loved them!', 1, DATE_SUB(NOW(), INTERVAL 14 DAY)),
(@asif_id, @order_start_id + 9, 3, 4, 'Good meal overall. Quick service.', 1, DATE_SUB(NOW(), INTERVAL 14 DAY)),
(@john_id, @order_start_id + 10, 4, 5, 'Outstanding quality. Best restaurant experience!', 1, DATE_SUB(NOW(), INTERVAL 13 DAY)),
(@jane_id, @order_start_id + 11, 4, 5, 'Superb! Will bring my family next time.', 1, DATE_SUB(NOW(), INTERVAL 9 DAY)),
(@mike_id, @order_start_id + 12, 3, 4, 'Solid meal. Good value for money.', 1, DATE_SUB(NOW(), INTERVAL 9 DAY)),
(@david_id, @order_start_id + 14, 4, 5, 'Incredible taste and presentation!', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(@emily_id, @order_start_id + 15, 4, 4, 'Great food. Portion could be slightly bigger.', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(@lisa_id, @order_start_id + 17, 4, 5, 'Fantastic dinner! Everything was perfect.', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(@jane_id, @order_start_id + 20, 4, 5, 'Absolutely delicious. Great service too!', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(@mike_id, @order_start_id + 21, 3, 5, 'Fresh and flavorful. Best salmon ever!', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(@david_id, @order_start_id + 23, 4, 5, 'Top notch quality. Highly recommended!', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@robert_id, @order_start_id + 25, 4, 5, 'Excellent meal. Worth the price!', 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- =============================================
-- SUMMARY
-- =============================================

SELECT '✅ Sample data inserted successfully!' as status;
SELECT 
    'Total Orders' as metric, 
    COUNT(*) as count 
FROM orders
UNION ALL
SELECT 
    'Total Customers' as metric, 
    COUNT(*) as count 
FROM users WHERE user_type = 'customer'
UNION ALL
SELECT 
    'Total Order Items' as metric, 
    COUNT(*) as count 
FROM order_items
UNION ALL
SELECT 
    'Total Reservations' as metric, 
    COUNT(*) as count 
FROM reservations
UNION ALL
SELECT 
    'Total Reviews' as metric, 
    COUNT(*) as count 
FROM reviews;

-- Show loyalty tier distribution
SELECT 
    '📊 Loyalty Tier Distribution' as info,
    loyalty_tier,
    COUNT(*) as customer_count,
    ROUND(AVG(loyalty_points), 0) as avg_points,
    ROUND(AVG(total_spent), 2) as avg_spent
FROM customer_profiles
GROUP BY loyalty_tier
ORDER BY 
    CASE loyalty_tier
        WHEN 'platinum' THEN 1
        WHEN 'gold' THEN 2
        WHEN 'silver' THEN 3
        WHEN 'bronze' THEN 4
    END;
