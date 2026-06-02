<?php
require_once(__DIR__ . '/../config/db.php');

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

try {
    // Get reviews where this user is the reviewee (received reviews)
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as reviewer_name, p.title as product_title
        FROM reviews r
        JOIN users u ON r.reviewer_id = u.id
        LEFT JOIN products p ON r.product_id = p.id
        WHERE r.reviewee_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $reviews = $stmt->fetchAll();

    echo json_encode($reviews);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load reviews']);
}
?>