<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if ($category_id <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT s.subcategory_id, s.name, COUNT(p.product_id) as product_count
        FROM subcategories s
        LEFT JOIN products p ON s.subcategory_id = p.subcategory_id
        WHERE s.category_id = ?
        GROUP BY s.subcategory_id, s.name
        ORDER BY product_count DESC, name ASC
    ");
    
    $stmt->execute([$category_id]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($subcategories);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
