<?php
require_once(__DIR__ . '/../config/db.php');
requireLogin();

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT o.*, p.seller_id FROM orders o JOIN products p ON o.product_id = p.id WHERE o.id = ?");
$stmt->execute([$id]);
$offer = $stmt->fetch();
if (!$offer || $offer['seller_id'] != $_SESSION['user_id']) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit(); }

$pdo->prepare("UPDATE orders SET status = 'rejected' WHERE id = ?")->execute([$id]);
echo json_encode(['success'=>true]);
?>