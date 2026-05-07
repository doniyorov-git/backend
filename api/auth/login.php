<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'message' => 'Invalid request method'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['phone']) || empty($data['password'])) {
    sendJson(['success' => false, 'message' => 'Telefon va parol kiritilishi shart'], 400);
}

if (empty($data['contract_accepted'])) {
    sendJson(['success' => false, 'message' => 'Shartnomaga rozilik talab qilinadi'], 400);
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
    recordContractSignature($pdo, 'platform_terms', $user['id'], null, ['source' => $data['contract_source'] ?? 'login']);
    $_SESSION['user'] = $user;
    
    sendJson(['success' => true, 'user' => $user]);
}

sendJson(['success' => false, 'message' => 'Noto\'g\'ri login yoki parol'], 401);
?>
