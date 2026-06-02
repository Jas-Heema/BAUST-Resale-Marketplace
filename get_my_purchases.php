<?php
require_once(__DIR__ . '/../config/db.php');
requireLogin();

$stmt = $pdo->prepare("SELECT o.*, p.title as product_title, (SELECT COUNT(*) FROM reviews WHERE product_id = o.product_id AND reviewer_id = o.buyer_id AND reviewee_id = o.seller_id) as is_rated FROM orders o JOIN products p ON o.product_id = p.id WHERE o.buyer_id = ? ORDER BY o.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
echo json_encode($stmt->fetchAll());
?>