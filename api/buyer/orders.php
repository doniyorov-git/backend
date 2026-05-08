<?php
require_once '../config/db.php';
requireRole(['buyer']);

$buyerId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT o.*, s.name as seller_name, s.phone as seller_phone, s.inn as seller_inn, s.bank_account, s.mfo 
        FROM orders o
        JOIN users s ON o.seller_id = s.id
        WHERE o.buyer_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$buyerId]);
    $orders = $stmt->fetchAll();
    
    foreach ($orders as &$order) {
        $stmt2 = $pdo->prepare("
            SELECT oi.*, p.name as product_name, p.unit, p.sku, p.mxik_code, p.category
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt2->execute([$order['id']]);
        $order['items'] = $stmt2->fetchAll();
    }
    
    sendJson(['success' => true, 'data' => $orders]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isMultipart = !empty($_POST);
    $data = $isMultipart ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
    
    if (isset($data['action']) && $data['action'] === 'create_order') {
        if (empty($data['sellerId']) || empty($data['items']) || empty($data['total'])) {
            sendJson(['success' => false, 'message' => 'Missing data'], 400);
        }

        if (empty($data['contract_accepted']) && !hasContractSignature($pdo, 'buyer_order', $buyerId)) {
            sendJson(['success' => false, 'message' => 'Buyurtma berish uchun shartnomaga rozilik talab qilinadi'], 400);
        }
        
        $orderId = uniqid('ord_');
        $comm = $data['total'] * 0.05; // 5% comm
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO orders (id, buyer_id, seller_id, total, comm) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$orderId, $buyerId, $data['sellerId'], $data['total'], $comm]);
            
            $stmt2 = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt3 = $pdo->prepare("INSERT INTO reports (id, seller_id, order_id, prod_id, status, due_date) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($data['items'] as $item) {
                $stmt2->execute([$orderId, $item['prodId'], $item['qty'], $item['price']]);
                
                $repId = uniqid('rep_');
                // Calculate due date (15 days from now roughly)
                $dueDate = date('Y-m-d', strtotime('+15 days'));
                $stmt3->execute([$repId, $data['sellerId'], $orderId, $item['prodId'], 'pending', $dueDate]);
            }

            recordContractSignature($pdo, 'buyer_order', $buyerId, null, ['source' => 'checkout']);
            
            $pdo->commit();
            createNotification($pdo, $data['sellerId'], 'Yangi buyurtma', '#' . $orderId . ' buyurtma qabul qilindi.', 'info', 'seller-orders');
            notifyRole($pdo, 'admin', 'Yangi buyurtma', '#' . $orderId . ' buyurtma yaratildi.', 'info', 'admin-orders');
            sendJson(['success' => true, 'order_id' => $orderId]);
        } catch (Exception $e) {
            $pdo->rollBack();
            sendJson(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()], 500);
        }
    } elseif (isset($data['action']) && $data['action'] === 'update_status') {
        if (empty($data['id']) || empty($data['status'])) {
            sendJson(['success' => false, 'message' => 'Missing parameters'], 400);
        }

        $stmt = $pdo->prepare("SELECT buyer_payment_proof, buyer_payment_due_at FROM orders WHERE id = ? AND buyer_id = ?");
        $stmt->execute([$data['id'], $buyerId]);
        $existingOrder = $stmt->fetch();
        if (!$existingOrder) {
            sendJson(['success' => false, 'message' => 'Buyurtma topilmadi'], 404);
        }

        $paymentProof = '';
        if ($data['status'] === 'buyer_paid') {
            $paymentProof = saveUploadedDocument('payment_proof', 'payments', 'buyer_pay');
            if (!$paymentProof && empty($existingOrder['buyer_payment_proof'])) {
                sendJson(['success' => false, 'message' => 'To\'lovni tasdiqlovchi PDF hujjatni yuklang'], 400);
            }
        }

        if ($paymentProof) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, buyer_payment_proof = ? WHERE id = ? AND buyer_id = ?");
            $stmt->execute([$data['status'], $paymentProof, $data['id'], $buyerId]);
        } elseif ($data['status'] === 'buyer_accepted') {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, buyer_payment_due_at = COALESCE(buyer_payment_due_at, DATE_ADD(NOW(), INTERVAL 10 DAY)) WHERE id = ? AND buyer_id = ?");
            $stmt->execute([$data['status'], $data['id'], $buyerId]);
        } else {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND buyer_id = ?");
            $stmt->execute([$data['status'], $data['id'], $buyerId]);
        }

        if ($data['status'] === 'paid') { // legacy logic mapping
            $stmt = $pdo->prepare("UPDATE orders SET comm_status = 'paid' WHERE id = ?");
            $stmt->execute([$data['id']]);
        }

        $stmt = $pdo->prepare("SELECT seller_id FROM orders WHERE id = ? AND buyer_id = ?");
        $stmt->execute([$data['id'], $buyerId]);
        $sellerId = $stmt->fetchColumn();
        if ($sellerId) {
            createNotification($pdo, $sellerId, 'Buyurtma holati yangilandi', '#' . $data['id'] . ' buyurtma holati: ' . $data['status'], 'info', 'seller-orders');
            if ($data['status'] === 'buyer_accepted') {
                createNotification($pdo, $sellerId, 'Xaridor mahsulotni qabul qildi', '#' . $data['id'] . ' buyurtma bo\'yicha 10 kunlik to\'lov muddati boshlandi.', 'success', 'seller-orders');
            } elseif ($data['status'] === 'buyer_paid') {
                createNotification($pdo, $sellerId, 'Xaridor to\'lov tasdig\'ini yubordi', '#' . $data['id'] . ' buyurtma to\'lovi tekshiruvni kutmoqda.', 'warning', 'seller-orders');
            }
        }
        
        sendJson(['success' => true]);
    } else {
        sendJson(['success' => false, 'message' => 'Unknown action'], 400);
    }
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
