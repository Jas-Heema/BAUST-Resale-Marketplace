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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty']);
    exit();
}

if ($receiver_id === $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['error' => 'You cannot reply to yourself']);
    exit();
}

try {
    // Check receiver exists
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->execute([$receiver_id]);
    $receiver = $stmt->fetch();
    
    if (!$receiver) {
        http_response_code(404);
        echo json_encode(['error' => 'Receiver not found']);
        exit();
    }
    
    // Handle product_id: if 0, set to NULL (by using null variable)
    $product_id_value = ($product_id > 0) ? $product_id : null;
    
    // If product_id is provided and > 0, verify it exists
    if ($product_id_value !== null) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$product_id_value]);
        if (!$stmt->fetch()) {
            $product_id_value = null;
        }
    }
    
    // Insert reply - using NULL for product_id if not valid
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, product_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $receiver_id, $product_id_value, $message]);
    
    echo json_encode(['success' => true, 'message' => 'Reply sent']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
exit();
?>