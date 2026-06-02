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

try {
    // Get warnings as notifications
    $stmt = $pdo->prepare("
        SELECT w.*, 'warning' as type, u.name as from_user
        FROM warnings w
        JOIN users u ON w.issued_by = u.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
    
    echo json_encode($notifications);
} catch (Exception $e) {
    echo json_encode([]);
}
exit();
?>