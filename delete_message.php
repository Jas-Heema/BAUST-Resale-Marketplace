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

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$message_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Message ID required']);
    exit();
}

try {
    // Verify the logged-in user is the receiver of this message
    $stmt = $pdo->prepare("SELECT receiver_id FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $msg = $stmt->fetch();
    
    if (!$msg) {
        http_response_code(404);
        echo json_encode(['error' => 'Message not found']);
        exit();
    }
    
    if ($msg['receiver_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'You can only delete messages you received']);
        exit();
    }
    
    // Delete the message
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
exit();
?>