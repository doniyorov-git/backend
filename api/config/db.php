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

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contracts (
            id VARCHAR(50) PRIMARY KEY,
            contract_type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            signer_user_id VARCHAR(50) NOT NULL,
            counterparty_user_id VARCHAR(50) NULL,
            product_id VARCHAR(50) NULL,
            order_id VARCHAR(50) NULL,
            document_text LONGTEXT NOT NULL,
            signer_snapshot LONGTEXT NULL,
            counterparty_snapshot LONGTEXT NULL,
            signed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_contracts_signer (signer_user_id, signed_at),
            INDEX idx_contracts_counterparty (counterparty_user_id, signed_at),
            INDEX idx_contracts_related (product_id, order_id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");

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

function contractPartyName($user) {
    return trim($user['name'] ?? '') ?: '________________';
}

function contractPartyDirector($user) {
    return trim($user['director'] ?? '') ?: '________________';
}

function contractPartyRequisites($label, $user) {
    return $label . ":\n" .
        "Nomi: " . contractPartyName($user) . "\n" .
        "STIR: " . (($user['inn'] ?? '') ?: '__') . "\n" .
        "Telefon: " . (($user['phone'] ?? '') ?: '__') . "\n" .
        "H/r: " . (($user['bank_account'] ?? '') ?: '__') . "\n" .
        "MFO: " . (($user['mfo'] ?? '') ?: '__') . "\n" .
        "Imzo: elektron tasdiq";
}

function contractPlatformRequisites() {
    return "PLATFORMA:\n" .
        "«RoboTexnika» MCHJ\n" .
        "Direktor: Mirzayev Sardor\n" .
        "STIR: __\n" .
        "H/r: __\n" .
        "MFO: __\n" .
        "Manzil: Andijon sh.\n" .
        "Imzo: elektron tasdiq";
}

function contractSnapshot($user) {
    if (!$user) {
        return null;
    }
    $snapshot = $user;
    unset($snapshot['password']);
    return json_encode($snapshot, JSON_UNESCAPED_UNICODE);
}

function fetchContractUser(PDO $pdo, $userId) {
    $stmt = $pdo->prepare("SELECT id, name, inn, phone, role, status, bank_account, mfo, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function platformContractText($user, $source = 'platform') {
    $userName = contractPartyName($user);
    $director = contractPartyDirector($user);
    $sourceLabel = $source === 'login_platform'
        ? 'tizimga kirish vaqtida'
        : ($source === 'product_listing' ? 'mahsulot joylash vaqtida' : 'ro\'yxatdan o\'tish vaqtida');

    return "HAMKORLIK VA XIZMAT KO'RSATISH SHARTNOMASI № 1\n" .
        "«___» ________ 2026 y.    Andijon sh.\n\n" .
        "1. SHARTNOMA TOMONLARI\n" .
        "1.1. «RoboTexnika» MCHJ, keyingi o'rinlarda «Platforma» deb yuritiladi, direktor Mirzayev Sardor (Ustav asosida) nomidan bir tomondan, va\n" .
        "1.2. «{$userName}», keyingi o'rinlarda «Ishlab chiqaruvchi» deb yuritiladi, direktor {$director} (Ustav asosida) nomidan ikkinchi tomondan, mazkur shartnomani quyidagilar to'g'risida tuzdilar.\n\n" .
        "2. SHARTNOMA PREDMETI\n" .
        "2.1. Platforma Ishlab chiqaruvchining tovarlarini chakana savdo nuqtalariga (Mijozlarga) sotishda vositachilik va axborot-texnologik xizmatlarini ko'rsatadi.\n" .
        "2.2. Platforma quyidagi majburiyatlarni oladi:\n" .
        "- Mijozlar bazasini shakllantirish va tovarni targ'ib qilish;\n" .
        "- Sotuvlar, yetkazib berish va to'lovlarning elektron hisobini yuritish;\n" .
        "- Ishlab chiqaruvchiga bozor tahlili va reyting ko'rsatkichlarini taqdim etish;\n" .
        "- Shartnomaning 5-bandiga muvofiq kafolatli hisob-kitoblarni ta'minlash.\n\n" .
        "3. TOMONLARNING HUQUQ VA MAJBURIYATLARI\n" .
        "3.1. Ishlab chiqaruvchining majburiyatlari:\n" .
        "- Tovarlarning sifati va amaldagi standartlarga (sertifikatlarga) mosligini ta'minlash;\n" .
        "- Platforma orqali kelgan buyurtmalarni o'z vaqtida va to'liq hajmda yetkazib berish;\n" .
        "- Tovar qoldiqlari va narxlar o'zgarishi haqida Platformani zudlik bilan xabardor qilish.\n" .
        "3.2. Platformaning huquqlari:\n" .
        "- Ishlab chiqaruvchining reyting ko'rsatkichlari pasaygan taqdirda xizmat ko'rsatishni vaqtincha to'xtatish;\n" .
        "- Mijozlar va Ishlab chiqaruvchi o'rtasidagi to'lov intizomini nazorat qilish.\n\n" .
        "4. KOMISSIYA MUKOFOTI VA HISOB-KITOBLAR\n" .
        "4.1. Platformaning xizmat haqi (komissiya) Mijoz tomonidan to'langan tovar qiymatining 5% (besh foiz) miqdorini tashkil etadi.\n" .
        "4.2. Platforma xizmatlari uchun hisob-fakturalarni (EHF) har oy yakunida taqdim etadi.\n" .
        "4.3. Ishlab chiqaruvchi komissiya to'lovini solishtirma dalolatnoma tasdiqlangan kundan boshlab 5 (besh) bank ish kuni ichida Platformaning hisob raqamiga o'tkazadi.\n" .
        "4.4. Agar tovar Mijoz tomonidan qaytarilsa, ushbu tovar bo'yicha hisoblangan komissiya keyingi davr hisob-kitoblarida chegirib qolinadi.\n\n" .
        "5. KAFOLAT VA MOLIYAVIY QO'LLAB-QUVVATLASH\n" .
        "5.1. Platforma Mijozlarning to'lov qobiliyatini o'z ichki tizimi orqali tahlil qiladi.\n" .
        "5.2. Platforma va Ishlab chiqaruvchi o'rtasidagi alohida kelishuvga asosan, Platforma Mijozning muddati o'tgan debitorlik qarzdorligini vaqtinchalik (faktoring yoki kafolat sifatida) qoplab berishi mumkin.\n" .
        "5.3. Platforma tomonidan qoplangan mablag' Mijozdan undirilgandan so'ng yoki kelishilgan muddatda Platformaga qaytariladi.\n\n" .
        "6. REYTING VA ELEKTRON TIZIM\n" .
        "6.1. Barcha oldi-sotdi operatsiyalari Platformaning dasturiy ta'minoti orqali qayd etiladi. Ushbu tizim ma'lumotlari hisob-kitob uchun asos hisoblanadi.\n" .
        "6.2. Ishlab chiqaruvchining reytingi quyidagilarga ta'sir qiladi:\n" .
        "- Platforma tomonidan beriladigan kafolat limitlariga;\n" .
        "- Tovarlarning tizimda ko'rinish ustuvorligiga (priority).\n\n" .
        "7. FORS-MAJOR VA JAVOBGARLIK\n" .
        "7.1. Tomonlar o'z majburiyatlarini bajarmagan taqdirda O'zbekiston Respublikasi qonunchiligiga muvofiq javobgar bo'ladilar.\n" .
        "7.2. Yetkazib berilgan tovarning sifati, yaroqlilik muddati va qadoqlanishi uchun to'liq javobgarlik Ishlab chiqaruvchi zimmasida bo'ladi.\n\n" .
        "8. NIZOLARNI HAL ETISH\n" .
        "8.1. Barcha kelishmovchiliklar muzokaralar yo'li bilan hal etiladi.\n" .
        "8.2. Kelishuvga erishilmagan taqdirda, nizo Platforma joylashgan hududdagi iqtisodiy sudda ko'rib chiqiladi.\n\n" .
        "9. YAKUNIY QOIDALAR\n" .
        "9.1. Shartnoma imzolangan kundan boshlab 12 oy davomida amal qiladi. Agar tomonlardan biri muddat tugashidan 30 kun avval bekor qilish haqida yozma xabar bermasa, shartnoma keyingi muddatga avtomatik uzaytiriladi.\n" .
        "9.2. Mazkur shartnoma ikki nusxada tuzildi va {$sourceLabel} elektron rozilik orqali tasdiqlandi.\n\n" .
        "10. TOMONLARNING REKVIZITLARI\n\n" .
        contractPlatformRequisites() . "\n\n" .
        contractPartyRequisites('ISHLAB CHIQARUVCHI', $user);
}

function buyerOrderContractText($buyer, $seller, $order) {
    $buyerName = contractPartyName($buyer);
    $buyerDirector = contractPartyDirector($buyer);

    return "MAHSULOT YETKAZIB BERISH VA XIZMAT KO'RSATISH SHARTNOMASI №" . (($order['id'] ?? '') ?: '___') . "\n" .
        "«___» ________ 2026 y.     Andijon sh.\n\n" .
        "1. SHARTNOMA TOMONLARI\n" .
        "1.1. «RoboTexnika» MCHJ, keyingi o'rinlarda «Platforma» deb yuritiladi, direktor Mirzayev Sardor nomidan, va\n" .
        "1.2. {$buyerName}, keyingi o'rinlarda «Xaridor» deb yuritiladi, direktor (yoki YATT) {$buyerDirector} nomidan, mazkur shartnomani quyidagilar to'g'risida tuzdilar.\n\n" .
        "2. SHARTNOMA PREDMETI\n" .
        "2.1. Platforma Xaridorga tizimdagi Ishlab chiqaruvchilarning mahsulotlarini tanlash, buyurtma berish va yetkazib berishni tashkil qilish xizmatlarini ko'rsatadi.\n" .
        "2.2. Xaridor Platforma orqali buyurtma qilingan tovarlarni qabul qilish va ularning haqini belgilangan muddatlarda to'lash majburiyatini oladi.\n\n" .
        "3. BUYURTMA VA YETKAZIB BERISH TARTIBI\n" .
        "3.1. Xaridor buyurtmani Platformaning elektron tizimi (ilova yoki sayt) orqali amalga oshiradi.\n" .
        "3.2. Tovarlar Xaridorning savdo nuqtasiga Ishlab chiqaruvchi yoki Platformaning logistika hamkorlari tomonidan yetkaziladi.\n" .
        "3.3. Tovar qabul qilinganda Xaridor uning sifati va miqdorini tekshirib, elektron yoki qog'oz shaklidagi yuk xatini (tovar-transport nakladnoyini) imzolaydi.\n\n" .
        "4. HISOB-KITOB TARTIBI\n" .
        "4.1. Tovar narxi Platforma tizimida buyurtma berilgan vaqtdagi narx bo'yicha belgilanadi.\n" .
        "4.2. Xaridor tovar uchun to'lovni quyidagi shaklda amalga oshirishi mumkin:\n" .
        "- Oldindan to'lov (100%);\n" .
        "- Bo'lib to'lash yoki kechiktirilgan to'lov (Platforma tomonidan belgilangan limit va reyting asosida).\n" .
        "4.3. To'lovlar naqd pulsiz shaklda, Platformaning tizimida ko'rsatilgan hisob raqamlariga amalga oshiriladi.\n\n" .
        "5. PLATFORMANING KAFOLATLARI\n" .
        "5.1. Platforma Xaridor va Ishlab chiqaruvchi o'rtasidagi hisob-kitoblarning shaffofligini ta'minlaydi.\n" .
        "5.2. Agar yetkazib berilgan tovar yaroqsiz (brak) chiqsa, Xaridor 24 soat ichida Platformaga ariza beradi va Platforma tovarni almashtirish yoki mablag'ni qaytarish jarayonini muvofiqlashtiradi.\n" .
        "5.3. Xaridor to'lovlarni o'z vaqtida amalga oshirsa, Platforma unga \"Ishonchli Xaridor\" maqomini va tovarlarni kechiktirib to'lash (kredit liniyasi) limitlarini taqdim etadi.\n\n" .
        "6. TOMONLARNING JAVOBGARLIGI\n" .
        "6.1. To'lov kechiktirilganda: Xaridor to'lov kechiktirilgan har bir kun uchun to'lanmagan summaning 0,1% miqdorida penya to'laydi, lekin bu jami summaning 10%idan oshmasligi kerak.\n" .
        "6.2. Mahsulotning sifati uchun bevosita Ishlab chiqaruvchi javobgar hisoblanadi, biroq Platforma nizoli vaziyatlarni hal qilishda Xaridor manfaatlarini himoya qilishga ko'maklashadi.\n\n" .
        "7. REYTING TIZIMI\n" .
        "7.1. Xaridorning to'lov intizomi asosida Platformada uning shaxsiy reytingi yuritiladi.\n" .
        "7.2. Past reyting Xaridor uchun kechiktirib to'lash imkoniyatining yopilishiga va buyurtmalarning cheklanishiga sabab bo'lishi mumkin.\n\n" .
        "8. SHARTNOMANING AMAL QILISHI\n" .
        "8.1. Shartnoma imzolangan kundan boshlab 12 oy davomida amal qiladi.\n" .
        "8.2. Shartnoma Platformaning elektron tizimida \"Ofertani qabul qilish\" tugmasini bosish orqali ham tuzilishi mumkin va u yuridik kuchga ega.\n\n" .
        "9. TOMONLARNING REKVIZITLARI\n\n" .
        contractPlatformRequisites() . "\n\n" .
        contractPartyRequisites('XARIDOR', $buyer) . "\n\n" .
        contractPartyRequisites('SOTUVCHI / ISHLAB CHIQARUVCHI', $seller) . "\n\n" .
        "Buyurtma: #" . (($order['id'] ?? '') ?: '__') . "\n" .
        "Buyurtma summasi: " . (($order['total'] ?? '') ?: '__');
}

function insertContract(PDO $pdo, $type, $title, $signer, $counterparty, $documentText, $productId = null, $orderId = null) {
    $stmt = $pdo->prepare("
        INSERT INTO contracts (
            id, contract_type, title, signer_user_id, counterparty_user_id, product_id, order_id,
            document_text, signer_snapshot, counterparty_snapshot
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $id = uniqid('ctr_');
    $stmt->execute([
        $id,
        $type,
        $title,
        $signer['id'],
        $counterparty['id'] ?? null,
        $productId,
        $orderId,
        $documentText,
        contractSnapshot($signer),
        contractSnapshot($counterparty)
    ]);
    return $id;
}

function createPlatformContract(PDO $pdo, $userId, $source = 'registration_platform') {
    $user = fetchContractUser($pdo, $userId);
    if (!$user) {
        return null;
    }
    $title = $source === 'login_platform'
        ? "Platforma shartnomasi (login)"
        : "Platforma shartnomasi (ro'yxatdan o'tish)";
    return insertContract($pdo, $source, $title, $user, null, platformContractText($user, $source));
}

function createProductListingContract(PDO $pdo, $sellerId, $productId) {
    $seller = fetchContractUser($pdo, $sellerId);
    if (!$seller) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    $text = platformContractText($seller, 'product_listing') .
        "\n\nMahsulot joylash ma'lumoti:\n" .
        "Mahsulot: " . (($product['name'] ?? '') ?: '__') . "\n" .
        "SKU: " . (($product['sku'] ?? '') ?: '__') . "\n" .
        "Narx: " . (($product['price'] ?? '') ?: '__') . "\n" .
        "Hudud: " . (($product['region'] ?? '') ?: '__');
    return insertContract($pdo, 'product_listing', "Mahsulot joylash shartnomasi", $seller, null, $text, $productId, null);
}

function createBuyerOrderContract(PDO $pdo, $buyerId, $sellerId, $orderId) {
    $buyer = fetchContractUser($pdo, $buyerId);
    $seller = fetchContractUser($pdo, $sellerId);
    if (!$buyer || !$seller) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    return insertContract($pdo, 'buyer_order', "Mahsulot yetkazib berish shartnomasi", $buyer, $seller, buyerOrderContractText($buyer, $seller, $order), null, $orderId);
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
