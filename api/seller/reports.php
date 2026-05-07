<?php
require_once '../config/db.php';
requireRole(['seller']);

$sellerId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->execute([$sellerId]);
    $reports = $stmt->fetchAll();
    sendJson(['success' => true, 'data' => $reports]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if it's updating an existing report (photo report upload)
    $reportId = $_POST['id'] ?? null;
    $note = $_POST['note'] ?? '';
    
    if ($reportId) {
        $fileUrl = saveUploadedFile('file', 'reports', 'rep');
        
        if ($fileUrl) {
            $stmt = $pdo->prepare("UPDATE reports SET image = ?, note = ?, status = 'done' WHERE id = ? AND seller_id = ?");
            $stmt->execute([$fileUrl, $note, $reportId, $sellerId]);
            sendJson(['success' => true]);
        } else {
            sendJson(['success' => false, 'message' => 'No image uploaded'], 400);
        }
    } else {
        sendJson(['success' => false, 'message' => 'Report ID missing'], 400);
    }
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
