<?php
include 'includes/db.php';

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

$stmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ?");
$stmt->execute([$category_id]);
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($subcategories);
?>