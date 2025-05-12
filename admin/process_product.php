<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $status = $_POST['status'];
    $subcategory_id = $_POST['subcategory_id'];
    
    try {
        // Handle image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $target_dir = "uploads/products/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $new_filename = uniqid() . "." . $ext;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image = "uploads/products/" . $new_filename;
                }
            }
        }
        
        // Insert product into database
        $stmt = $conn->prepare("INSERT INTO products (subcategory_id, name, description, price, stock_quantity, status, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$subcategory_id, $name, $description, $price, $stock_quantity, $status, $image]);
        
        $_SESSION['success_message'] = "Product added successfully!";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error adding product: " . $e->getMessage();
    }
}

header("Location: inventory.php?category_id=" . $_POST['category_id'] . "&subcategory_id=" . $subcategory_id);
exit();
