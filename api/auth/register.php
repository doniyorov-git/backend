<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'message' => 'Invalid request method'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['phone']) || empty($data['password']) || empty($data['role'])) {
    sendJson(['success' => false, 'message' => 'Missing required fields'], 400);
}

if (empty($data['contract_accepted'])) {
    sendJson(['success' => false, 'message' => 'Shartnomaga rozilik talab qilinadi'], 400);
}

$id = uniqid('u_');
$name = $data['name'] ?? '';
$inn = $data['inn'] ?? '';
$phone = preg_replace('/\D/', '', $data['phone']);
if (strlen($phone) === 9) {
    $phone = '998' . $phone;
}
$password = $data['password'];
$role = $data['role'];
$bank_account = $data['bank_account'] ?? '';
$mfo = $data['mfo'] ?? '';

if (!in_array($role, ['seller', 'buyer'])) {
    sendJson(['success' => false, 'message' => 'Invalid role'], 400);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO users (id, name, inn, phone, password, role, bank_account, mfo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $name, $inn, $phone, $password, $role, $bank_account, $mfo]);
    recordContractSignature($pdo, 'platform_terms', $id, null, ['source' => $data['contract_source'] ?? 'register']);
    $pdo->commit();
    
    $_SESSION['user'] = [
        'id' => $id,
        'name' => $name,
        'phone' => $phone,
        'role' => $role,
        'inn' => $inn,
        'bank_account' => $bank_account,
        'mfo' => $mfo,
        'status' => 'active'
    ];
    
    sendJson(['success' => true, 'user' => $_SESSION['user']]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e instanceof PDOException && $e->getCode() == 23000) {
        sendJson(['success' => false, 'message' => 'Bu telefon raqam allaqachon ro\'yxatdan o\'tgan'], 400);
    }
    sendJson(['success' => false, 'message' => 'Database error'], 500);
}
?>
