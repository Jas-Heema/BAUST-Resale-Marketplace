<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_clean();
header('Content-Type: application/json');

require_once(__DIR__ . '/../config/db.php');

$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT p.*, u.name as seller_name,
        COALESCE((SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1), 'default.jpg') as primary_image
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE p.status = 'available'";
$params = [];

// Apply category filter
if ($category > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    echo json_encode($products);
} catch (PDOException $e) {
    error_log("get_products.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
exit();
?>