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
    $data = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
    $orderId = $data['id'] ?? null;
    $status = $data['status'] ?? null;

    if (!$orderId || !$status) {
        sendJson(['success' => false, 'message' => 'Missing parameters'], 400);
    }

    $stmt = $pdo->prepare("SELECT status, seller_commission_proof, commission_due_at FROM orders WHERE id = ? AND seller_id = ?");
    $stmt->execute([$orderId, $sellerId]);
    $existingOrder = $stmt->fetch();
    if (!$existingOrder) {
        sendJson(['success' => false, 'message' => 'Buyurtma topilmadi'], 404);
    }
    
    $dispatchReport = null;
    if ($status === 'dispatched' && isset($_FILES['dispatch_report']) && $_FILES['dispatch_report']['error'] === UPLOAD_ERR_OK) {
        $dispatchReport = saveUploadedFile('dispatch_report', 'reports', 'rep');
    }

    $commissionProof = '';
    if ($status === 'seller_paid_comm') {
        $commissionProof = saveUploadedDocument('commission_payment_proof', 'payments', 'seller_comm');
        if (!$commissionProof && empty($existingOrder['seller_commission_proof'])) {
            sendJson(['success' => false, 'message' => 'Komissiya to\'lovini tasdiqlovchi PDF hujjatni yuklang'], 400);
        }
    }
    
    if ($dispatchReport) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, dispatch_report = ? WHERE id = ? AND seller_id = ?");
        $stmt->execute([$status, $dispatchReport, $orderId, $sellerId]);
    } else if ($commissionProof) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, seller_commission_proof = ?, comm_status = 'pending_admin', commission_due_at = COALESCE(commission_due_at, DATE_ADD(NOW(), INTERVAL 3 DAY)) WHERE id = ? AND seller_id = ?");
        $stmt->execute([$status, $commissionProof, $orderId, $sellerId]);
        notifyRole($pdo, 'admin', 'Komissiya tasdiq kutmoqda', '#' . $orderId . ' buyurtma komissiyasi tekshiruvga yuborildi.', 'warning', 'admin-comm');
    } else {
        if ($status === 'product_ready' || $status === 'invoice_generated') {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, invoice_generated_at = COALESCE(invoice_generated_at, NOW()), buyer_payment_due_at = COALESCE(buyer_payment_due_at, DATE_ADD(NOW(), INTERVAL 10 DAY)) WHERE id = ? AND seller_id = ?");
            $stmt->execute([$status, $orderId, $sellerId]);
        } elseif ($status === 'trade_closed') {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, commission_due_at = COALESCE(commission_due_at, DATE_ADD(NOW(), INTERVAL 3 DAY)) WHERE id = ? AND seller_id = ?");
            $stmt->execute([$status, $orderId, $sellerId]);
        } else {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?");
            $stmt->execute([$status, $orderId, $sellerId]);
        }
        
        if ($status === 'seller_paid_comm') {
            $stmt = $pdo->prepare("UPDATE orders SET comm_status = 'pending_admin', commission_due_at = COALESCE(commission_due_at, DATE_ADD(NOW(), INTERVAL 3 DAY)) WHERE id = ? AND seller_id = ?");
            $stmt->execute([$orderId, $sellerId]);
            notifyRole($pdo, 'admin', 'Komissiya tasdiq kutmoqda', '#' . $orderId . ' buyurtma komissiyasi tekshiruvga yuborildi.', 'warning', 'admin-comm');
        }
    }

    $stmt = $pdo->prepare("SELECT buyer_id FROM orders WHERE id = ? AND seller_id = ?");
    $stmt->execute([$orderId, $sellerId]);
    $buyerId = $stmt->fetchColumn();
    if ($buyerId) {
        if ($status === 'product_ready') {
            createNotification($pdo, $buyerId, 'Mahsulot tayyor', '#' . $orderId . ' buyurtma mahsuloti tayyor. Hisob-faktura kabinetda ochildi, to\'lov muddati: 10 kun.', 'warning', 'buyer-orders');
        } elseif ($status === 'invoice_generated') {
            createNotification($pdo, $buyerId, 'Hisob-faktura yaratildi', '#' . $orderId . ' buyurtma uchun hisob-faktura yaratildi. To\'lov muddati: 10 kun.', 'warning', 'buyer-orders');
        } else {
            createNotification($pdo, $buyerId, 'Buyurtma holati yangilandi', '#' . $orderId . ' buyurtma holati: ' . $status, 'info', 'buyer-orders');
        }
    }
    
    sendJson(['success' => true]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
