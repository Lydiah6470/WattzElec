<?php
require_once 'includes/db.php';

// Handle file upload
function handleFileUpload() {
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/subcategories/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            return '/uploads/subcategories/' . $new_filename; // Added leading slash
        }
    }
    return '';
}

// Handle POST requests (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $action = $_POST['action'] ?? 'add';

    try {
        if ($action === 'edit') {
            $subcategory_id = (int)$_POST['subcategory_id'];
            $image_path = handleFileUpload();
            
            if ($image_path) {
                // Get old image to delete
                $stmt = $conn->prepare("SELECT image FROM subcategories WHERE subcategory_id = ?");
                $stmt->execute([$subcategory_id]);
                $old_image = $stmt->fetch(PDO::FETCH_COLUMN);
                
                // Delete old image if it exists
                if ($old_image && file_exists('../' . $old_image)) {
                    unlink('../' . $old_image);
                }
                
                // Update with new image
                $stmt = $conn->prepare("UPDATE subcategories SET name = ?, description = ?, image = ? WHERE subcategory_id = ?");
                $stmt->execute([$name, $description, $image_path, $subcategory_id]);
            } else {
                // If no new image, keep existing image
                $stmt = $conn->prepare("UPDATE subcategories SET name = ?, description = ? WHERE subcategory_id = ?");
                $stmt->execute([$name, $description, $subcategory_id]);
            }
        } else {
            // Add new subcategory
            $image_path = handleFileUpload();
            $stmt = $conn->prepare("INSERT INTO subcategories (name, description, image, category_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $image_path, $category_id]);
        }
        
        header("Location: inventory.php?category=" . $category_id);
        exit();
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}

// Handle GET requests (Delete)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'delete') {
    $subcategory_id = (int)$_GET['id'];
    $category_id = (int)$_GET['category_id'];

    try {
        // First, get the image path to delete the file
        $stmt = $conn->prepare("SELECT image FROM subcategories WHERE subcategory_id = ?");
        $stmt->execute([$subcategory_id]);
        $image = $stmt->fetch(PDO::FETCH_COLUMN);

        // Delete the image file if it exists
        if ($image && file_exists('../' . $image)) {
            unlink('../' . $image);
        }

        // Delete the subcategory from database
        $stmt = $conn->prepare("DELETE FROM subcategories WHERE subcategory_id = ?");
        $stmt->execute([$subcategory_id]);

        header("Location: inventory.php?category=" . $category_id);
        exit();
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}
