<?php

declare(strict_types=1);

// раскомментировать для вывода ошибок на экран
require_once 'utils/debug.php';
require_once 'utils/session.php';
require_once 'utils/rateLimit.php';

$clientIP = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isIpRateLimited($clientIP, 30)) {
        $_SESSION['error_message'] = 'Слишком много запросов. Пожалуйста, подождите 30 секунд и попробуйте снова.';
        header('Location: calc.php');
        exit;
    }
}

$location = 'Location: calc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header($location);
    exit;
}

// Проверка CAPTCHA
if (!isset($_POST['captcha']) || !isset($_SESSION['captcha_answer'])) {
    $_SESSION['error_message'] = 'Ошибка проверки. Пожалуйста, обновите страницу и попробуйте снова.';
    header($location);
    exit;
}

$userAnswer = (int) $_POST['captcha'];
$correctAnswer = (int) $_SESSION['captcha_answer'];

// Проверяем, не истекла ли капча (30 минут)
$captchaAge = time() - ($_SESSION['captcha_generated_at'] ?? 0);
if ($captchaAge > 1800) { // 30 минут
    $_SESSION['error_message'] = 'Время проверки истекло. Пожалуйста, обновите страницу.';
    header($location);
    exit;
}

// Проверяем правильность ответа
if ($userAnswer !== $correctAnswer) {
    $_SESSION['error_message'] = 'Неверный ответ на проверочный вопрос. Попробуйте снова.';
    // Генерируем новую капчу при ошибке
    header($location);
    exit;
}

// Очищаем капчу после успешной проверки
unset($_SESSION['captcha_answer'], $_SESSION['captcha_generated_at']);

// Получаем данные из формы
$itemNameKey      = $_POST['itemName'] ?? null;
$selectedAddons = $_POST['addons'] ?? [];
$quantity       = (int) ($_POST['quantity'] ?? 1);
$customerName   = htmlspecialchars($_POST['customer_name'] ?? '');
$customerEmail  = $_POST['customer_email'] ?? '';
$customerPhone  = htmlspecialchars($_POST['customer_phone'] ?? '');

// Проверка обязательных полей
if (!$itemNameKey) {
    die('Ошибка: Не выбран товар.');
}

if (empty($customerEmail)) {
    die('Ошибка: Не указан email.');
}

include_once 'invoice.php';
require_once 'mailer.php';
require_once 'configs/adminSettings.php';
require_once 'generatePDF.php';

$orderNumber    = date('Ymd-His');
$subject        = "Счет на оплату №{$orderNumber} от " . date('d.m.Y');

// счет
$fullInvoiceHTML = getInvoice(
    $itemNameKey,
    $selectedAddons,
    $quantity,
    $customerName,
    $customerPhone,
    $orderNumber
);
$emailMessage = getEmailMessage(
    $itemNameKey,
    $selectedAddons,
    $quantity,
    $orderNumber
);
$responsibleInvoice = getResponsibleInvoice(
    $itemNameKey,
    $selectedAddons,
    $quantity,
    $customerName,
    $customerPhone,
    $orderNumber
);

// Генерация PDF
$pdfContent = generatePDF($fullInvoiceHTML);
$pdfFilename = "Счёт_{$orderNumber}.pdf";

// Отправка покупателю (с PDF-вложением)
$resultCustomer = sendInvoiceEmail(
    $customerEmail,
    $customerName,
    $subject,
    $emailMessage,
    $pdfContent,       // PDF-вложение
    $pdfFilename
);

// Отправка админу (с PDF-вложением)
$admin = getAdminSettings();
$resultAdmin = sendInvoiceEmail(
    $admin['email'],
    'Администратор',
    "Копия: " . $subject,
    $emailMessage,
    $pdfContent,
    $pdfFilename
);

// Показываем результат
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ оформлен - Счет на оплату</title>
    <link rel="stylesheet" href="styles/checkout.css">
</head>
<body>
    <div class="result-container">
        <h1>✓ Заказ оформлен!</h1>

        <div class="success-message">
            <p>Наш менеджер свяжется с вами дополнительно.</p><br>
            <p>Счет отправлен на <strong><?= htmlspecialchars($customerEmail) ?></strong>
            <?php if (!empty($customerPhone)) : ?>
                (<strong><?= htmlspecialchars($customerPhone) ?></strong>)
            <?php endif; ?></p>
            <p>Копия на <strong><?= htmlspecialchars($admin['email']) ?></strong>
                (<strong><?= htmlspecialchars($admin['name']) ?></strong>)</p>
        </div>

        <?php if (!$resultCustomer) : ?>
            <div class="email-status email-error">
                <strong>⚠ Внимание!</strong> Письмо не было отправлено. Проверьте настройки почты.
            </div>
        <?php else : ?>
            <div class="email-status email-success">
                <strong>✓ Письмо успешно отправлено!</strong> Проверьте папку «Спам», если не видите письма.
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="calc.php" class="btn btn-back">
                ← ВЕРНУТЬСЯ К КАЛЬКУЛЯТОРУ
            </a>
        </div>

        <div class="invoice-preview">
            <?= $responsibleInvoice ?>
        </div>

        <div class="print-note">
            <p>📧 Полная версия версия счета отправлена на вашу почту для удобной оплаты с телефона</p>
        </div>
    </div>
</body>
</html>
