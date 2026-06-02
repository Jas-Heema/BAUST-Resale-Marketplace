<?php
require_once(__DIR__ . '/../config/db.php');

$stmt = $pdo->query("
    SELECT p.*, u.name as seller_name,
    COALESCE((SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1), 'default.jpg') as primary_image
    FROM products p
    JOIN users u ON p.seller_id = u.id
    WHERE p.status = 'available'
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($products, JSON_PRETTY_PRINT);