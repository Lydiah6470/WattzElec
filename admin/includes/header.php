<?php
// session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Wattz Electronics</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="admin-dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Wattz Admin</h3>
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="orders.php">Orders</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="promotions.php">Promotions</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="notifications.php">Notifications</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">