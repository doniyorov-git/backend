<?php
require_once '../config/db.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT p.*, s.name as seller_name, s.phone as seller_phone, s.inn as seller_inn
        FROM products p
        LEFT JOIN users s ON p.seller_id = s.id
        ORDER BY p.created_at DESC
    ");
    $products = $stmt->fetchAll();
    sendJson(['success' => true, 'data' => $products]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? '';
    $id = $data['id'] ?? '';
    $note = trim($data['note'] ?? '');

    if (!$id || !in_array($action, ['approve', 'reject'], true)) {
        sendJson(['success' => false, 'message' => 'Noto\'g\'ri moderatsiya so\'rovi'], 400);
    }

    $stmt = $pdo->prepare("
        SELECT p.*, s.name as seller_name
        FROM products p
        LEFT JOIN users s ON p.seller_id = s.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        sendJson(['success' => false, 'message' => 'Mahsulot topilmadi'], 404);
    }

    $status = $action === 'approve' ? 'approved' : 'rejected';
    if ($status === 'rejected' && !$note) {
        $note = 'Admin tomonidan rad etildi';
    }

    $stmt = $pdo->prepare("
        UPDATE products
        SET status = ?, moderation_note = ?, moderated_by = ?, moderated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$status, $note, $_SESSION['user']['id'], $id]);

    if ($status === 'approved') {
        createNotification($pdo, $product['seller_id'], 'Mahsulot tasdiqlandi', $product['name'] . ' katalogda ko\'rinadi.', 'success', 'seller-catalog');
    } else {
        createNotification($pdo, $product['seller_id'], 'Mahsulot rad etildi', $product['name'] . ': ' . $note, 'danger', 'seller-catalog');
    }

    sendJson(['success' => true, 'status' => $status]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
