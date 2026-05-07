<?php
require_once '../config/db.php';

if (!isset($_SESSION['user'])) {
    sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'message' => 'Invalid request method'], 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';
$userId = $_SESSION['user']['id'] ?? '';

if (!$userId) {
    sendJson(['success' => false, 'message' => 'Foydalanuvchi topilmadi'], 401);
}

if ($action === 'password') {
    $currentPassword = trim($data['current_password'] ?? '');
    $newPassword = trim($data['new_password'] ?? '');

    if (!$currentPassword || strlen($newPassword) < 4) {
        sendJson(['success' => false, 'message' => 'Joriy parol va yangi parolni to\'g\'ri kiriting'], 400);
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || $user['password'] !== $currentPassword) {
        sendJson(['success' => false, 'message' => 'Joriy parol noto\'g\'ri'], 400);
    }

    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->execute([$newPassword, $userId]);
} elseif ($action === 'bank') {
    $bankAccount = preg_replace('/\D/', '', $data['bank_account'] ?? '');
    $mfo = preg_replace('/\D/', '', $data['mfo'] ?? '');

    if (strlen($bankAccount) !== 20 || strlen($mfo) !== 5) {
        sendJson(['success' => false, 'message' => 'Hisob raqam 20 ta, MFO 5 ta raqam bo\'lishi kerak'], 400);
    }

    $update = $pdo->prepare("UPDATE users SET bank_account = ?, mfo = ? WHERE id = ?");
    $update->execute([$bankAccount, $mfo, $userId]);
} else {
    sendJson(['success' => false, 'message' => 'Invalid action'], 400);
}

$stmt = $pdo->prepare("SELECT id, name, inn, phone, role, status, bank_account, mfo, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$updatedUser = $stmt->fetch();

if (!$updatedUser) {
    sendJson(['success' => false, 'message' => 'Foydalanuvchi topilmadi'], 404);
}

$_SESSION['user'] = $updatedUser;
sendJson(['success' => true, 'user' => $updatedUser]);
?>
