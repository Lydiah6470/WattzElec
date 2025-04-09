<?php
include 'includes/db.php';

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Fetch subcategories with products in the selected category
$stmt = $conn->prepare("
    SELECT s.id, s.name
    FROM subcategories s
    JOIN products p ON s.id = p.subcategory_id
    WHERE s.category_id = ?
    GROUP BY s.id
");
$stmt->execute([$category_id]);
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($subcategories);
?>