<?php
require_once(__DIR__ . '/../config/db.php');
requireLogin();

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT seller_id FROM products WHERE id = ?");
$stmt->execute([$id]);
$prod = $stmt->fetch();
if (!$prod || $prod['seller_id'] != $_SESSION['user_id']) { http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit(); }

$pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
echo json_encode(['success'=>true]);
?>