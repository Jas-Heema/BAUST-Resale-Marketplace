<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_clean();
header('Content-Type: application/json');

require_once(__DIR__ . '/../config/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               p.title as product_title, 
               u.name as buyer_name
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN users u ON o.buyer_id = u.id
        WHERE o.seller_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $offers = $stmt->fetchAll();
    
    // Return empty array if no offers, not an error
    echo json_encode($offers);
    
} catch (PDOException $e) {
    error_log("get_my_offers.php error: " . $e->getMessage());
    echo json_encode([]); // Return empty array on error
}
exit();
?>