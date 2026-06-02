<?php
require_once(__DIR__ . '/../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit(); }

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
if (empty($email) || empty($password)) { http_response_code(400); echo json_encode(['error'=>'Email and password required']); exit(); }

$stmt = $pdo->prepare("SELECT id, name, email, password, role, is_blocked FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) { http_response_code(401); echo json_encode(['error'=>'Invalid email or password']); exit(); }
if ($user['is_blocked']) { http_response_code(403); echo json_encode(['error'=>'Your account has been blocked']); exit(); }

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['role'] = $user['role'];

echo json_encode(['success' => true, 'user' => ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]]);
?>