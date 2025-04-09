<php?
$query = "
    SELECT p.id, p.name, p.image_url, p.image_url_2, p.image_url_3, p.price, p.discount, p.stock, p.description, p.subcategory_id, c.name AS category_name, s.name AS subcategory_name
    FROM products p
    JOIN subcategories s ON p.subcategory_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE p.id = ?
";
?>