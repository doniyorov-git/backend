<?php
require_once 'config/db.php';

if (!isset($_SESSION['user'])) {
    sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT id, type, title, message, link, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$userId]);
    sendJson(['success' => true, 'data' => $stmt->fetchAll()]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? '';

    if ($action === 'mark_read') {
        $id = $data['id'] ?? '';
        if (!$id) {
            sendJson(['success' => false, 'message' => 'Bildirishnoma ID kerak'], 400);
        }

        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        sendJson(['success' => true]);
    } elseif ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        sendJson(['success' => true]);
    }

    sendJson(['success' => false, 'message' => 'Noma\'lum amal'], 400);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
