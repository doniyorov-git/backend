<?php
require_once '../config/db.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all orders for charts
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
    $orders = $stmt->fetchAll();
    sendJson(['success' => true, 'data' => $orders]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
