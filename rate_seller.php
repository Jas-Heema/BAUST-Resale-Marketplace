<?php
require_once(__DIR__ . '/../config/db.php');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$order_id = (int)$_POST['order_id'];
$seller_id = (int)$_POST['seller_id'];
$product_id = (int)$_POST['product_id'];
$rating = (int)$_POST['rating'];
$comment = trim($_POST['comment'] ?? '');

if ($rating < 1 || $rating > 5) { http_response_code(400); echo json_encode(['error'=>'Rating must be 1-5']); exit(); }

$stmt = $pdo->prepare("SELECT buyer_id, status FROM orders WHERE id = ? AND product_id = ? AND seller_id = ?");
$stmt->execute([$order_id, $product_id, $seller_id]);
$order = $stmt->fetch();
if (!$order || $order['buyer_id'] != $_SESSION['user_id']) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit(); }
if ($order['status'] !== 'accepted') { http_response_code(400); echo json_encode(['error'=>'You can only rate after the offer is accepted']); exit(); }

$check = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND reviewer_id = ? AND reviewee_id = ?");
$check->execute([$product_id, $_SESSION['user_id'], $seller_id]);
if ($check->fetch()) { http_response_code(400); echo json_encode(['error'=>'You already rated this seller']); exit(); }

$stmt = $pdo->prepare("INSERT INTO reviews (product_id, reviewer_id, reviewee_id, rating, comment) VALUES (?,?,?,?,?)");
$stmt->execute([$product_id, $_SESSION['user_id'], $seller_id, $rating, $comment]);
$pdo->prepare("UPDATE users SET rating = (SELECT AVG(rating) FROM reviews WHERE reviewee_id = ?), total_reviews = total_reviews + 1 WHERE id = ?")->execute([$seller_id, $seller_id]);

echo json_encode(['success'=>true]);
?>