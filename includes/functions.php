<?php
// File: includes/functions.php

function getCategoriesFromDatabase() {
    global $conn; // Access the PDO connection from db.php

    try {
        $query = "SELECT id, name FROM category";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch results as an associative array
    } catch (PDOException $e) {
        echo "Error fetching categories: " . $e->getMessage();
        return [];
    }
}

function getFeaturedProductsFromDatabase() {
    global $conn;

    try {
        $query = "SELECT id, name, image_url, description, price FROM products WHERE featured = 1 LIMIT 8";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching featured products: " . $e->getMessage();
        return [];
    }
}

function getOtherProductsFromDatabase() {
    global $conn;

    try {
        $query = "SELECT id, name, image_url, description, price FROM products WHERE featured = 0 LIMIT 8";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching other products: " . $e->getMessage();
        return [];
    }
}
?>