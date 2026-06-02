<?php
require_once(__DIR__ . '/../config/db.php');

$requested_id = (int)($_GET['id'] ?? 0);

if ($requested_id > 0) {
    $stmt = $pdo->prepare("SELECT id, name, department, batch, profile_picture, rating, total_reviews, created_at FROM users WHERE id = ?");
    $stmt->execute([$requested_id]);
    $user = $stmt->fetch();
    echo json_encode($user ? ['user' => $user] : ['error' => 'User not found']);
    exit();
}

if (!isset($_SESSION['user_id'])) { echo json_encode(['loggedIn' => false]); exit(); }

$stmt = $pdo->prepare("SELECT id, name, student_id, department, batch, email, profile_picture, rating, total_reviews, created_at, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
echo json_encode(['loggedIn' => true, 'user' => $stmt->fetch()]);
?>