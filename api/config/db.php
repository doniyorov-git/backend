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

function appEscape($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function appValue($value, $fallback = 'Kiritilmagan') {
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? $value : $fallback;
}

function appFetchUserParty(PDO $pdo, $userId) {
    if (!$userId) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id, name, inn, phone, role, bank_account, mfo, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }

    return [
        'id' => $user['id'],
        'name' => $user['name'],
        'director' => '',
        'inn' => $user['inn'] ?? '',
        'phone' => $user['phone'] ?? '',
        'role' => $user['role'] ?? '',
        'bank_account' => $user['bank_account'] ?? '',
        'mfo' => $user['mfo'] ?? '',
        'created_at' => $user['created_at'] ?? ''
    ];
}

function appPlatformParty(PDO $pdo) {
    $admin = null;
    try {
        $stmt = $pdo->query("SELECT id, name, inn, phone, bank_account, mfo FROM users WHERE role = 'admin' ORDER BY created_at ASC LIMIT 1");
        $admin = $stmt->fetch();
    } catch (Exception $e) {
        $admin = null;
    }

    return [
        'id' => $admin['id'] ?? 'platform',
        'name' => 'RoboTexnika MCHJ',
        'director' => 'Mirzayev Sardor',
        'inn' => $admin['inn'] ?? '',
        'phone' => $admin['phone'] ?? '',
        'role' => 'platform',
        'bank_account' => $admin['bank_account'] ?? '',
        'mfo' => $admin['mfo'] ?? '',
        'created_at' => ''
    ];
}

function appPartyRequisitesHtml($label, $party) {
    return '
        <div class="contract-party">
            <b>' . appEscape($label) . '</b><br>
            Nomi: ' . appEscape(appValue($party['name'] ?? '')) . '<br>
            Direktor/YATT: ' . appEscape(appValue($party['director'] ?? '', 'Kiritilmagan')) . '<br>
            STIR: ' . appEscape(appValue($party['inn'] ?? '')) . '<br>
            Telefon: ' . appEscape(appValue($party['phone'] ?? '')) . '<br>
            H/r: ' . appEscape(appValue($party['bank_account'] ?? '')) . '<br>
            MFO: ' . appEscape(appValue($party['mfo'] ?? '')) . '
        </div>
    ';
}

function appFetchProduct(PDO $pdo, $productId) {
    if (!$productId) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id, name, sku, category, price, unit, region, model, prepay_percent, real_days, photo_days FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    return $stmt->fetch() ?: null;
}

function appFetchOrder(PDO $pdo, $orderId) {
    if (!$orderId) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id, total, comm, created_at FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetch() ?: null;
}

function appSellerListingContractHtml(PDO $pdo, $sellerId, $context = []) {
    $platform = appPlatformParty($pdo);
    $seller = appFetchUserParty($pdo, $sellerId);
    $product = appFetchProduct($pdo, $context['product_id'] ?? null);
    $productTradeTerms = '';
    if ($product) {
        $productTradeTerms = $product['model'] === 'prepayment'
            ? ', oldindan to\'lov: ' . appEscape($product['prepay_percent'] ?? 0) . '%'
            : ', savdo modeli: realizatsiya';
        $productTradeTerms .= ', realizatsiya muddati: ' . appEscape($product['real_days'] ?? 30) . ' kun';
    }
    $productLine = $product
        ? '<p><b>Mahsulot:</b> ' . appEscape($product['name']) . ' (' . appEscape($product['sku'] ?? '') . '), narx: ' . appEscape($product['price']) . ' UZS, hudud: ' . appEscape($product['region'] ?? '') . $productTradeTerms . '.</p>'
        : '';

    $content = '
        <div class="contract-document">
            <h4>1. SHARTNOMA TOMONLARI</h4>
            <p>1.1. "' . appEscape($platform['name']) . '", keyingi o\'rinlarda "Platforma" deb yuritiladi, direktor ' . appEscape($platform['director']) . ' nomidan bir tomondan, va</p>
            <p>1.2. "' . appEscape(appValue($seller['name'] ?? '')) . '", keyingi o\'rinlarda "Ishlab chiqaruvchi" deb yuritiladi, direktor yoki YATT ' . appEscape(appValue($seller['director'] ?? '', 'Kiritilmagan')) . ' nomidan ikkinchi tomondan, mazkur shartnomani quyidagilar to\'g\'risida tuzdilar:</p>
            <h4>2. SHARTNOMA PREDMETI</h4>
            <p>2.1. Platforma Ishlab chiqaruvchining tovarlarini chakana savdo nuqtalariga (Mijozlarga) sotishda vositachilik va axborot-texnologik xizmatlarini ko\'rsatadi.</p>
            <p>2.2. Platforma quyidagi majburiyatlarni oladi:</p>
            <ul>
                <li>Mijozlar bazasini shakllantirish va tovarni targ\'ib qilish;</li>
                <li>Sotuvlar, yetkazib berish va to\'lovlarning elektron hisobini yuritish;</li>
                <li>Ishlab chiqaruvchiga bozor tahlili va reyting ko\'rsatkichlarini taqdim etish;</li>
                <li>Shartnomaning 5-bandiga muvofiq kafolatli hisob-kitoblarni ta\'minlash.</li>
            </ul>
            ' . $productLine . '
            <h4>3. TOMONLARNING HUQUQ VA MAJBURIYATLARI</h4>
            <p>3.1. Ishlab chiqaruvchi tovarlarning sifati va amaldagi standartlarga mosligini ta\'minlaydi, Platforma orqali kelgan buyurtmalarni o\'z vaqtida va to\'liq hajmda yetkazib beradi, tovar qoldiqlari va narxlar o\'zgarishi haqida Platformani zudlik bilan xabardor qiladi.</p>
            <p>3.2. Platforma Ishlab chiqaruvchining reyting ko\'rsatkichlari pasaygan taqdirda xizmat ko\'rsatishni vaqtincha to\'xtatish hamda Mijozlar va Ishlab chiqaruvchi o\'rtasidagi to\'lov intizomini nazorat qilish huquqiga ega.</p>
            <h4>4. KOMISSIYA MUKOFOTI VA HISOB-KITOBLAR</h4>
            <p>4.1. Platformaning xizmat haqi Mijoz tomonidan to\'langan tovar qiymatining 5% (besh foiz) miqdorini tashkil etadi.</p>
            <p>4.2. Platforma xizmatlari uchun hisob-fakturalarni har oy yakunida taqdim etadi.</p>
            <p>4.3. Ishlab chiqaruvchi komissiya to\'lovini solishtirma dalolatnoma tasdiqlangan kundan boshlab 5 (besh) bank ish kuni ichida Platformaning hisob raqamiga o\'tkazadi.</p>
            <p>4.4. Agar tovar Mijoz tomonidan qaytarilsa, ushbu tovar bo\'yicha hisoblangan komissiya keyingi davr hisob-kitoblarida chegirib qolinadi.</p>
            <h4>5. KAFOLAT VA MOLIYAVIY QO\'LLAB-QUVVATLASH</h4>
            <p>5.1. Platforma Mijozlarning to\'lov qobiliyatini o\'z ichki tizimi orqali tahlil qiladi.</p>
            <p>5.2. Alohida kelishuvga asosan Platforma Mijozning muddati o\'tgan debitorlik qarzdorligini vaqtinchalik qoplab berishi mumkin.</p>
            <p>5.3. Platforma tomonidan qoplangan mablag\' Mijozdan undirilgandan so\'ng yoki kelishilgan muddatda Platformaga qaytariladi.</p>
            <h4>6. REYTING VA ELEKTRON TIZIM</h4>
            <p>6.1. Barcha oldi-sotdi operatsiyalari Platformaning dasturiy ta\'minoti orqali qayd etiladi. Ushbu tizim ma\'lumotlari hisob-kitob uchun asos hisoblanadi.</p>
            <p>6.2. Ishlab chiqaruvchining reytingi Platforma tomonidan beriladigan kafolat limitlariga va tovarlarning tizimda ko\'rinish ustuvorligiga ta\'sir qiladi.</p>
            <h4>7. FORS-MAJOR VA JAVOBGARLIK</h4>
            <p>7.1. Tomonlar o\'z majburiyatlarini bajarmagan taqdirda O\'zbekiston Respublikasi qonunchiligiga muvofiq javobgar bo\'ladilar.</p>
            <p>7.2. Yetkazib berilgan tovarning sifati, yaroqlilik muddati va qadoqlanishi uchun to\'liq javobgarlik Ishlab chiqaruvchi zimmasida bo\'ladi.</p>
            <h4>8. NIZOLARNI HAL ETISH</h4>
            <p>8.1. Barcha kelishmovchiliklar muzokaralar yo\'li bilan hal etiladi.</p>
            <p>8.2. Kelishuvga erishilmagan taqdirda, nizo Platforma joylashgan hududdagi iqtisodiy sudda ko\'rib chiqiladi.</p>
            <h4>9. YAKUNIY QOIDALAR</h4>
            <p>9.1. Shartnoma imzolangan kundan boshlab 12 oy davomida amal qiladi. Tomonlardan biri muddat tugashidan 30 kun avval bekor qilish haqida yozma xabar bermasa, shartnoma keyingi muddatga avtomatik uzaytiriladi.</p>
            <p>9.2. Elektron tizimda "Roziman" tugmasini bosish ushbu shartnomani imzolash bilan teng yuridik kuchga ega.</p>
            <h4>10. TOMONLARNING REKVIZITLARI</h4>
            <div class="contract-parties">' . appPartyRequisitesHtml('PLATFORMA', $platform) . appPartyRequisitesHtml('ISHLAB CHIQARUVCHI', $seller ?: []) . '</div>
        </div>
    ';

    return ['title' => 'Hamkorlik va xizmat ko\'rsatish shartnomasi', 'content' => $content, 'signer' => $seller, 'counterparty' => null];
}

function appBuyerOrderContractHtml(PDO $pdo, $buyerId, $sellerId, $context = []) {
    $platform = appPlatformParty($pdo);
    $buyer = appFetchUserParty($pdo, $buyerId);
    $seller = appFetchUserParty($pdo, $sellerId);
    $order = appFetchOrder($pdo, $context['order_id'] ?? null);
    $orderLine = $order
        ? '<p><b>Buyurtma:</b> #' . appEscape($order['id']) . ', summa: ' . appEscape($order['total']) . ' UZS.</p>'
        : '';

    $content = '
        <div class="contract-document">
            <h4>1. SHARTNOMA TOMONLARI</h4>
            <p>1.1. "' . appEscape($platform['name']) . '", keyingi o\'rinlarda "Platforma" deb yuritiladi, direktor ' . appEscape($platform['director']) . ' nomidan, va</p>
            <p>1.2. "' . appEscape(appValue($buyer['name'] ?? '')) . '", keyingi o\'rinlarda "Xaridor" deb yuritiladi, direktor yoki YATT ' . appEscape(appValue($buyer['director'] ?? '', 'Kiritilmagan')) . ' nomidan, mazkur shartnomani quyidagilar to\'g\'risida tuzdilar:</p>
            ' . $orderLine . '
            <h4>2. SHARTNOMA PREDMETI</h4>
            <p>2.1. Platforma Xaridorga tizimdagi Ishlab chiqaruvchilarning mahsulotlarini tanlash, buyurtma berish va yetkazib berishni tashkil qilish xizmatlarini ko\'rsatadi.</p>
            <p>2.2. Xaridor Platforma orqali buyurtma qilingan tovarlarni qabul qilish va ularning haqini belgilangan muddatlarda to\'lash majburiyatini oladi.</p>
            <h4>3. BUYURTMA VA YETKAZIB BERISH TARTIBI</h4>
            <p>3.1. Xaridor buyurtmani Platformaning elektron tizimi orqali amalga oshiradi.</p>
            <p>3.2. Tovarlar Xaridorning savdo nuqtasiga Ishlab chiqaruvchi yoki Platformaning logistika hamkorlari tomonidan yetkaziladi.</p>
            <p>3.3. Tovar qabul qilinganda Xaridor uning sifati va miqdorini tekshiradi hamda elektron yoki qog\'oz shaklidagi yuk xatini imzolaydi.</p>
            <h4>4. HISOB-KITOB TARTIBI</h4>
            <p>4.1. Tovar narxi Platforma tizimida buyurtma berilgan vaqtdagi narx bo\'yicha belgilanadi.</p>
            <p>4.2. Xaridor tovar uchun to\'lovni oldindan to\'lov, bo\'lib to\'lash yoki kechiktirilgan to\'lov shaklida amalga oshirishi mumkin.</p>
            <p>4.3. To\'lovlar naqd pulsiz shaklda, Platformaning tizimida ko\'rsatilgan hisob raqamlariga amalga oshiriladi.</p>
            <h4>5. PLATFORMANING KAFOLATLARI</h4>
            <p>5.1. Platforma Xaridor va Ishlab chiqaruvchi o\'rtasidagi hisob-kitoblarning shaffofligini ta\'minlaydi.</p>
            <p>5.2. Agar yetkazib berilgan tovar yaroqsiz chiqsa, Xaridor 24 soat ichida Platformaga ariza beradi va Platforma tovarni almashtirish yoki mablag\'ni qaytarish jarayonini muvofiqlashtiradi.</p>
            <p>5.3. Xaridor to\'lovlarni o\'z vaqtida amalga oshirsa, Platforma unga "Ishonchli Xaridor" maqomini va kechiktirib to\'lash limitlarini taqdim etadi.</p>
            <h4>6. TOMONLARNING JAVOBGARLIGI</h4>
            <p>6.1. To\'lov kechiktirilganda Xaridor har bir kechiktirilgan kun uchun to\'lanmagan summaning 0,1% miqdorida penya to\'laydi, biroq bu jami summaning 10%idan oshmaydi.</p>
            <p>6.2. Mahsulotning sifati uchun bevosita Ishlab chiqaruvchi javobgar hisoblanadi, Platforma nizoli vaziyatlarni hal qilishda Xaridor manfaatlarini himoya qilishga ko\'maklashadi.</p>
            <h4>7. REYTING TIZIMI</h4>
            <p>7.1. Xaridorning to\'lov intizomi asosida Platformada uning shaxsiy reytingi yuritiladi.</p>
            <p>7.2. Past reyting Xaridor uchun kechiktirib to\'lash imkoniyatining yopilishiga va buyurtmalarning cheklanishiga sabab bo\'lishi mumkin.</p>
            <h4>8. SHARTNOMANING AMAL QILISHI</h4>
            <p>8.1. Shartnoma imzolangan kundan boshlab 12 oy davomida amal qiladi.</p>
            <p>8.2. Shartnoma Platformaning elektron tizimida "Ofertani qabul qilish" yoki "Roziman" tugmasini bosish orqali ham tuzilishi mumkin va u yuridik kuchga ega.</p>
            <h4>9. TOMONLARNING REKVIZITLARI</h4>
            <div class="contract-parties">' . appPartyRequisitesHtml('PLATFORMA', $platform) . appPartyRequisitesHtml('XARIDOR', $buyer ?: []) . '</div>
            <p><b>Sotuvchi ma\'lumotnomasi:</b> ' . appEscape(appValue($seller['name'] ?? '')) . ', STIR: ' . appEscape(appValue($seller['inn'] ?? '')) . ', telefon: ' . appEscape(appValue($seller['phone'] ?? '')) . '.</p>
        </div>
    ';

    return ['title' => 'Mahsulot yetkazib berish va xizmat ko\'rsatish shartnomasi', 'content' => $content, 'signer' => $buyer, 'counterparty' => $seller];
}

function appPlatformTermsContractHtml(PDO $pdo, $userId, $context = []) {
    $platform = appPlatformParty($pdo);
    $user = appFetchUserParty($pdo, $userId);
    $source = $context['source'] ?? 'register';

    $content = '
        <div class="contract-document">
            <h3>PLATFORMA OFERTASI VA XIZMAT KO\'RSATISH SHARTNOMASI</h3>
            <p>Ushbu shartnoma "' . appEscape($platform['name']) . '" va foydalanuvchi o\'rtasida elektron tarzda tuziladi.</p>
            <h4>1. TOMONLAR</h4>
            <p>1.1. Platforma: "' . appEscape($platform['name']) . '", direktor ' . appEscape($platform['director']) . '.</p>
            <p>1.2. Foydalanuvchi: "' . appEscape(appValue($user['name'] ?? '')) . '", rol: ' . appEscape(appValue($user['role'] ?? '')) . '.</p>
            <h4>2. XIZMATLAR</h4>
            <p>2.1. Platforma foydalanuvchiga kabinet, katalog, buyurtma, hisob-kitob, bildirishnoma va yordam servislaridan foydalanish imkonini beradi.</p>
            <p>2.2. Foydalanuvchi kiritilgan kompaniya, STIR, telefon va bank rekvizitlari to\'g\'riligiga shaxsan javob beradi.</p>
            <h4>3. ELEKTRON ROZILIK</h4>
            <p>3.1. Ro\'yxatdan o\'tish vaqtida "Roziman" tugmasini bosish shartnomani elektron imzolash bilan teng kuchga ega.</p>
            <p>3.2. Rozilik manbasi: ' . appEscape($source) . '.</p>
            <h4>4. MAXFIYLIK VA JAVOBGARLIK</h4>
            <p>4.1. Tomonlar shartnoma doirasida olingan tijorat, shaxsiy va moliyaviy ma\'lumotlarni uchinchi shaxslarga asossiz oshkor qilmaydi.</p>
            <p>4.2. Tizimdagi barcha operatsiyalar, buyurtmalar va bildirishnomalar elektron dalil sifatida qabul qilinadi.</p>
            <h4>5. REKVIZITLAR</h4>
            <div class="contract-parties">' . appPartyRequisitesHtml('PLATFORMA', $platform) . appPartyRequisitesHtml('FOYDALANUVCHI', $user ?: []) . '</div>
        </div>
    ';

    return ['title' => 'Platforma ofertasi va xizmat ko\'rsatish shartnomasi', 'content' => $content, 'signer' => $user, 'counterparty' => null];
}

function buildContractDocument(PDO $pdo, $type, $signerId, $counterpartyId = null, $context = []) {
    if ($type === 'seller_listing') {
        return appSellerListingContractHtml($pdo, $signerId, $context);
    }

    if ($type === 'buyer_order') {
        return appBuyerOrderContractHtml($pdo, $signerId, $counterpartyId, $context);
    }

    return appPlatformTermsContractHtml($pdo, $signerId, $context);
}

function recordContractSignature(PDO $pdo, $type, $signerId, $counterpartyId = null, $context = []) {
    $document = buildContractDocument($pdo, $type, $signerId, $counterpartyId, $context);
    $id = uniqid('ctr_');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $signerSnapshot = json_encode($document['signer'] ?? [], JSON_UNESCAPED_UNICODE);
    $counterpartySnapshot = json_encode($document['counterparty'] ?? [], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        INSERT INTO contract_signatures (
            id, contract_type, title, signer_id, counterparty_id, product_id, order_id, source,
            content, signer_snapshot, counterparty_snapshot, ip_address, user_agent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $id,
        $type,
        $document['title'],
        $signerId,
        $counterpartyId,
        $context['product_id'] ?? null,
        $context['order_id'] ?? null,
        $context['source'] ?? null,
        $document['content'],
        $signerSnapshot,
        $counterpartySnapshot,
        $ip,
        $agent
    ]);

    return $id;
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

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contract_signatures (
            id VARCHAR(50) PRIMARY KEY,
            contract_number INT UNSIGNED NOT NULL AUTO_INCREMENT,
            contract_type ENUM('platform_terms', 'seller_listing', 'buyer_order') NOT NULL,
            title VARCHAR(255) NOT NULL,
            signer_id VARCHAR(50) NOT NULL,
            counterparty_id VARCHAR(50) NULL,
            product_id VARCHAR(50) NULL,
            order_id VARCHAR(50) NULL,
            source VARCHAR(50) NULL,
            content MEDIUMTEXT NOT NULL,
            signer_snapshot TEXT NULL,
            counterparty_snapshot TEXT NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_contract_number (contract_number),
            INDEX idx_contracts_signer (signer_id, signed_at),
            INDEX idx_contracts_counterparty (counterparty_id, signed_at),
            INDEX idx_contracts_order (order_id),
            INDEX idx_contracts_product (product_id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");

    // Migrate: add contract_number to existing contract_signatures table
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_signatures' AND COLUMN_NAME = 'contract_number'
    ");
    $stmt->execute();
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE contract_signatures ADD COLUMN contract_number INT UNSIGNED NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY uk_contract_number (contract_number)");
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->fetchColumn()) {
        $columns = [
            'bank_account' => "ALTER TABLE users ADD COLUMN bank_account VARCHAR(50) NULL AFTER status",
            'mfo' => "ALTER TABLE users ADD COLUMN mfo VARCHAR(10) NULL AFTER bank_account"
        ];

        foreach ($columns as $column => $sql) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?
            ");
            $stmt->execute([$column]);
            if ((int) $stmt->fetchColumn() === 0) {
                $pdo->exec($sql);
            }
        }
    }

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
        'prepay_percent' => "ALTER TABLE products ADD COLUMN prepay_percent DECIMAL(5,2) NULL AFTER model",
        'real_days' => "ALTER TABLE products ADD COLUMN real_days INT DEFAULT 30 AFTER prepay_percent",
        'photo_days' => "ALTER TABLE products ADD COLUMN photo_days INT DEFAULT 15 AFTER real_days",
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
