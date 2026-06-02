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

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT w.*, u.name as issued_by_name 
        FROM warnings w
        JOIN users u ON w.issued_by = u.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $warnings = $stmt->fetchAll();
    
    echo json_encode($warnings);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load warnings']);
}
exit();
?>