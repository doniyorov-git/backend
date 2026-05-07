<?php
require_once '../config/db.php';
requireRole(['seller']);

$sellerId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT o.*, b.name as buyer_name, b.phone as buyer_phone, b.inn as buyer_inn
        FROM orders o
        JOIN users b ON o.buyer_id = b.id
        WHERE o.seller_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$sellerId]);
    $orders = $stmt->fetchAll();
    
    // Fetch items for each order
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
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle status update and file upload
    $orderId = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$orderId || !$status) {
        $data = json_decode(file_get_contents('php://input'), true);
        $orderId = $data['id'] ?? null;
        $status = $data['status'] ?? null;
    }

    if (!$orderId || !$status) {
        sendJson(['success' => false, 'message' => 'Missing parameters'], 400);
    }
    
    $dispatchReport = null;
    if ($status === 'dispatched' && isset($_FILES['dispatch_report']) && $_FILES['dispatch_report']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['dispatch_report']['tmp_name'];
        $ext = pathinfo($_FILES['dispatch_report']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('rep_') . '.' . $ext;
        $dest = '../../uploads/reports/' . $filename;
        if (move_uploaded_file($tmpName, $dest)) {
            $dispatchReport = 'uploads/reports/' . $filename;
        }
    }
    
    if ($dispatchReport) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, dispatch_report = ? WHERE id = ? AND seller_id = ?");
        $stmt->execute([$status, $dispatchReport, $orderId, $sellerId]);
    } else {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?");
        $stmt->execute([$status, $orderId, $sellerId]);
        
        if ($status === 'seller_paid_comm') {
            $stmt = $pdo->prepare("UPDATE orders SET comm_status = 'pending_admin' WHERE id = ? AND seller_id = ?");
            $stmt->execute([$orderId, $sellerId]);
        }
    }
    
    sendJson(['success' => true]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
