-- Sample Inventory Data for Restaurant Management System
-- This script adds sample inventory items for testing

USE restaurant_management;

-- Insert sample inventory items
INSERT INTO `inventory` (`item_name`, `category`, `current_stock`, `minimum_stock`, `unit`, `cost_per_unit`, `supplier`, `last_updated`) VALUES
-- Vegetables
('Tomatoes', 'Vegetables', 25, 20, 'kg', 2.50, 'Fresh Farm Suppliers', NOW()),
('Lettuce', 'Vegetables', 15, 10, 'kg', 1.80, 'Fresh Farm Suppliers', NOW()),
('Onions', 'Vegetables', 40, 30, 'kg', 1.20, 'Fresh Farm Suppliers', NOW()),
('Bell Peppers', 'Vegetables', 12, 15, 'kg', 3.50, 'Fresh Farm Suppliers', NOW()),
('Cucumbers', 'Vegetables', 18, 10, 'kg', 2.00, 'Fresh Farm Suppliers', NOW()),
('Carrots', 'Vegetables', 22, 15, 'kg', 1.50, 'Fresh Farm Suppliers', NOW()),

-- Meats & Seafood
('Chicken Breast', 'Meats', 30, 25, 'kg', 8.50, 'Prime Meat Co.', NOW()),
('Beef Ribeye', 'Meats', 20, 15, 'kg', 18.00, 'Prime Meat Co.', NOW()),
('Ground Beef', 'Meats', 15, 20, 'kg', 7.50, 'Prime Meat Co.', NOW()),
('Salmon Fillet', 'Seafood', 12, 10, 'kg', 15.00, 'Ocean Fresh Seafood', NOW()),
('Shrimp', 'Seafood', 8, 12, 'kg', 12.50, 'Ocean Fresh Seafood', NOW()),
('Bacon', 'Meats', 10, 8, 'kg', 9.00, 'Prime Meat Co.', NOW()),

-- Dairy Products
('Milk', 'Dairy', 30, 25, 'L', 1.50, 'Dairy Fresh Ltd.', NOW()),
('Butter', 'Dairy', 15, 10, 'kg', 6.00, 'Dairy Fresh Ltd.', NOW()),
('Cheese (Cheddar)', 'Dairy', 8, 10, 'kg', 10.00, 'Dairy Fresh Ltd.', NOW()),
('Cheese (Mozzarella)', 'Dairy', 12, 8, 'kg', 9.50, 'Dairy Fresh Ltd.', NOW()),
('Heavy Cream', 'Dairy', 10, 8, 'L', 4.50, 'Dairy Fresh Ltd.', NOW()),
('Eggs', 'Dairy', 25, 20, 'dozen', 3.50, 'Dairy Fresh Ltd.', NOW()),

-- Grains & Bread
('Rice', 'Grains', 50, 30, 'kg', 2.00, 'Grains & More', NOW()),
('Pasta', 'Grains', 20, 15, 'kg', 2.50, 'Grains & More', NOW()),
('Bread Flour', 'Grains', 35, 25, 'kg', 1.80, 'Bakery Supplies Co.', NOW()),
('Bread Rolls', 'Bakery', 40, 30, 'pcs', 0.50, 'Local Bakery', NOW()),
('Pizza Dough', 'Bakery', 15, 10, 'kg', 3.00, 'Bakery Supplies Co.', NOW()),

-- Oils & Condiments
('Olive Oil', 'Oils', 15, 10, 'L', 12.00, 'Mediterranean Imports', NOW()),
('Vegetable Oil', 'Oils', 20, 15, 'L', 5.00, 'Wholesale Foods', NOW()),
('Salt', 'Condiments', 10, 5, 'kg', 1.00, 'Wholesale Foods', NOW()),
('Black Pepper', 'Condiments', 3, 2, 'kg', 15.00, 'Spice World', NOW()),
('Garlic Powder', 'Condiments', 2, 3, 'kg', 8.00, 'Spice World', NOW()),
('Ketchup', 'Condiments', 12, 10, 'L', 4.50, 'Wholesale Foods', NOW()),
('Mayonnaise', 'Condiments', 10, 8, 'L', 5.50, 'Wholesale Foods', NOW()),
('Mustard', 'Condiments', 8, 6, 'L', 4.00, 'Wholesale Foods', NOW()),

-- Beverages
('Orange Juice', 'Beverages', 18, 15, 'L', 3.50, 'Juice Factory', NOW()),
('Apple Juice', 'Beverages', 15, 12, 'L', 3.20, 'Juice Factory', NOW()),
('Cola', 'Beverages', 50, 40, 'L', 1.50, 'Beverage Distributors', NOW()),
('Sparkling Water', 'Beverages', 30, 25, 'L', 1.20, 'Beverage Distributors', NOW()),
('Coffee Beans', 'Beverages', 10, 8, 'kg', 18.00, 'Premium Coffee Co.', NOW()),
('Tea Bags', 'Beverages', 200, 150, 'pcs', 0.15, 'Tea Imports', NOW()),

-- Frozen Items
('French Fries', 'Frozen', 40, 30, 'kg', 2.80, 'Frozen Foods Ltd.', NOW()),
('Ice Cream (Vanilla)', 'Frozen', 15, 10, 'L', 8.00, 'Dairy Fresh Ltd.', NOW()),
('Frozen Vegetables Mix', 'Frozen', 25, 20, 'kg', 3.50, 'Frozen Foods Ltd.', NOW()),

-- Herbs & Spices
('Basil (Fresh)', 'Herbs', 2, 3, 'kg', 12.00, 'Fresh Farm Suppliers', NOW()),
('Parsley (Fresh)', 'Herbs', 1, 2, 'kg', 10.00, 'Fresh Farm Suppliers', NOW()),
('Oregano (Dried)', 'Herbs', 1, 1, 'kg', 15.00, 'Spice World', NOW()),
('Thyme (Dried)', 'Herbs', 0.5, 1, 'kg', 18.00, 'Spice World', NOW()),

-- Paper & Disposables
('Paper Napkins', 'Disposables', 500, 300, 'pcs', 0.05, 'Restaurant Supplies Inc.', NOW()),
('Takeout Boxes', 'Disposables', 200, 150, 'pcs', 0.35, 'Restaurant Supplies Inc.', NOW()),
('Plastic Cutlery Sets', 'Disposables', 300, 200, 'pcs', 0.10, 'Restaurant Supplies Inc.', NOW()),
('Paper Cups', 'Disposables', 400, 300, 'pcs', 0.08, 'Restaurant Supplies Inc.', NOW()),

-- Cleaning Supplies
('Dish Soap', 'Cleaning', 10, 8, 'L', 6.00, 'Cleaning Supplies Co.', NOW()),
('All-Purpose Cleaner', 'Cleaning', 8, 6, 'L', 7.50, 'Cleaning Supplies Co.', NOW()),
('Dishwasher Detergent', 'Cleaning', 5, 5, 'kg', 12.00, 'Cleaning Supplies Co.', NOW()),
('Paper Towels', 'Cleaning', 0, 10, 'box', 15.00, 'Restaurant Supplies Inc.', NOW());

-- Display summary
SELECT '✅ Sample inventory data inserted successfully!' as status;

SELECT 
    'Inventory Summary' as report,
    COUNT(*) as total_items,
    COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock_items,
    COUNT(CASE WHEN current_stock = 0 THEN 1 END) as out_of_stock_items,
    CONCAT('$', FORMAT(SUM(current_stock * cost_per_unit), 2)) as total_inventory_value
FROM inventory;

-- Show items by category
SELECT 
    category,
    COUNT(*) as items,
    CONCAT('$', FORMAT(SUM(current_stock * cost_per_unit), 2)) as category_value
FROM inventory
GROUP BY category
ORDER BY category;

-- Show critical items (low stock or out of stock)
SELECT 
    '🚨 Critical Stock Alerts' as alert_type,
    item_name,
    current_stock,
    minimum_stock,
    unit,
    CASE 
        WHEN current_stock = 0 THEN 'OUT OF STOCK'
        WHEN current_stock <= minimum_stock THEN 'LOW STOCK'
    END as status
FROM inventory
WHERE current_stock <= minimum_stock
ORDER BY current_stock ASC;
