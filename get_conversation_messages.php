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

$withUser = isset($_GET['with_user']) ? (int)$_GET['with_user'] : 0;
$myId = $_SESSION['user_id'];

if ($withUser <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$myId, $withUser, $withUser, $myId]);
    $messages = $stmt->fetchAll();
    
    // Mark messages from the other user as read
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")->execute([$withUser, $myId]);
    
    echo json_encode($messages);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load messages: ' . $e->getMessage()]);
}
exit();
?>