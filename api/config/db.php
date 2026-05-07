<?php
session_start();

$host = 'localhost';
$db   = 'c1309_diller';
$user = 'c1309_diller';
$pass = 'Buxoro2025';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Baza xatosi: " . $e->getMessage()]);
    exit;
}

// Ensure all PHP errors/exceptions return JSON
set_exception_handler(function($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Server xatosi: " . $e->getMessage()]);
    exit;
});

// Utility function to send JSON response
function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Utility function to check role
function requireRole($allowedRoles) {
    if (!isset($_SESSION['user'])) {
        sendJson(["success" => false, "message" => "Unauthorized"], 401);
    }
    if (!in_array($_SESSION['user']['role'], $allowedRoles)) {
        sendJson(["success" => false, "message" => "Forbidden"], 403);
    }
}

function appRootPath($path = '') {
    $root = dirname(__DIR__, 2);
    return $path ? $root . '/' . ltrim($path, '/') : $root;
}

function ensureDirectory($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function saveUploadedFile($fieldName, $folder, $prefix = 'file') {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        sendJson(['success' => false, 'message' => 'Faqat rasm fayllarini yuklash mumkin'], 400);
    }

    $safeFolder = trim($folder, '/');
    $uploadDir = appRootPath('uploads/' . $safeFolder);
    ensureDirectory($uploadDir);

    $filename = uniqid($prefix . '_', true) . '.' . $ext;
    $dest = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $dest)) {
        sendJson(['success' => false, 'message' => 'Rasmni saqlashda xatolik'], 500);
    }

    return 'uploads/' . $safeFolder . '/' . $filename;
}

function ensureAppSchema(PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id VARCHAR(50) PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            type VARCHAR(50) DEFAULT 'info',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            link VARCHAR(100),
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notifications_user (user_id, is_read, created_at)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");

    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if (!$stmt->fetchColumn()) {
        return;
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'status'");
    $statusColumn = $stmt->fetch();
    if ($statusColumn && strpos($statusColumn['Type'], 'approved') === false) {
        $pdo->exec("ALTER TABLE products MODIFY status ENUM('pending', 'approved', 'rejected', 'active', 'inactive') DEFAULT 'pending'");
    }

    $columns = [
        'moderation_note' => "ALTER TABLE products ADD COLUMN moderation_note TEXT NULL AFTER status",
        'moderated_by' => "ALTER TABLE products ADD COLUMN moderated_by VARCHAR(50) NULL AFTER moderation_note",
        'moderated_at' => "ALTER TABLE products ADD COLUMN moderated_at DATETIME NULL AFTER moderated_by"
    ];

    foreach ($columns as $column => $sql) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = ?
        ");
        $stmt->execute([$column]);
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec($sql);
        }
    }
}

function createNotification(PDO $pdo, $userId, $title, $message, $type = 'info', $link = null) {
    if (!$userId) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO notifications (id, user_id, type, title, message, link)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([uniqid('ntf_'), $userId, $type, $title, $message, $link]);
}

function notifyRole(PDO $pdo, $role, $title, $message, $type = 'info', $link = null) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ? AND status = 'active'");
    $stmt->execute([$role]);

    foreach ($stmt->fetchAll() as $user) {
        createNotification($pdo, $user['id'], $title, $message, $type, $link);
    }
}

ensureAppSchema($pdo);
?>
