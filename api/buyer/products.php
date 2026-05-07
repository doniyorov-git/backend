<?php
require_once '../config/db.php';
requireRole(['buyer']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get active products with seller info
    $stmt = $pdo->query("
        SELECT p.*, s.name as seller_name, s.phone as seller_phone 
        FROM products p
        JOIN users s ON p.seller_id = s.id
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC
    ");
    $products = $stmt->fetchAll();
    sendJson(['success' => true, 'data' => $products]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
