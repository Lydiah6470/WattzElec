<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    try {
        // Handle image upload
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $target_dir = "uploads/categories/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $new_filename = uniqid() . "." . $ext;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image = "uploads/categories/" . $new_filename;
                }
            }
        }
        
        // Insert category into database
        $stmt = $conn->prepare("INSERT INTO category (name, description, image) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $image]);
        
        $_SESSION['success_message'] = "Category added successfully!";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error adding category: " . $e->getMessage();
    }
}

header("Location: inventory.php");
exit();
