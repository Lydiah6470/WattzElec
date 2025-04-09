<?php
session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }

include 'includes/db.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$user_id]);

header("Location: users.php");
exit;
?>