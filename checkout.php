<?php

declare(strict_types=1);

// раскомментировать для вывода ошибок на экран
require_once 'utils/debug.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Получаем данные из формы
$tariffKey      = $_POST['tariff'] ?? null;
$selectedAddons = $_POST['addons'] ?? [];
$quantity       = (int) ($_POST['quantity'] ?? 1);
$customerName   = htmlspecialchars($_POST['customer_name'] ?? '');
$customerEmail  = $_POST['customer_email'] ?? '';
$customerPhone  = htmlspecialchars($_POST['customer_phone'] ?? '');

// Проверка обязательных полей
if (!$tariffKey) {
    die('Ошибка: Не выбран тариф.');
}

if (empty($customerEmail)) {
    die('Ошибка: Не указан email.');
}

include_once 'invoice.php';
require_once 'mailer.php';
require_once 'configs/adminSettings.php';
require_once 'generatePDF.php';

$orderNumber    = 'Б-' . date('Ymd-His');
$subject        = "Счет на оплату №{$orderNumber} от " . date('d.m.Y');

// счет
$fullInvoiceHTML = getInvoice($tariffKey, $selectedAddons, $quantity, $customerName, $orderNumber);
$emailMessage = getEmailMessage($tariffKey, $selectedAddons, $quantity, $customerName, $orderNumber);

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
            <!-- Кнопка открытия PDF -->
            <a href="data:application/pdf;base64,<?= base64_encode($pdfContent) ?>"
              download="<?= $pdfFilename ?>"
              class="btn btn-print">
                📄 ОТКРЫТЬ СЧЕТ В PDF
            </a>
            <a href="index.php" class="btn btn-back">
                ← Вернуться к калькулятору
            </a>
        </div>

        <div class="print-note">
            <p>💡 Нажмите кнопку выше, затем выберите "Сохранить как PDF" в списке принтеров</p>
        </div>

        <div class="invoice-preview">
            <?= $fullInvoiceHTML ?>
        </div>

        <div class="print-note">
            <p>📧 Простая версия счета отправлена на вашу почту для удобной оплаты с телефона</p>
        </div>
    </div>
</body>
</html>
