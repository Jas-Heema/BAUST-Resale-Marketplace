<?php
require_once(__DIR__ . '/../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$name = trim($_POST['name']);
$student_id = trim($_POST['student_id']);
$department = trim($_POST['department']);
$batch = trim($_POST['batch']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

if ($password !== $confirm) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match']);
    exit();
}

// Check existing user
$stmt = $pdo->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
$stmt->execute([$student_id, $email]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'Student ID or email already exists']);
    exit();
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, student_id, department, batch, email, password) VALUES (?,?,?,?,?,?)");
if ($stmt->execute([$name, $student_id, $department, $batch, $email, $hashed])) {
    echo json_encode(['success' => true, 'message' => 'Registration successful! Please login.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed']);
}
?>