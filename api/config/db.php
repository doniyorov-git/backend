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

function saveUploadedDocument($fieldName, $folder, $prefix = 'doc', $allowedExtensions = ['pdf']) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        sendJson(['success' => false, 'message' => 'Faqat PDF hujjat yuklash mumkin'], 400);
    }

    $safeFolder = trim($folder, '/');
    $uploadDir = appRootPath('uploads/' . $safeFolder);
    ensureDirectory($uploadDir);

    $filename = uniqid($prefix . '_', true) . '.' . $ext;
    $dest = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $dest)) {
        sendJson(['success' => false, 'message' => 'Hujjatni saqlashda xatolik'], 500);
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

function appGenerateContractNumber(PDO $pdo) {
    $prefix = date('ymd');

    // Keep contract numbers compact while avoiding accidental duplicates.
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $contractNumber = $prefix . sprintf('%04d', random_int(0, 9999));
        $stmt = $pdo->prepare("SELECT 1 FROM contract_signatures WHERE contract_number = ? LIMIT 1");
        $stmt->execute([$contractNumber]);
        if (!$stmt->fetchColumn()) {
            return $contractNumber;
        }
    }

    return date('ymdHis');
}

function appFormatContractDate($value) {
    $raw = trim((string) ($value ?? ''));
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $match)) {
        return $match[3] . '.' . $match[2] . '.' . $match[1];
    }
    return $raw !== '' ? $raw : date('d.m.Y');
}

function appContractMetaHtml($contractNumber, $signedAt) {
    return '
        <p><b>Shartnoma raqami:</b> ' . appEscape(appValue($contractNumber, 'Aniqlanmagan')) . '<br>
        <b>Sana:</b> ' . appEscape(appFormatContractDate($signedAt)) . '</p>
    ';
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

    $stmt = $pdo->prepare("SELECT id, name, sku, mxik_code, category, price, unit, region, model, prepay_percent, real_days, photo_days FROM products WHERE id = ?");
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

function appContractDocumentHtml(array $lines) {
    $html = '<div class="contract-document">';
    foreach ($lines as $line) {
        if (strpos($line, 'TITLE:') === 0) {
            $html .= '<h3>' . appEscape(substr($line, 6)) . '</h3>';
        } elseif (strpos($line, 'SECTION:') === 0) {
            $html .= '<h4>' . appEscape(substr($line, 8)) . '</h4>';
        } elseif ($line === '') {
            $html .= '<div class="contract-spacer"></div>';
        } else {
            $html .= '<p>' . appEscape($line) . '</p>';
        }
    }
    return $html . '</div>';
}

function appSellerRegisterContractLines() {
    return [
        "TITLE:HAMKORLIK VA XIZMAT KO'RSATISH SHARTNOMASI No. 1",
        "\"___\" ________ 2026 y.    Andijon sh.",
        "SECTION:1. SHARTNOMA TOMONLARI",
        "1.1. \"RoboTexnika\" MCHJ, keyingi o'rinlarda \"Platforma\" deb yuritiladi, direktor Mirzayev Sardor (Ustav asosida) nomidan bir tomondan, va",
        "1.2. \"________________\", keyingi o'rinlarda \"Ishlab chiqaruvchi\" deb yuritiladi, direktor ________________ (Ustav asosida) nomidan ikkinchi tomondan, mazkur shartnomani quyidagilar to'g'risida tuzdilar:",
        "SECTION:2. SHARTNOMA PREDMETI",
        "2.1. Platforma Ishlab chiqaruvchining tovarlarini chakana savdo nuqtalariga (Mijozlarga) sotishda vositachilik va axborot-texnologik xizmatlarini ko'rsatadi.",
        "2.2. Platforma quyidagi majburiyatlarni oladi:",
        "- Mijozlar bazasini shakllantirish va tovarni targ'ib qilish;",
        "- Sotuvlar, yetkazib berish va to'lovlarning elektron hisobini yuritish;",
        "- Ishlab chiqaruvchiga bozor tahlili va reyting ko'rsatkichlarini taqdim etish;",
        "- Shartnomaning 5-bandiga muvofiq kafolatli hisob-kitoblarni ta'minlash.",
        "SECTION:3. TOMONLARNING HUQUQ VA MAJBURIYATLARI",
        "3.1. Ishlab chiqaruvchining majburiyatlari:",
        "- Tovarlarning sifati va amaldagi standartlarga (sertifikatlarga) mosligini ta'minlash;",
        "- Platforma orqali kelgan buyurtmalarni o'z vaqtida va to'liq hajmda yetkazib berish;",
        "- Tovar qoldiqlari va narxlar o'zgarishi haqida Platformani zudlik bilan xabardor qilish.",
        "3.2. Platformaning huquqlari:",
        "- Ishlab chiqaruvchining reyting ko'rsatkichlari pasaygan taqdirda xizmat ko'rsatishni vaqtincha to'xtatish;",
        "- Mijozlar va Ishlab chiqaruvchi o'rtasidagi to'lov intizomini nazorat qilish.",
        "SECTION:4. KOMISSIYA MUKOFOTI VA HISOB-KITOBLAR",
        "4.1. Platformaning xizmat haqi (komissiya) Mijoz tomonidan to'langan tovar qiymatining 5% (besh foiz) miqdorini tashkil etadi.",
        "4.2. Platforma xizmatlari uchun hisob-fakturalarni (EHF) har oy yakunida taqdim etadi.",
        "4.3. Ishlab chiqaruvchi komissiya to'lovini solishtirma dalolatnoma tasdiqlangan kundan boshlab 5 (besh) bank ish kuni ichida Platformaning hisob raqamiga o'tkazadi.",
        "4.4. Agar tovar Mijoz tomonidan qaytarilsa, ushbu tovar bo'yicha hisoblangan komissiya keyingi davr hisob-kitoblarida chegirib qolinadi.",
        "SECTION:5. KAFOLAT VA MOLIYAVIY QO'LLAB-QUVVATLASH",
        "5.1. Platforma Mijozlarning to'lov qobiliyatini o'z ichki tizimi orqali tahlil qiladi.",
        "5.2. Platforma va Ishlab chiqaruvchi o'rtasidagi alohida kelishuvga asosan, Platforma Mijozning muddati o'tgan debitorlik qarzdorligini vaqtinchalik (faktoring yoki kafolat sifatida) qoplab berishi mumkin.",
        "5.3. Platforma tomonidan qoplangan mablag' Mijozdan undirilgandan so'ng yoki kelishilgan muddatda Platformaga qaytariladi.",
        "SECTION:6. REYTING VA ELEKTRON TIZIM",
        "6.1. Barcha oldi-sotdi operatsiyalari Platformaning dasturiy ta'minoti orqali qayd etiladi. Ushbu tizim ma'lumotlari hisob-kitob uchun asos hisoblanadi.",
        "6.2. Ishlab chiqaruvchining reytingi quyidagilarga ta'sir qiladi:",
        "- Platforma tomonidan beriladigan kafolat limitlariga;",
        "- Tovarlarning tizimda ko'rinish ustuvorligiga (priority).",
        "SECTION:7. FORS-MAJOR VA JAVOBGARLIK",
        "7.1. Tomonlar o'z majburiyatlarini bajarmagan taqdirda O'zbekiston Respublikasi qonunchiligiga muvofiq javobgar bo'ladilar.",
        "7.2. Yetkazib berilgan tovarning sifati, yaroqlilik muddati va qadoqlanishi uchun to'liq javobgarlik Ishlab chiqaruvchi zimmasida bo'ladi.",
        "SECTION:8. NIZOLARNI HAL ETISH",
        "8.1. Barcha kelishmovchiliklar muzokaralar yo'li bilan hal etiladi.",
        "8.2. Kelishuvga erishilmagan taqdirda, nizo Platforma joylashgan hududdagi iqtisodiy sudda ko'rib chiqiladi.",
        "SECTION:9. YAKUNIY QOIDALAR",
        "9.1. Shartnoma imzolangan kundan boshlab 12 oy davomida amal qiladi. Agar tomonlardan biri muddat tugashidan 30 kun avval bekor qilish haqida yozma xabar bermasa, shartnoma keyingi muddatga avtomatik uzaytiriladi.",
        "9.2. Mazkur shartnoma ikki nusxada tuzildi."
    ];
}

function appBuyerRegisterContractLines() {
    return [
        "TITLE:MAHSULOT YETKAZIB BERISH VA XIZMAT KO'RSATISH SHARTNOMASI No.___",
        "\"___\" ________ 2026 y.     Andijon sh.",
        "SECTION:1. SHARTNOMA TOMONLARI",
        "1.1. \"RoboTexnika\" MCHJ, keyingi o'rinlarda \"Platforma\" deb yuritiladi, direktor Mirzayev Sardor nomidan, va",
        "1.2. ________________, keyingi o'rinlarda \"Xaridor\" deb yuritiladi, direktor (yoki YATT) ________________ nomidan, mazkur shartnomani quyidagilar to'g'risida tuzdilar:",
        "SECTION:2. SHARTNOMA PREDMETI",
        "2.1. Platforma Xaridorga tizimdagi Ishlab chiqaruvchilarning mahsulotlarini tanlash, buyurtma berish va yetkazib berishni tashkil qilish xizmatlarini ko'rsatadi.",
        "2.2. Xaridor Platforma orqali buyurtma qilingan tovarlarni qabul qilish va ularning haqini belgilangan muddatlarda to'lash majburiyatini oladi.",
        "SECTION:3. BUYURTMA VA YETKAZIB BERISH TARTIBI",
        "3.1. Xaridor buyurtmani Platformaning elektron tizimi (ilova yoki sayt) orqali amalga oshiradi.",
        "3.2. Tovarlar Xaridorning savdo nuqtasiga Ishlab chiqaruvchi yoki Platformaning logistika hamkorlari tomonidan yetkaziladi.",
        "3.3. Tovar qabul qilinganda Xaridor uning sifati va miqdorini tekshirib, elektron yoki qog'oz shaklidagi yuk xatini (tovar-transport nakladnoyini) imzolaydi.",
        "SECTION:4. HISOB-KITOB TARTIBI",
        "4.1. Tovar narxi Platforma tizimida buyurtma berilgan vaqtdagi narx bo'yicha belgilanadi.",
        "4.2. Xaridor tovar uchun to'lovni quyidagi shaklda amalga oshirishi mumkin:",
        "- Oldindan to'lov (100%);",
        "- Bo'lib to'lash yoki kechiktirilgan to'lov (Platforma tomonidan belgilangan limit va reyting asosida).",
        "4.3. To'lovlar naqd pulsiz shaklda, Platformaning tizimida ko'rsatilgan hisob raqamlariga amalga oshiriladi.",
        "SECTION:5. PLATFORMANING KAFOLATLARI",
        "5.1. Platforma Xaridor va Ishlab chiqaruvchi o'rtasidagi hisob-kitoblarning shaffofligini ta'minlaydi.",
        "5.2. Agar yetkazib berilgan tovar yaroqsiz (brak) chiqsa, Xaridor 24 soat ichida Platformaga ariza beradi va Platforma tovarni almashtirish yoki mablag'ni qaytarish jarayonini muvofiqlashtiradi.",
        "5.3. Xaridor to'lovlarni o'z vaqtida amalga oshirsa, Platforma unga \"Ishonchli Xaridor\" maqomini va tovarlarni kechiktirib to'lash (kredit liniyasi) limitlarini taqdim etadi.",
        "SECTION:6. TOMONLARNING JAVOBGARLIGI",
        "6.1. To'lov kechiktirilganda: Xaridor to'lov kechiktirilgan har bir kun uchun to'lanmagan summaning 0,1% miqdorida penya to'laydi, lekin bu jami summaning 10%idan oshmasligi kerak.",
        "6.2. Mahsulotning sifati uchun bevosita Ishlab chiqaruvchi javobgar hisoblanadi, biroq Platforma nizoli vaziyatlarni hal qilishda Xaridor manfaatlarini himoya qilishga ko'maklashadi.",
        "SECTION:7. REYTING TIZIMI",
        "7.1. Xaridorning to'lov intizomi asosida Platformada uning shaxsiy reytingi yuritiladi.",
        "7.2. Past reyting Xaridor uchun kechiktirib to'lash imkoniyatining yopilishiga va buyurtmalarning cheklanishiga sabab bo'lishi mumkin.",
        "SECTION:8. SHARTNOMANING AMAL QILISHI",
        "8.1. Shartnoma imzolangan kundan boshlab 12 oy davomida amal qiladi.",
        "8.2. Shartnoma Platformaning elektron tizimida \"Ofertani qabul qilish\" tugmasini bosish orqali ham tuzilishi mumkin va u yuridik kuchga ega."
    ];
}

function appBuyerOrderContractLines() {
    return [
        "TITLE:MAHSULOT OLDI-SOTDI SHARTNOMASI No.___",
        "\"___\" ________ 2026 y.",
        "SECTION:1. SHARTNOMA TOMONLARI",
        "1.1. \"________________\" (keyingi o'rinlarda - Sotuvchi), direktor ________________ nomidan bir tomondan, va",
        "1.2. \"________________\" (keyingi o'rinlarda - Xaridor), direktor ________________ nomidan ikkinchi tomondan, mazkur shartnomani quyidagilar to'g'risida tuzdilar:",
        "SECTION:2. SHARTNOMA PREDMETI",
        "2.1. Sotuvchi o'zi ishlab chiqargan mahsulotlarni Xaridorga mulk qilib topshirish, Xaridor esa mahsulotlarni qabul qilish va haqini to'lash majburiyatini oladi.",
        "2.2. Mazkur shartnoma doirasidagi barcha buyurtmalar, tovarlar ro'yxati va ularning narxi \"My-Diler.uz\" elektron platformasi (keyingi o'rinlarda - Platforma) orqali rasmiylashtiriladi.",
        "SECTION:3. TO'LOV SHARTLARI",
        "3.1. Mahsulotlarning narxi Platformada buyurtma berilgan vaqtda belgilangan amaldagi preyskurant bo'yicha hisoblanadi.",
        "3.2. To'lov shartlari (oldindan to'lov, bo'lib to'lash yoki kechiktirib to'lash) va muddatlari Platformada belgilangan tartibda va miqdorda amalga oshiriladi.",
        "3.3. Xaridor tomonidan to'lovlar Platformaning texnik imkoniyatlari va hisob-kitob tizimidan foydalangan holda amalga oshirilishi mumkin.",
        "SECTION:4. YETKAZIB BERISH TARTIBI",
        "4.1. Mahsulotlarni yetkazib berish xizmati va shartlari Platforma tomonidan belgilangan logistika qoidalariga asosan amalga oshiriladi.",
        "4.2. Yetkazib berish muddati Platformadagi elektron buyurtma tasdiqlangan vaqtdan boshlab hisoblanadi.",
        "4.3. Mahsulot Xaridor tomonidan qabul qilib olingan vaqtda elektron yuk xati (EHF yoki Platforma dalolatnomasi) tasdiqlangan paytdan boshlab mahsulotga bo'lgan mulk huquqi Xaridorga o'tadi.",
        "SECTION:5. MAHSULOT SIFATI VA KAFOLATI",
        "5.1. Sotuvchi mahsulotning sifati O'zbekiston Respublikasi standartlariga va Platformada ko'rsatilgan tavsiflarga mos kelishiga kafolat beradi.",
        "5.2. Yashirin nuqsonlar yoki yaroqsiz (brak) mahsulotlar aniqlangan taqdirda, Xaridor Platformaning da'volar bilan ishlash tartibiga muvofiq mahsulotni almashtirishni talab qilish huquqiga ega.",
        "SECTION:6. TOMONLARNING JAVOBGARLIGI",
        "6.1. Tomonlar majburiyatlarini bajarmagan taqdirda O'zbekiston Respublikasining amaldagi qonunchiligi va Platformaning ichki qoidalariga muvofiq javobgar bo'ladilar.",
        "6.2. Platforma tizimidagi texnik xatoliklar yoki logistikadagi uzilishlar uchun Sotuvchi javobgar hisoblanmaydi (agar ayb Sotuvchida bo'lmasa).",
        "SECTION:7. YAKUNIY QOIDALAR",
        "7.1. Platformadagi elektron ma'lumotlar, buyurtmalar tarixi va hisob-kitoblar shartnomaning ajralmas qismi va rasmiy dalil hisoblanadi.",
        "7.2. Nizolar muzokaralar yo'li bilan, kelishuv bo'lmasa, iqtisodiy sudda ko'rib chiqiladi.",
        "7.3. Shartnoma tomonlar imzolagan paytdan boshlab 1 yil davomida amal qiladi."
    ];
}

function appSellerListingContractHtml(PDO $pdo, $sellerId, $context = []) {
    return [
        'title' => 'Hamkorlik va xizmat ko\'rsatish shartnomasi',
        'content' => appContractDocumentHtml(appSellerRegisterContractLines()),
        'signer' => appFetchUserParty($pdo, $sellerId),
        'counterparty' => null
    ];
}

function appBuyerOrderContractHtml(PDO $pdo, $buyerId, $sellerId, $context = []) {
    return [
        'title' => 'Mahsulot oldi-sotdi shartnomasi',
        'content' => appContractDocumentHtml(appBuyerOrderContractLines()),
        'signer' => appFetchUserParty($pdo, $buyerId),
        'counterparty' => appFetchUserParty($pdo, $sellerId)
    ];
}

function appPlatformTermsContractHtml(PDO $pdo, $userId, $context = []) {
    $user = appFetchUserParty($pdo, $userId);
    $isBuyer = ($user['role'] ?? '') === 'buyer';
    return [
        'title' => $isBuyer ? 'Mahsulot yetkazib berish va xizmat ko\'rsatish shartnomasi' : 'Hamkorlik va xizmat ko\'rsatish shartnomasi',
        'content' => appContractDocumentHtml($isBuyer ? appBuyerRegisterContractLines() : appSellerRegisterContractLines()),
        'signer' => $user,
        'counterparty' => null
    ];
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

function hasContractSignature(PDO $pdo, $type, $signerId) {
    $stmt = $pdo->prepare("SELECT 1 FROM contract_signatures WHERE contract_type = ? AND signer_id = ? LIMIT 1");
    $stmt->execute([$type, $signerId]);
    return (bool) $stmt->fetchColumn();
}

function recordContractSignature(PDO $pdo, $type, $signerId, $counterpartyId = null, $context = []) {
    if ($type === 'seller_listing') {
        $stmt = $pdo->prepare("SELECT id FROM contract_signatures WHERE contract_type = ? AND signer_id = ? ORDER BY signed_at ASC LIMIT 1");
        $stmt->execute([$type, $signerId]);
        $existingId = $stmt->fetchColumn();
        if ($existingId) {
            return $existingId;
        }
    }

    $contractNumber = appGenerateContractNumber($pdo);
    $signedAt = date('Y-m-d H:i:s');
    $context = array_merge($context, [
        'contract_number' => $contractNumber,
        'contract_signed_at' => $signedAt
    ]);
    $document = buildContractDocument($pdo, $type, $signerId, $counterpartyId, $context);
    $id = uniqid('ctr_');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $signerSnapshot = json_encode($document['signer'] ?? [], JSON_UNESCAPED_UNICODE);
    $counterpartySnapshot = json_encode($document['counterparty'] ?? [], JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        INSERT INTO contract_signatures (
            id, contract_type, title, contract_number, signer_id, counterparty_id, product_id, order_id, source,
            content, signer_snapshot, counterparty_snapshot, ip_address, user_agent, signed_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $id,
        $type,
        $document['title'],
        $contractNumber,
        $signerId,
        $counterpartyId,
        $context['product_id'] ?? null,
        $context['order_id'] ?? null,
        $context['source'] ?? null,
        $document['content'],
        $signerSnapshot,
        $counterpartySnapshot,
        $ip,
        $agent,
        $signedAt
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
            contract_type ENUM('platform_terms', 'seller_listing', 'buyer_order') NOT NULL,
            title VARCHAR(255) NOT NULL,
            contract_number VARCHAR(30) NULL,
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
            INDEX idx_contracts_signer (signer_id, signed_at),
            INDEX idx_contracts_counterparty (counterparty_id, signed_at),
            INDEX idx_contracts_order (order_id),
            INDEX idx_contracts_product (product_id)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");

    $stmt = $pdo->query("SHOW TABLES LIKE 'contract_signatures'");
    if ($stmt->fetchColumn()) {
        $columns = [
            'contract_number' => "ALTER TABLE contract_signatures ADD COLUMN contract_number VARCHAR(30) NULL AFTER title"
        ];

        foreach ($columns as $column => $sql) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_signatures' AND COLUMN_NAME = ?
            ");
            $stmt->execute([$column]);
            if ((int) $stmt->fetchColumn() === 0) {
                $pdo->exec($sql);
            }
        }

        $pdo->exec("
            UPDATE contract_signatures
            SET contract_number = CONCAT(
                DATE_FORMAT(COALESCE(signed_at, created_at), '%y%m%d'),
                LPAD(MOD(CRC32(id), 10000), 4, '0')
            )
            WHERE contract_number IS NULL
                OR contract_number = ''
                OR contract_number REGEXP '^[0-9]{16}$'
        ");
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

    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($stmt->fetchColumn()) {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'status'");
        $statusColumn = $stmt->fetch();
        if ($statusColumn) {
            $statusType = $statusColumn['Type'];
            if (strpos($statusType, 'invoice_generated') !== false) {
                $pdo->exec("UPDATE orders SET status = 'product_ready' WHERE status = 'invoice_generated'");
            }
            if (strpos($statusType, 'product_ready') === false || strpos($statusType, 'invoice_generated') !== false) {
                $pdo->exec("ALTER TABLE orders MODIFY status ENUM('pending_seller_accept', 'seller_accepted', 'product_ready', 'dispatched', 'delivered', 'buyer_accepted', 'buyer_paid', 'trade_closed', 'seller_paid_comm', 'paid') DEFAULT 'pending_seller_accept'");
            }
        }

        $columns = [
            'buyer_payment_proof' => "ALTER TABLE orders ADD COLUMN buyer_payment_proof VARCHAR(255) NULL AFTER dispatch_report",
            'seller_commission_proof' => "ALTER TABLE orders ADD COLUMN seller_commission_proof VARCHAR(255) NULL AFTER buyer_payment_proof",
            'invoice_generated_at' => "ALTER TABLE orders ADD COLUMN invoice_generated_at DATETIME NULL AFTER seller_commission_proof",
            'buyer_payment_due_at' => "ALTER TABLE orders ADD COLUMN buyer_payment_due_at DATETIME NULL AFTER invoice_generated_at",
            'commission_due_at' => "ALTER TABLE orders ADD COLUMN commission_due_at DATETIME NULL AFTER buyer_payment_due_at"
        ];

        foreach ($columns as $column => $sql) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = ?
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
        'mxik_code' => "ALTER TABLE products ADD COLUMN mxik_code VARCHAR(32) NULL AFTER sku",
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
