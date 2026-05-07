<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJson(['success' => false, 'message' => 'Invalid request method'], 405);
}

if (isset($_SESSION['user'])) {
    sendJson(['success' => true, 'user' => $_SESSION['user']]);
} else {
    sendJson(['success' => false, 'user' => null]);
}
?>
