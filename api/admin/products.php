<?php
require_once '../config/db.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all active products with seller info
    $stmt = $pdo->query("
        SELECT p.*, s.name as seller_name, s.phone as seller_phone, s.inn as seller_inn
        FROM products p
        LEFT JOIN users s ON p.seller_id = s.id
        ORDER BY p.created_at DESC
    ");
    $products = $stmt->fetchAll();
    sendJson(['success' => true, 'data' => $products]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
