<?php
require_once(__DIR__ . '/../config/db.php');
requireLogin();

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT o.*, p.seller_id, p.id as product_id FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = ?");
$stmt->execute([$id]);
$offer = $stmt->fetch();
if (!$offer || $offer['seller_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$pdo->prepare("UPDATE orders SET status = 'accepted' WHERE id = ?")->execute([$id]);
$pdo->prepare("UPDATE products SET status = 'sold' WHERE id = ?")->execute([$offer['product_id']]);
$pdo->prepare("UPDATE orders SET status = 'rejected' WHERE product_id = ? AND id != ? AND status = 'pending'")->execute([$offer['product_id'], $id]);
$pdo->prepare("INSERT INTO notifications (user_id, type, message, related_id) VALUES (?, 'offer_accepted', ?, ?)")->execute([$offer['buyer_id'], "Your offer has been accepted", $offer['product_id']]);

echo json_encode(['success' => true]);
?>