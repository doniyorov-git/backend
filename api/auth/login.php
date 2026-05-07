<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'message' => 'Invalid request method'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['phone']) || empty($data['password'])) {
    sendJson(['success' => false, 'message' => 'Telefon va parol kiritilishi shart'], 400);
}

$phone = preg_replace('/\D/', '', $data['phone']);
if (strlen($phone) === 9) {
    $phone = '998' . $phone;
}
$password = $data['password'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch();

if ($user && $password === $user['password']) {
    if ($user['status'] !== 'active') {
        sendJson(['success' => false, 'message' => 'Sizning hisobingiz bloklangan'], 403);
    }
    
    unset($user['password']);
    $_SESSION['user'] = $user;
    
    sendJson(['success' => true, 'user' => $user]);
} else {
    // Admin override check (hardcoded fallback if DB is not seeded properly)
    if ($phone == '998901234567' && $password == 'admin') {
        $admin = [
            'id' => 'u_admin',
            'name' => 'Tizim Administratori',
            'phone' => '998901234567',
            'role' => 'admin',
            'status' => 'active'
        ];
        $_SESSION['user'] = $admin;
        sendJson(['success' => true, 'user' => $admin]);
    }
    sendJson(['success' => false, 'message' => 'Noto\'g\'ri login yoki parol'], 401);
}
?>
