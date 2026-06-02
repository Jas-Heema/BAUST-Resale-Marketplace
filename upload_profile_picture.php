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

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit();
}

$file = $_FILES['profile_picture'];
$allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Only JPG, PNG, GIF, WEBP images are allowed']);
    exit();
}

if ($file['size'] > 2 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large (max 2MB)']);
    exit();
}

$upload_dir = __DIR__ . '/../assets/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check if directory is writable
if (!is_writable($upload_dir)) {
    chmod($upload_dir, 0777);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$new_filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
$destination = $upload_dir . $new_filename;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    // Delete old profile picture if not default
    $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $old = $stmt->fetchColumn();
    if ($old && $old !== 'default.jpg' && file_exists($upload_dir . $old)) {
        unlink($upload_dir . $old);
    }
    
    $update = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $update->execute([$new_filename, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Profile picture updated', 'filename' => $new_filename]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload file. Check folder permissions.']);
}
exit();
?>