<?php
require_once '../config/db.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT o.*, cs.contract_number, cs.signed_at as contract_date
        FROM orders o
        LEFT JOIN contract_signatures cs ON cs.order_id = o.id AND cs.contract_type = 'buyer_order'
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();
    sendJson(['success' => true, 'data' => $orders]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
