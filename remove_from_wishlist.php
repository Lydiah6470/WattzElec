<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ($product_id > 0 && isset($_SESSION['wishlist'])) {
        // Remove the product ID from the wishlist
        $_SESSION['wishlist'] = array_diff($_SESSION['wishlist'], [$product_id]);
    }
}

header("Location: wishlist.php");
exit;
?>