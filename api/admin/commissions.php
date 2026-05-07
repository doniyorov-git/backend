<?php
require_once '../config/db.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT o.*, 
               b.name as buyer_name, 
               s.name as seller_name,
               c.contract_number,
               c.signed_at as contract_signed_at
        FROM orders o
        JOIN users b ON o.buyer_id = b.id
        JOIN users s ON o.seller_id = s.id
        LEFT JOIN contract_signatures c ON c.order_id = o.id AND c.contract_type = 'buyer_order'
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();
    sendJson(['success' => true, 'data' => $orders]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id']) || empty($data['action'])) {
        sendJson(['success' => false, 'message' => 'Invalid data'], 400);
    }
    
    if ($data['action'] === 'confirm_comm') {
        $stmt = $pdo->prepare("UPDATE orders SET comm_status = 'paid', status = 'paid' WHERE id = ?");
        $stmt->execute([$data['id']]);
        sendJson(['success' => true]);
    } else {
        sendJson(['success' => false, 'message' => 'Unknown action'], 400);
    }
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
