<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'message' => 'Invalid request method'], 405);
}

if (!isset($_SESSION['user'])) {
    sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';
$userId = $_SESSION['user']['id'] ?? '';

if (!$userId) {
    sendJson(['success' => false, 'message' => 'Foydalanuvchi topilmadi'], 401);
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
    sendJson(['success' => false, 'message' => 'Foydalanuvchi bazadan topilmadi'], 404);
}

if ($action === 'update_password') {
    $password = trim($data['password'] ?? '');
    if (strlen($password) < 4) {
        sendJson(['success' => false, 'message' => 'Parol kamida 4 belgidan iborat bo'lishi kerak'], 400);
    }

    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->execute([$password, $userId]);
} elseif ($action === 'update_bank') {
    $bankAccount = preg_replace('/\D/', '', $data['bank_account'] ?? '');
    $mfo = preg_replace('/\D/', '', $data['mfo'] ?? '');

    if (strlen($bankAccount) !== 20 || strlen($mfo) !== 5) {
        sendJson(['success' => false, 'message' => 'Hisob raqam 20 ta, MFO 5 ta raqam bo'lishi kerak'], 400);
    }

    $update = $pdo->prepare("UPDATE users SET bank_account = ?, mfo = ? WHERE id = ?");
    $update->execute([$bankAccount, $mfo, $userId]);
} else {
    sendJson(['success' => false, 'message' => 'Invalid action'], 400);
}

$stmt = $pdo->prepare("SELECT id, name, inn, phone, role, status, bank_account, mfo, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$_SESSION['user'] = $user;

sendJson(['success' => true, 'user' => $user]);
?>
