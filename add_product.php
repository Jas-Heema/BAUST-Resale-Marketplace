<?php
require_once(__DIR__ . '/../config/db.php');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get and validate inputs
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$category_id = intval($_POST['category_id'] ?? 0);
$condition = $_POST['condition'] ?? 'good';
$location = trim($_POST['location'] ?? '');

$errors = [];
if (empty($title)) $errors[] = 'Title is required';
if (empty($description)) $errors[] = 'Description is required';
if ($price <= 0) $errors[] = 'Valid price is required';
if ($category_id <= 0) $errors[] = 'Please select a category';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => implode(', ', $errors)]);
    exit();
}

try {
    // Insert product
    $stmt = $pdo->prepare("INSERT INTO products (seller_id, title, description, price, category_id, `condition`, location, status) VALUES (?,?,?,?,?,?,?, 'available')");
    $stmt->execute([$_SESSION['user_id'], $title, $description, $price, $category_id, $condition, $location]);
    $product_id = $pdo->lastInsertId();

    // Handle image uploads
    $upload_dir = __DIR__ . '/../assets/uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image_inserted = false;
    $upload_errors = [];
    
    // Check if images were uploaded
    if (isset($_FILES['images']) && !empty($_FILES['images']['tmp_name'][0])) {
        $is_primary = true;
        
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp_name) {
            // Skip empty uploads
            if (empty($tmp_name) || $_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($ext, $allowed)) {
                $upload_errors[] = $_FILES['images']['name'][$i] . ' has invalid format (use JPG, PNG, GIF)';
                continue;
            }
            
            // Check file size (max 5MB)
            if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
                $upload_errors[] = $_FILES['images']['name'][$i] . ' is too large (max 5MB)';
                continue;
            }
            
            $new_name = uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_name;
            
            if (move_uploaded_file($tmp_name, $destination)) {
                $stmt_img = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
                $stmt_img->execute([$product_id, $new_name, $is_primary ? 1 : 0]);
                $is_primary = false;
                $image_inserted = true;
            } else {
                $upload_errors[] = 'Failed to upload ' . $_FILES['images']['name'][$i];
            }
        }
    }
    
    // If no image was uploaded, insert a placeholder
    if (!$image_inserted) {
        $stmt_img = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, 'default.jpg', 1)");
        $stmt_img->execute([$product_id]);
    }
    
    $response = ['success' => true, 'product_id' => $product_id, 'message' => 'Product added successfully'];
    if (!empty($upload_errors)) {
        $response['image_warnings'] = $upload_errors;
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>