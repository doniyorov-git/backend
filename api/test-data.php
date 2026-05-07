<?php
// Test data insertion script
session_start();
require_once 'config/db.php';

// Allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    // Insert test sellers
    $sellers = [
        ['id' => 'u_seller1', 'name' => 'Seller 1 Shop', 'phone' => '998901234567', 'inn' => '123456789'],
        ['id' => 'u_seller2', 'name' => 'Seller 2 Shop', 'phone' => '998902234567', 'inn' => '987654321'],
    ];
    
    foreach ($sellers as $seller) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$seller['id']]);
        if (!$stmt->fetch()) {
            $stmt2 = $pdo->prepare("INSERT INTO users (id, name, phone, password, role, inn, bank_account, mfo, status) VALUES (?, ?, ?, '123456', 'seller', ?, '12345678901234567890', '00000', 'active')");
            $stmt2->execute([$seller['id'], $seller['name'], $seller['phone'], $seller['inn']]);
        }
    }
    
    // Insert test buyer
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute(['u_buyer1']);
    if (!$stmt->fetch()) {
        $stmt2 = $pdo->prepare("INSERT INTO users (id, name, phone, password, role, status) VALUES (?, ?, ?, '123456', 'buyer', 'active')");
        $stmt2->execute(['u_buyer1', 'Test Buyer', '998903234567']);
    }
    
    // Insert test products
    $products = [
        [
            'id' => 'p_test1',
            'seller_id' => 'u_seller1',
            'name' => 'Kompyuter Monitori',
            'sku' => 'MON-001',
            'category' => 'electronics',
            'price' => 500000,
            'region' => 'Toshkent shahri',
            'model' => 'realization',
            'image' => 'uploads/products/monitor.jpg'
        ],
        [
            'id' => 'p_test2',
            'seller_id' => 'u_seller1',
            'name' => 'Ofis Stoli',
            'sku' => 'DSK-001',
            'category' => 'furniture',
            'price' => 1000000,
            'region' => 'Toshkent viloyati',
            'model' => 'prepayment',
            'image' => 'uploads/products/desk.jpg'
        ],
        [
            'id' => 'p_test3',
            'seller_id' => 'u_seller2',
            'name' => 'Qurilish Mixi',
            'sku' => 'BLD-001',
            'category' => 'building',
            'price' => 250000,
            'region' => 'Samarqand',
            'model' => 'realization',
            'image' => 'uploads/products/mixer.jpg'
        ],
    ];
    
    foreach ($products as $product) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$product['id']]);
        if (!$stmt->fetch()) {
            $stmt2 = $pdo->prepare("INSERT INTO products (id, seller_id, name, sku, category, price, region, model, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt2->execute([
                $product['id'],
                $product['seller_id'],
                $product['name'],
                $product['sku'],
                $product['category'],
                $product['price'],
                $product['region'],
                $product['model'],
                $product['image']
            ]);
        }
    }
    
    sendJson(['success' => true, 'message' => 'Test data inserted successfully']);
} catch (Exception $e) {
    sendJson(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
}
?>
