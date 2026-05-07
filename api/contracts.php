<?php
require_once 'config/db.php';
requireRole(['admin', 'seller', 'buyer']);

$user = $_SESSION['user'];
$userId = $user['id'];
$role = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "
        SELECT
            c.*,
            su.name AS signer_name,
            su.role AS signer_role,
            su.phone AS signer_phone,
            cu.name AS counterparty_name,
            cu.role AS counterparty_role,
            cu.phone AS counterparty_phone,
            p.name AS product_name
        FROM contracts c
        JOIN users su ON c.signer_user_id = su.id
        LEFT JOIN users cu ON c.counterparty_user_id = cu.id
        LEFT JOIN products p ON c.product_id = p.id
    ";

    $params = [];
    if ($role !== 'admin') {
        $sql .= " WHERE c.signer_user_id = ? OR c.counterparty_user_id = ?";
        $params = [$userId, $userId];
    }

    $sql .= " ORDER BY c.signed_at DESC, c.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    sendJson(['success' => true, 'data' => $stmt->fetchAll()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? '';

    if ($action === 'sign_platform') {
        $source = $data['source'] ?? 'manual_platform';
        $contractId = createPlatformContract($pdo, $userId, $source);
        sendJson(['success' => true, 'contract_id' => $contractId]);
    }

    sendJson(['success' => false, 'message' => 'Unknown action'], 400);
}

sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
?>
