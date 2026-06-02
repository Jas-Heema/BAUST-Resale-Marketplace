<?php
require_once(__DIR__ . '/../config/db.php');

try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    echo json_encode($categories);
} catch (PDOException $e) {
    // Return empty array and log error (don't expose to frontend)
    error_log("get_categories.php error: " . $e->getMessage());
    echo json_encode([]);
}
?>