<?php
// Fetch categories for the dropdown
if (!isset($conn)) {
    require_once __DIR__ . '/db.php';
}
$stmt = $conn->query("SELECT * FROM category ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Wattz Electronics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .sidebar {
            background: #2c3e50;
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        .sidebar h3 {
            color: #3498db;
            margin-bottom: 30px;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar li {
            margin-bottom: 10px;
        }
        .sidebar a {
            color: #ecf0f1;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .sidebar a:hover {
            background: #34495e;
            color: #3498db;
        }
        .sidebar .active {
            background: #34495e;
            color: #3498db;
        }
        .category-dropdown {
            margin: 10px 0;
            position: relative;
        }
        .category-dropdown .dropdown-toggle {
            width: 100%;
            text-align: left;
            background: #34495e;
            border: none;
            color: #ecf0f1;
            padding: 10px;
            border-radius: 5px;
        }
        .category-dropdown .dropdown-menu {
            width: 100%;
            background: #2c3e50;
            border: 1px solid #34495e;
        }
        .category-dropdown .dropdown-item {
            color: #ecf0f1;
            padding: 8px 15px;
        }
        .category-dropdown .dropdown-item:hover {
            background: #34495e;
            color: #3498db;
        }
    </style>
</head>
<body>
<div class="admin-dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Wattz Admin</h3>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li>
                <div class="category-dropdown">
                    <button class="btn dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-box me-2"></i>Inventory
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="categoryDropdown">
                        <?php foreach ($categories as $category): ?>
                            <li><a class="dropdown-item" href="inventory.php?category=<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="reviews.php">Reviews</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>