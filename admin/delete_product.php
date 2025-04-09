<?php
session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }

include 'includes/db.php'; // Include your database connection

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("DELETE FROM products WHERE id=?");
$stmt->execute([$product_id]);

header("Location: products.php");
exit;
?>