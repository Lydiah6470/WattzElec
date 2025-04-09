<?php
session_start();
include 'includes/db.php';

// Clear the session
session_unset();
session_destroy();

// Clear the "Remember Me" cookie
if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
    $user_id = intval($_COOKIE['user_id']);

    // Remove the token from the database
    $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
    $stmt->execute([$user_id]);

    // Delete the cookies
    setcookie('remember_token', '', time() - 3600, "/", "", true, true);
    setcookie('user_id', '', time() - 3600, "/", "", true, true);
}

// Redirect to the login page
header("Location: login.php");
exit;
?>