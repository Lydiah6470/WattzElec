-- Add featured column to products table
ALTER TABLE products ADD COLUMN featured BOOLEAN DEFAULT FALSE AFTER status;

-- Update some existing products as featured (optional)
-- UPDATE products SET featured = TRUE WHERE product_id IN (SELECT product_id FROM (SELECT product_id FROM products WHERE status = 'in_stock' ORDER BY RAND() LIMIT 8) temp);
