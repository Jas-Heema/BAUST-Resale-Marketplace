<?php
require_once(__DIR__ . '/../config/db.php');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$product_id = (int)$_POST['product_id'];
$offer_price = floatval($_POST['offer_price']);
$message = trim($_POST['message']);
$buyer_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT seller_id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) { http_response_code(404); echo json_encode(['error'=>'Product not found']); exit(); }
if ($product['seller_id'] == $buyer_id) { http_response_code(400); echo json_encode(['error'=>'You cannot offer on your own product']); exit(); }

$stmt = $pdo->prepare("INSERT INTO orders (product_id, buyer_id, seller_id, offer_price, message) VALUES (?,?,?,?,?)");
$stmt->execute([$product_id, $buyer_id, $product['seller_id'], $offer_price, $message]);
$pdo->prepare("INSERT INTO notifications (user_id, type, message, related_id) VALUES (?, 'offer', ?, ?)")->execute([$product['seller_id'], "New offer of ৳$offer_price on your product", $product_id]);

echo json_encode(['success'=>true]);
?>