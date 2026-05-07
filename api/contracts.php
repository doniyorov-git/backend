<?php
require_once 'config/db.php';

if (!isset($_SESSION['user'])) {
    sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user = $_SESSION['user'];
$userId = $user['id'] ?? '';
$role = $user['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($role === 'admin') {
        $stmt = $pdo->query("
            SELECT c.id, c.contract_number, c.contract_type, c.title, c.signer_id, c.counterparty_id,
                   c.product_id, c.order_id, c.source, c.content, c.signer_snapshot, c.counterparty_snapshot,
                   c.ip_address, c.user_agent, c.signed_at, c.created_at,
                   s.name AS signer_name, s.role AS signer_role, s.inn AS signer_inn, s.phone AS signer_phone,
                   cp.name AS counterparty_name, cp.role AS counterparty_role, cp.inn AS counterparty_inn, cp.phone AS counterparty_phone,
                   p.name AS product_name, p.sku AS product_sku,
                   o.total AS order_total
            FROM contract_signatures c
            LEFT JOIN users s ON c.signer_id = s.id
            LEFT JOIN users cp ON c.counterparty_id = cp.id
            LEFT JOIN products p ON c.product_id = p.id
            LEFT JOIN orders o ON c.order_id = o.id
            ORDER BY c.signed_at DESC
        ");
        sendJson(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    $stmt = $pdo->prepare("
        SELECT c.id, c.contract_number, c.contract_type, c.title, c.signer_id, c.counterparty_id,
               c.product_id, c.order_id, c.source, c.content, c.signer_snapshot, c.counterparty_snapshot,
               c.ip_address, c.user_agent, c.signed_at, c.created_at,
               s.name AS signer_name, s.role AS signer_role, s.inn AS signer_inn, s.phone AS signer_phone,
               cp.name AS counterparty_name, cp.role AS counterparty_role, cp.inn AS counterparty_inn, cp.phone AS counterparty_phone,
               p.name AS product_name, p.sku AS product_sku,
               o.total AS order_total
        FROM contract_signatures c
        LEFT JOIN users s ON c.signer_id = s.id
        LEFT JOIN users cp ON c.counterparty_id = cp.id
        LEFT JOIN products p ON c.product_id = p.id
        LEFT JOIN orders o ON c.order_id = o.id
        WHERE c.signer_id = ? OR c.counterparty_id = ?
        ORDER BY c.signed_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    sendJson(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? '';

    if ($action !== 'preview') {
        sendJson(['success' => false, 'message' => 'Unknown action'], 400);
    }

    $type = $data['type'] ?? 'platform_terms';
    if (!in_array($type, ['platform_terms', 'seller_listing', 'buyer_order'], true)) {
        sendJson(['success' => false, 'message' => 'Invalid contract type'], 400);
    }

    $counterpartyId = $data['counterparty_id'] ?? null;
    if ($type === 'seller_listing' && $role !== 'seller') {
        sendJson(['success' => false, 'message' => 'Forbidden'], 403);
    }
    if ($type === 'buyer_order' && $role !== 'buyer') {
        sendJson(['success' => false, 'message' => 'Forbidden'], 403);
    }

    $document = buildContractDocument($pdo, $type, $userId, $counterpartyId, [
        'source' => $data['source'] ?? 'preview',
        'product_id' => $data['product_id'] ?? null,
        'order_id' => $data['order_id'] ?? null
    ]);

    sendJson(['success' => true, 'data' => $document]);
}

sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
?>
