<?php
// File: includes/functions.php

function getCategoriesFromDatabase() {
    global $conn; // Access the PDO connection from db.php

    try {
        $query = "SELECT c.category_id, c.name, c.description, c.image, 
                         (SELECT COUNT(*) FROM products p 
                          JOIN subcategories s ON p.subcategory_id = s.subcategory_id 
                          WHERE s.category_id = c.category_id AND p.status = 'in_stock') as product_count
                  FROM category c
                  ORDER BY c.name ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch results as an associative array
    } catch (PDOException $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        return [];
    }
}

function getFeaturedProductsFromDatabase() {
    global $conn;

    try {
        $query = "SELECT p.product_id, p.name, p.description, p.price, 
                         p.stock_quantity, p.status, p.final_price, p.discount,
                         p.image_1, s.category_id, c.name as category_name, s.name as subcategory_name
                  FROM products p
                  LEFT JOIN subcategories s ON p.subcategory_id = s.subcategory_id
                  LEFT JOIN category c ON s.category_id = c.category_id
                  WHERE p.status = 'in_stock' AND p.featured = 1
                  ORDER BY p.created_at DESC LIMIT 6";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching featured products: " . $e->getMessage());
        return [];
    }
}

function getOtherProductsFromDatabase() {
    global $conn;

    try {
        $query = "SELECT p.product_id, p.name, p.description, p.price,
                         p.stock_quantity, p.status, p.final_price, p.discount,
                         p.image_1, s.category_id, c.name as category_name
                  FROM products p
                  LEFT JOIN subcategories s ON p.subcategory_id = s.subcategory_id
                  LEFT JOIN category c ON s.category_id = c.category_id
                  WHERE p.status = 'in_stock' AND p.featured = 0
                  ORDER BY RAND() LIMIT 8";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching other products: " . $e->getMessage());
        return [];
    }
}
?>