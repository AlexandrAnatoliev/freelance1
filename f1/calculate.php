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

// Временно отключаем проверку тарифа для теста
// if (!isset($items[$tariffKey])) {
//   die('Ошибка: Тариф не найден.');
// }

// Просто загружаем шаблон счета как есть
$htmlContent = file_get_contents('shet_obrasez.html');

// Подключаем PHPMailer
require_once 'mailer.php';

$subject = "Счет на оплату №" . date('Ymd-His') . " от " . date('d.m.Y');

// Отправка покупателю
$resultCustomer = sendInvoiceEmail($customerEmail, $customerName, $subject, $htmlContent);

// Отправка админу (уведомление)
$adminEmail = 'otetzalexandr1986@gmail.com';
$resultAdmin = sendInvoiceEmail($adminEmail, 'Администратор', "Копия: " . $subject, $htmlContent);

// Показываем результат
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказ оформлен</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="calculator" style="text-align: center;">
        <h1 style="color: #1e8449;">✓ Заказ оформлен!</h1>
        <p>Счет отправлен на <strong><?= htmlspecialchars($customerEmail) ?></strong>.</p>
        <?php if (!$resultCustomer): ?>
            <p style="color: red;">⚠ Внимание: Письмо не отправлено. Проверьте настройки почты.</p>
        <?php endif; ?>
        <p>Проверьте папку «Спам», если письма нет.</p>

        <hr style="margin: 30px 0;">
        <h2>Ваш счет</h2>
        <?= $htmlContent ?>
        <p><a href="index.php">← Вернуться к калькулятору</a></p>
    </div>
</body>
</html>
