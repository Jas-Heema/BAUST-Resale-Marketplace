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

$name = trim($_POST['name'] ?? '');
$department = trim($_POST['department'] ?? '');
$batch = trim($_POST['batch'] ?? '');
$email = trim($_POST['email'] ?? '');

if (empty($name) || empty($department) || empty($batch) || empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET name=?, department=?, batch=?, email=? WHERE id=?");
    $stmt->execute([$name, $department, $batch, $email, $_SESSION['user_id']]);
    $_SESSION['user_name'] = $name;
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
exit();
?>