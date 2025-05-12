USE wattzelec;

-- Drop specifications and image columns
ALTER TABLE products 
DROP COLUMN IF EXISTS specifications,
DROP COLUMN IF EXISTS image;

-- Add three image columns
ALTER TABLE products
ADD COLUMN image_1 VARCHAR(255),
ADD COLUMN image_2 VARCHAR(255),
ADD COLUMN image_3 VARCHAR(255);
