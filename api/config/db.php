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
    echo json_encode($data);
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
?>
