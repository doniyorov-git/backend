<?php
require_once 'config/db.php';

if (!isset($_SESSION['user'])) {
    sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($user['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM tickets ORDER BY created_at DESC");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user['id']]);
    }
    
    $tickets = $stmt->fetchAll();
    
    // Fetch replies for each ticket
    foreach ($tickets as &$ticket) {
        $rStmt = $pdo->prepare("SELECT author_name AS author, message AS text, DATE(created_at) AS date FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC");
        $rStmt->execute([$ticket['id']]);
        $ticket['replies'] = $rStmt->fetchAll();
    }
    
    sendJson(['success' => true, 'data' => $tickets]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $data['action'] ?? '';
    
    if ($action === 'create') {
        $id = uniqid('t_');
        $subject = $data['subject'] ?? '';
        $message = $data['message'] ?? '';
        
        if (!$subject || !$message) {
            sendJson(['success' => false, 'message' => 'Mavzu va xabar kiritilishi shart'], 400);
        }
        
        $stmt = $pdo->prepare("INSERT INTO tickets (id, user_id, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $user['id'], $subject, $message]);
        sendJson(['success' => true]);
        
    } elseif ($action === 'reply') {
        $ticketId = $data['ticket_id'] ?? '';
        $message = $data['message'] ?? '';
        
        if (!$ticketId || !$message) {
            sendJson(['success' => false, 'message' => 'Ticket ID va xabar kiritilishi shart'], 400);
        }
        
        // Ensure ticket belongs to user if not admin
        if ($user['role'] !== 'admin') {
            $check = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ?");
            $check->execute([$ticketId, $user['id']]);
            if (!$check->fetch()) {
                sendJson(['success' => false, 'message' => 'Ruxsat etilmagan'], 403);
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, author_name, message) VALUES (?, ?, ?)");
        $stmt->execute([$ticketId, $user['name'], $message]);
        sendJson(['success' => true]);
        
    } else {
        sendJson(['success' => false, 'message' => 'Invalid action'], 400);
    }
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
