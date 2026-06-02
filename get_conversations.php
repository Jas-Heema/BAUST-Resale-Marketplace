<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_clean();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $sql = "SELECT DISTINCT 
            CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as other_user_id,
            u.name as other_user_name,
            u.profile_picture,
            (SELECT message FROM messages WHERE (sender_id = ? AND receiver_id = other_user_id) OR (sender_id = other_user_id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM messages WHERE (sender_id = ? AND receiver_id = other_user_id) OR (sender_id = other_user_id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_time
            FROM messages m
            JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id) AND u.id != ?
            WHERE m.sender_id = ? OR m.receiver_id = ?
            ORDER BY last_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
    $conversations = $stmt->fetchAll();
    
    echo json_encode($conversations);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load conversations: ' . $e->getMessage()]);
}
exit();
?>