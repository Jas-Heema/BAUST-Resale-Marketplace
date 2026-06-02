<?php
require_once(__DIR__ . '/../config/db.php');

$id = (int)$_GET['id'];
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'Invalid product ID']); exit(); }

$stmt = $pdo->prepare("SELECT p.*, u.name as seller_name, u.id as seller_id, u.rating as seller_rating FROM products p JOIN users u ON p.seller_id = u.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { http_response_code(404); echo json_encode(['error'=>'Product not found']); exit(); }

$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$id]);

$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
$imgStmt->execute([$id]);
$product['images'] = $imgStmt->fetchAll();
$product['primary_image'] = !empty($product['images']) ? $product['images'][0]['image_url'] : 'default.jpg';
$product['current_user_id'] = $_SESSION['user_id'] ?? 0;

echo json_encode($product);
?>