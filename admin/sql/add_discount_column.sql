-- Add discount column to products table
ALTER TABLE products 
ADD COLUMN discount DECIMAL(5,2) DEFAULT 0.00 
AFTER price;

-- Update existing products to have 0 discount
UPDATE products SET discount = 0.00 WHERE discount IS NULL;
