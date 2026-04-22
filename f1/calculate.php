<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php');
  exit;
}

require_once 'configs/items.php';
require_once 'configs/addons.php';

// Получаем данные из формы
$tariffKey = $_POST['tariff'] ?? null;
$selectedAddons = $_POST['addons'] ?? [];
$quantity = (int)($_POST['quantity'] ?? 1);
$customerName = htmlspecialchars($_POST['customer_name'] ?? '');
$customerEmail = $_POST['customer_email'] ?? '';
$customerPhone = htmlspecialchars($_POST['customer_phone'] ?? '');

// Проверка обязательных полей
if (!$tariffKey) {
  die('Ошибка: Не выбран тариф.');
}

if (empty($customerEmail)) {
  die('Ошибка: Не указан email.');
}

$orderNumber = 'INV-' . date('Ymd-His');
$orderDate = date('d.m.Y H:i');
$total = 10880.00; // Ваша сумма

// Загружаем полный счет для отображения на сайте
$fullInvoiceHTML = include 'invoice.php';

// Подключаем PHPMailer
require_once 'mailer.php';

$subject = "Счет на оплату №{$orderNumber} от " . date('d.m.Y');

// Отправка покупателю 
$resultCustomer = sendInvoiceEmail($customerEmail, $customerName, $subject, $fullInvoiceHTML);

// Отправка админу
$adminEmail = 'otetzalexandr1986@gmail.com';
$resultAdmin = sendInvoiceEmail($adminEmail, 'Администратор', "Копия: " . $subject, $fullInvoiceHTML);

// Показываем результат
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ оформлен - Счет на оплату</title>
    <link rel="stylesheet" href="styles/calculate.css">
</head>
<body>
    <div class="result-container">
        <h1>✓ Заказ оформлен!</h1>

        <div class="success-message">
            <p>Счет №<?= $orderNumber ?> отправлен на <strong><?= htmlspecialchars($customerEmail) ?></strong></p>
            <?php if (!empty($customerPhone)): ?>
                <p>Номер телефона: <strong><?= htmlspecialchars($customerPhone) ?></strong></p>
            <?php endif; ?>
        </div>

        <?php if (!$resultCustomer): ?>
            <div class="email-status email-error">
                <strong>⚠ Внимание!</strong> Письмо не было отправлено. Проверьте настройки почты.
            </div>
        <?php else: ?>
            <div class="email-status email-success">
                <strong>✓ Письмо успешно отправлено!</strong> Проверьте папку «Спам», если не видите письма.
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <button class="btn btn-print" onclick="window.print()">
                🖨️ РАСПЕЧАТАТЬ / СОХРАНИТЬ В PDF
            </button>
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
