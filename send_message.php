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

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty']);
    exit();
}

try {
    // Get the seller from the product
    $stmt = $pdo->prepare("SELECT seller_id, title FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit();
    }
    
    $receiver_id = $product['seller_id'];
    $sender_id = $_SESSION['user_id'];
    
    // Don't allow sending message to yourself
    if ($receiver_id == $sender_id) {
        http_response_code(400);
        echo json_encode(['error' => 'You cannot message yourself']);
        exit();
    }
    
    // Insert message
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, product_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $product_id, $message]);
    
    echo json_encode(['success' => true, 'message' => 'Message sent']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
exit();
?>