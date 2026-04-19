<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Данные товаров
$items = [
    'standart' => ['name' => 'Тариф Стандарт', 'price' => 1000],
    'pro'      => ['name' => 'Тариф Про', 'price' => 2500],
    'vip'      => ['name' => 'Тариф VIP', 'price' => 5000]
];
$addons = [
    'support' => ['name' => 'Поддержка 24/7', 'price' => 500],
    'backup'  => ['name' => 'Резервное копирование', 'price' => 300],
    'seo'     => ['name' => 'SEO-аудит', 'price' => 700]
];

// Получаем данные из формы
$tariffKey = $_POST['tariff'] ?? null;
$selectedAddons = $_POST['addons'] ?? [];
$quantity = (int)($_POST['quantity'] ?? 1);
$customerName = htmlspecialchars($_POST['customer_name'] ?? '');
$customerEmail = filter_var($_POST['customer_email'] ?? '', FILTER_VALIDATE_EMAIL);
$customerPhone = htmlspecialchars($_POST['customer_phone'] ?? '');

// Проверка обязательных полей
if (!$tariffKey || !isset($items[$tariffKey]) || !$customerEmail) {
    die('Ошибка: Не выбраны обязательные опции или неверный email.');
}

// Расчет стоимости
$subtotal = $items[$tariffKey]['price'];
$addonDetails = [];
foreach ($selectedAddons as $addonKey) {
    if (isset($addons[$addonKey])) {
        $subtotal += $addons[$addonKey]['price'];
        $addonDetails[] = $addons[$addonKey]['name'] . ' (' . number_format($addons[$addonKey]['price'], 0, ',', ' ') . ' ₽)';
    }
}
$total = $subtotal * $quantity;

$orderDate = date('d.m.Y H:i');
$orderNumber = 'INV-' . date('Ymd-His');

// Формируем HTML-счет
$htmlContent = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Счет на оплату №{$orderNumber}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        h2 { color: #2c3e50; }
        table { width: 100%; line-height: 1.8; border-collapse: collapse; margin: 20px 0; }
        td, th { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .total { font-size: 1.5rem; font-weight: bold; text-align: right; }
        .footer { margin-top: 30px; font-size: 0.9rem; color: #777; }
        .print-btn { background: #1e8449; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class='invoice-box'>
        <h2>Счет на оплату №{$orderNumber}</h2>
        <p><strong>Дата:</strong> {$orderDate}</p>
        <p><strong>Плательщик:</strong> {$customerName}<br>
        Email: {$customerEmail}<br>
        Телефон: {$customerPhone}</p>

        <table>
            <tr><th>Наименование</th><th>Кол-во</th><th>Цена</th><th>Сумма</th></tr>
            <tr>
                <td>{$items[$tariffKey]['name']}</td>
                <td>{$quantity} мес.</td>
                <td>" . number_format($items[$tariffKey]['price'], 0, ',', ' ') . " ₽</td>
                <td>" . number_format($items[$tariffKey]['price'] * $quantity, 0, ',', ' ') . " ₽</td>
            </tr>";

foreach ($selectedAddons as $addonKey) {
    if (isset($addons[$addonKey])) {
        $htmlContent .= "
            <tr>
                <td>{$addons[$addonKey]['name']}</td>
                <td>{$quantity} мес.</td>
                <td>" . number_format($addons[$addonKey]['price'], 0, ',', ' ') . " ₽</td>
                <td>" . number_format($addons[$addonKey]['price'] * $quantity, 0, ',', ' ') . " ₽</td>
            </tr>";
    }
}

$htmlContent .= "
            <tr style='border-top: 2px solid #333; font-weight: bold;'>
                <td colspan='3'>ИТОГО К ОПЛАТЕ:</td>
                <td>" . number_format($total, 0, ',', ' ') . " ₽</td>
            </tr>
        </table>

        <p><strong>Реквизиты для оплаты:</strong><br>
        ООО «Ваша Компания»<br>
        ИНН 1234567890 / КПП 123456789<br>
        Р/с 40702810123456789012 в БАНКЕ<br>
        БИК 044525225</p>

        <p><em>Счет действителен в течение 3 рабочих дней.</em></p>
        <p style='text-align: center; margin-top: 30px;'>
            <a href='#' onclick='window.print(); return false;' class='print-btn'>🖨️ Сохранить / Распечатать счет</a>
        </p>
        <div class='footer'>
            С уважением,<br>
            Ваша Компания<br>
            +7 (999) 123-45-67
        </div>
    </div>
</body>
</html>
";

// Подключаем PHPMailer
require_once 'mail_config.php';

$subject = "Счет на оплату №{$orderNumber} от " . date('d.m.Y');

// Отправка покупателю
$resultCustomer = sendInvoiceEmail($customerEmail, $customerName, $subject, $htmlContent);

// Отправка админу (уведомление)
$adminEmail = 'otetzalexandr1986@gmail.com'; // ← Ваша почта для теста
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
        <p>Счет №<?= $orderNumber ?> отправлен на <strong><?= $customerEmail ?></strong>.</p>
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
