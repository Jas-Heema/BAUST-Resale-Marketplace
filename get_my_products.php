<?php
require_once(__DIR__ . '/../config/db.php');
requireLogin();

$stmt = $pdo->prepare("SELECT p.*, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image FROM products p WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
echo json_encode($stmt->fetchAll());
?>