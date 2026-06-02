<?php
require_once 'config/db.php';

$name = 'Administrator';
$student_id = 'ADMIN001';
$department = 'Admin';
$batch = 'N/A';
$email = 'admin@baust.com';
$password = 'Admin123';

$hashed = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "Admin already exists. Use email: $email and password: $password";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, student_id, department, batch, email, password, role) VALUES (?, ?, ?, ?, ?, ?, 'admin')");
        $stmt->execute([$name, $student_id, $department, $batch, $email, $hashed]);
        echo "Admin created!<br>Email: $email<br>Password: $password";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>