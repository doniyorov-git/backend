<?php
require_once '../config/db.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT id, name, inn, phone, role, status, bank_account, mfo, created_at FROM users WHERE role != 'admin' ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
    sendJson(['success' => true, 'data' => $users]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id'])) {
        sendJson(['success' => false, 'message' => 'ID is required'], 400);
    }
    
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$data['id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendJson(['success' => false, 'message' => 'User not found'], 404);
    }
    
    $newStatus = $user['status'] === 'active' ? 'blocked' : 'active';
    
    $update = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $update->execute([$newStatus, $data['id']]);
    
    sendJson(['success' => true, 'status' => $newStatus]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
