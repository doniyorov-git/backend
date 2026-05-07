<?php
require_once '../config/db.php';
requireRole(['seller']);

$sellerId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->execute([$sellerId]);
    $products = $stmt->fetchAll();
    sendJson(['success' => true, 'data' => $products]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = uniqid('p_');
    $name = $_POST['name'] ?? '';
    $sku = $_POST['sku'] ?? '';
    $category = $_POST['category'] ?? '';
    $region = $_POST['region'] ?? '';
    $model = $_POST['model'] ?? '';
    $price = $_POST['price'] ?? 0;
    $unit = $_POST['unit'] ?? 'dona';
    
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '.' . $ext;
        $uploadDir = dirname(__DIR__) . '/uploads/products/';
        
        // Ensure directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($tmpName, $dest)) {
            $imagePath = 'uploads/products/' . $filename;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO products (id, seller_id, name, sku, category, region, model, price, unit, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $sellerId, $name, $sku, $category, $region, $model, $price, $unit, $imagePath]);
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    sendJson(['success' => true, 'data' => $stmt->fetch()]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
