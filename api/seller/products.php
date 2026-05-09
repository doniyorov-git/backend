<?php
require_once '../config/db.php';
requireRole(['seller']);

$sellerId = $_SESSION['user']['id'];

function generateProductSku(PDO $pdo) {
    $stmt = $pdo->query("SELECT sku FROM products WHERE sku REGEXP '^RDP-[0-9]+$' ORDER BY CAST(SUBSTRING(sku, 5) AS UNSIGNED) DESC LIMIT 1");
    $lastSku = $stmt->fetchColumn();
    $next = 1;

    if ($lastSku && preg_match('/^RDP-(\d+)$/', $lastSku, $matches)) {
        $next = ((int) $matches[1]) + 1;
    }

    return 'RDP-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->execute([$sellerId]);
    $products = $stmt->fetchAll();
    sendJson(['success' => true, 'data' => $products]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if (!$id) {
            sendJson(['success' => false, 'message' => 'Mahsulot ID topilmadi'], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$id, $sellerId]);
        sendJson(['success' => true]);
    }

    $id = $_POST['id'] ?? '';
    $isUpdate = !empty($id);
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $region = $_POST['region'] ?? '';
    $model = $_POST['model'] ?? '';
    $mxikCode = preg_replace('/\D+/', '', $_POST['mxik_code'] ?? $_POST['mxikCode'] ?? '');
    $price = $_POST['price'] ?? 0;
    $unit = $_POST['unit'] ?? 'dona';
    $prepayPercent = $_POST['prepay_percent'] ?? $_POST['prepayPercent'] ?? null;
    $realDays = $_POST['real_days'] ?? $_POST['realDays'] ?? 30;
    $photoDays = $_POST['photo_days'] ?? $_POST['photoDays'] ?? 15;

    if (!$name || !$category || !$region || !$model || (float) $price <= 0) {
        sendJson(['success' => false, 'message' => 'Mahsulot nomi, kategoriya, viloyat, model va narx majburiy'], 400);
    }

    if (!$mxikCode || strlen($mxikCode) < 5 || strlen($mxikCode) > 32) {
        sendJson(['success' => false, 'message' => 'MXIK kodini to\'g\'ri kiriting'], 400);
    }

    if (!in_array($model, ['realization', 'prepayment'], true)) {
        sendJson(['success' => false, 'message' => 'Savdo modeli noto\'g\'ri tanlangan'], 400);
    }

    $realDays = max(1, (int) $realDays);
    $photoDays = max(1, (int) $photoDays);
    $prepayPercent = $model === 'prepayment' ? (float) ($prepayPercent ?? 30) : null;

    if ($model === 'prepayment' && ($prepayPercent <= 0 || $prepayPercent > 100)) {
        sendJson(['success' => false, 'message' => 'Oldindan to\'lov foizi 1 dan 100 gacha bo\'lishi kerak'], 400);
    }

    if (!$isUpdate && empty($_POST['contract_accepted']) && !hasContractSignature($pdo, 'seller_listing', $sellerId)) {
        sendJson(['success' => false, 'message' => 'Mahsulot joylash uchun shartnomaga rozilik talab qilinadi'], 400);
    }

    $imagePath = saveUploadedFile('image', 'products', 'img');

    if ($isUpdate) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$id, $sellerId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            sendJson(['success' => false, 'message' => 'Mahsulot topilmadi'], 404);
        }

        $imagePath = $imagePath ?: $existing['image'];
        $stmt = $pdo->prepare("
            UPDATE products
            SET name = ?, mxik_code = ?, category = ?, region = ?, model = ?, price = ?, unit = ?, image = ?,
                prepay_percent = ?, real_days = ?, photo_days = ?,
                status = 'pending', moderation_note = NULL, moderated_by = NULL, moderated_at = NULL
            WHERE id = ? AND seller_id = ?
        ");
        $stmt->execute([$name, $mxikCode, $category, $region, $model, $price, $unit, $imagePath, $prepayPercent, $realDays, $photoDays, $id, $sellerId]);
        notifyRole($pdo, 'admin', 'Mahsulot qayta moderatsiyaga yuborildi', $name . ' mahsuloti tekshiruv kutmoqda.', 'warning', 'admin-moderation');
    } else {
        $id = uniqid('p_');
        $sku = generateProductSku($pdo);
        $stmt = $pdo->prepare("
            INSERT INTO products (id, seller_id, name, sku, mxik_code, category, region, model, price, unit, image, prepay_percent, real_days, photo_days, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$id, $sellerId, $name, $sku, $mxikCode, $category, $region, $model, $price, $unit, $imagePath, $prepayPercent, $realDays, $photoDays]);
        recordContractSignature($pdo, 'seller_listing', $sellerId, null, ['source' => 'product_create', 'product_id' => $id]);
        notifyRole($pdo, 'admin', 'Yangi mahsulot moderatsiyada', $name . ' mahsuloti tasdiqlash uchun yuborildi.', 'info', 'admin-moderation');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    sendJson(['success' => true, 'data' => $stmt->fetch()]);
} else {
    sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
