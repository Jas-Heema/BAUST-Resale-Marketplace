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

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// If JSON decoding failed, try POST data
if (!$data) {
    $reported_user_id = isset($_POST['reported_user_id']) ? (int)$_POST['reported_user_id'] : 0;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
} else {
    $reported_user_id = isset($data['reported_user_id']) ? (int)$data['reported_user_id'] : 0;
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    $message = isset($data['message']) ? trim($data['message']) : '';
}

// Validate inputs
if ($reported_user_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit();
}

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide a reason for the complaint']);
    exit();
}

// Prevent self-reporting
if ($reported_user_id == $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['error' => 'You cannot report yourself']);
    exit();
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
    $stmt->execute([$reported_user_id]);
    $reported_user = $stmt->fetch();
    
    if (!$reported_user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }
    
    // Check if product exists (if product_id provided)
    if ($product_id > 0) {
        $stmt = $pdo->prepare("SELECT id, title FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        if (!$stmt->fetch()) {
            $product_id = null; // Set to null if product doesn't exist
        }
    } else {
        $product_id = null;
    }
    
    // Insert complaint
    $stmt = $pdo->prepare("INSERT INTO complaints (complainant_id, reported_user_id, product_id, message, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
    $result = $stmt->execute([$_SESSION['user_id'], $reported_user_id, $product_id, $message]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Complaint filed successfully. Admin will review.']);
    } else {
        throw new Exception('Failed to insert complaint');
    }
    
} catch (PDOException $e) {
    error_log("Complaint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Complaint error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
exit();
?>