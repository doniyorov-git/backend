<?php
require_once '../config/db.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all orders for charts
    $stmt = $pdo->query("
        SELECT o.*,
               b.name as buyer_name, b.phone as buyer_phone, b.inn as buyer_inn,
               s.name as seller_name, s.phone as seller_phone, s.inn as seller_inn, s.bank_account, s.mfo
        FROM orders o
        JOIN users b ON o.buyer_id = b.id
        JOIN users s ON o.seller_id = s.id
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $stmt2 = $pdo->prepare("
            SELECT oi.*, p.name as product_name, p.unit
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt2->execute([$order['id']]);
        $order['items'] = $stmt2->fetchAll();
    }

    sendJson(['success' => true, 'data' => $orders]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
