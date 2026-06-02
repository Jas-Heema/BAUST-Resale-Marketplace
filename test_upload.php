<?php
$upload_dir = __DIR__ . '/assets/uploads/';

echo "<h2>Image Upload Debug</h2>";
echo "Upload directory: " . $upload_dir . "<br>";
echo "Directory exists: " . (file_exists($upload_dir) ? 'YES' : 'NO') . "<br>";
echo "Directory writable: " . (is_writable($upload_dir) ? 'YES' : 'NO') . "<br>";

if (!file_exists($upload_dir)) {
    echo "Creating directory...<br>";
    mkdir($upload_dir, 0777, true);
    echo "Directory created: " . (file_exists($upload_dir) ? 'YES' : 'NO') . "<br>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Upload Results:</h3>";
    if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['test_image']['name'], PATHINFO_EXTENSION);
        $new_name = 'test_' . time() . '.' . $ext;
        $destination = $upload_dir . $new_name;
        
        if (move_uploaded_file($_FILES['test_image']['tmp_name'], $destination)) {
            echo "✅ Upload successful! File saved as: " . $new_name . "<br>";
            echo "Full path: " . $destination . "<br>";
            echo "<img src='assets/uploads/" . $new_name . "' width='200'>";
        } else {
            echo "❌ Move failed. Error: " . error_get_last()['message'];
        }
    } else {
        echo "❌ No file uploaded or upload error. Code: " . ($_FILES['test_image']['error'] ?? 'N/A');
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_image" accept="image/*">
    <button type="submit">Test Upload</button>
</form>