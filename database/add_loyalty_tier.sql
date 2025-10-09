-- Add loyalty tier and total spent columns to customer_profiles table
-- Run this script to update the database schema for loyalty points feature

USE restaurant_management;

-- Add loyalty_tier column if it doesn't exist
ALTER TABLE customer_profiles 
ADD COLUMN IF NOT EXISTS loyalty_tier ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze' AFTER loyalty_points;

-- Add total_spent column if it doesn't exist
ALTER TABLE customer_profiles 
ADD COLUMN IF NOT EXISTS total_spent DECIMAL(10,2) DEFAULT 0.00 AFTER loyalty_tier;

-- Update loyalty tiers based on current points
UPDATE customer_profiles 
SET loyalty_tier = CASE 
    WHEN loyalty_points >= 2000 THEN 'platinum'
    WHEN loyalty_points >= 1000 THEN 'gold'
    WHEN loyalty_points >= 500 THEN 'silver'
    ELSE 'bronze'
END;

-- Display results
SELECT 'Loyalty columns added successfully!' as status;
SELECT 
    loyalty_tier,
    COUNT(*) as customer_count,
    AVG(loyalty_points) as avg_points
FROM customer_profiles
GROUP BY loyalty_tier;
