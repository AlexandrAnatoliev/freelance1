<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once 'configs/items.php';
require_once 'configs/addons.php';

// Функция для преобразования числа в сумму прописью
function num2words($num)
{
    $nul = 'ноль';
    $ten = [
        ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
        ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
    ];
    $a20 = ['десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать'];
    $tens = ['', '', 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто'];
    $hundred = ['', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот'];
    $unit = [
        ['копейка', 'копейки', 'копеек', 1],
        ['рубль', 'рубля', 'рублей', 0],
        ['тысяча', 'тысячи', 'тысяч', 1],
        ['миллион', 'миллиона', 'миллионов', 0],
        ['миллиард', 'миллиарда', 'миллиардов', 0],
    ];

    if (!is_numeric($num)) {
        return 'ноль рублей 00 копеек';
    }

    $num = round($num, 2);
    [$rub, $kop] = explode('.', sprintf("%015.2f", $num));

    $out = [];
    if (intval($rub) > 0) {
        foreach (str_split($rub, 3) as $uk => $v) {
            if (!intval($v)) {
                continue;
            }

            $uk = sizeof($unit) - $uk - 1;
            $gender = $unit[$uk][3];

            [$i1, $i2, $i3] = array_map('intval', str_split($v, 1));

            $out[] = $hundred[$i1];
            if ($i2 > 1) {
                $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3];
            } else {
                $out[] = ($i2 > 0) ? $a20[$i3] : $ten[$gender][$i3];
            }

            if ($uk > 1) {
                $out[] = morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
            }
        }
    } else {
        $out[] = $nul;
    }

    $out[] = morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]);
    $out[] = $kop . ' ' . morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]);

    return trim(preg_replace('/ {2,}/', ' ', implode(' ', $out)));
}

function morph($n, $f1, $f2, $f5)
{
    $n = abs(intval($n)) % 100;
    if ($n > 10 && $n < 20) {
        return $f5;
    }
    $n = $n % 10;
    if ($n > 1 && $n < 5) {
        return $f2;
    }
    if ($n == 1) {
        return $f1;
    }
    return $f5;
}

// Данные товаров
$items = getItems();
$addons = getAddons();

// Получаем данные из формы
$tariffKey = $_POST['tariff'] ?? null;
$selectedAddons = $_POST['addons'] ?? [];
$quantity = (int) ($_POST['quantity'] ?? 1);
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
    body {
        font-family: 'Times New Roman', Times, serif;
        color: #000;
        background: #fff;
        padding: 20px;
    }
    .invoice-box {
        max-width: 800px;
        margin: auto;
        padding: 30px;
        border: 1px solid #000;
        background: #fff;
    }
    h2 {
        color: #000;
        text-align: center;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 20px;
        font-size: 20px;
    }
    table {
        width: 100%;
        line-height: 1.5;
        border-collapse: collapse;
        margin: 20px 0;
        border: 1px solid #000;
    }
    td, th {
        padding: 8px 10px;
        border: 1px solid #000;
        text-align: left;
        vertical-align: top;
    }
    th {
        background: #e0e0e0;
        font-weight: bold;
        text-align: center;
        text-transform: uppercase;
        font-size: 14px;
    }
    .total {
        font-size: 1.2rem;
        font-weight: bold;
        text-align: right;
    }
    .footer {
        margin-top: 30px;
        font-size: 0.9rem;
        color: #000;
        border-top: 1px solid #000;
        padding-top: 15px;
    }
    .print-btn {
        background: #ccc;
        color: #000;
        padding: 8px 15px;
        text-decoration: none;
        border: 1px solid #000;
        display: inline-block;
        font-weight: normal;
        text-transform: uppercase;
        font-size: 14px;
    }
    .print-btn:hover {
        background: #aaa;
    }
    .company-details {
        margin-bottom: 20px;
        border-bottom: 1px solid #000;
        padding-bottom: 10px;
    }
    .invoice-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    .invoice-number {
        font-weight: bold;
        font-size: 16px;
    }
    .signature-line {
        display: flex;
        justify-content: space-between;
        margin-top: 50px;
        font-size: 14px;
    }
    .signature-item {
        width: 45%;
        border-top: 1px solid #000;
        padding-top: 5px;
        text-align: center;
    }
    .bank-details {
        font-family: 'Courier New', monospace;
        background: #f8f8f8;
        padding: 10px;
        border: 1px solid #000;
        margin: 20px 0;
        font-size: 13px;
    }
    .total-row {
        background: #e0e0e0;
        font-weight: bold;
    }
    .text-center {
        text-align: center;
    }
    .text-right {
        text-align: right;
    }
    .uppercase {
        text-transform: uppercase;
    }
</style>
</head>
<body>
<div class='invoice-box'>
    <div class='invoice-header'>
        <div class='company-name'><strong>ООО «ВАША КОМПАНИЯ»</strong></div>
        <div class='invoice-number'>СЧЕТ-ФАКТУРА № {$orderNumber} от {$orderDate}</div>
    </div>

    <div class='company-details'>
        <strong>Поставщик:</strong> ООО «Ваша Компания», ИНН 1234567890, КПП 123456789<br>
        <strong>Адрес:</strong> 123456, г. Москва, ул. Примерная, д. 1, офис 101<br>
        <strong>Тел:</strong> +7 (999) 123-45-67
    </div>

    <div style='margin-bottom: 20px;'>
        <strong>Покупатель:</strong> {$customerName}<br>
        <strong>Email:</strong> {$customerEmail}<br>
        <strong>Телефон:</strong> {$customerPhone}
    </div>

    <table>
        <thead>
            <tr>
                <th style='width: 5%;'>№</th>
                <th style='width: 35%;'>Наименование товара (работ, услуг)</th>
                <th style='width: 10%;'>Кол-во</th>
                <th style='width: 10%;'>Ед.</th>
                <th style='width: 20%;'>Цена (₽)</th>
                <th style='width: 20%;'>Сумма (₽)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class='text-center'>1</td>
                <td>{$items[$tariffKey]['name']}</td>
                <td class='text-center'>{$quantity}</td>
                <td class='text-center'>мес.</td>
                <td class='text-right'>" . number_format($items[$tariffKey]['price'], 2, ',', ' ') . "</td>
                <td class='text-right'>" . number_format($items[$tariffKey]['price'] * $quantity, 2, ',', ' ') . "</td>
            </tr>";

$rowNum = 2;
foreach ($selectedAddons as $addonKey) {
    if (isset($addons[$addonKey])) {
        $htmlContent .= "
            <tr>
                <td class='text-center'>{$rowNum}</td>
                <td>{$addons[$addonKey]['name']}</td>
                <td class='text-center'>{$quantity}</td>
                <td class='text-center'>мес.</td>
                <td class='text-right'>" . number_format($addons[$addonKey]['price'], 2, ',', ' ') . "</td>
                <td class='text-right'>" . number_format($addons[$addonKey]['price'] * $quantity, 2, ',', ' ') . "</td>
            </tr>";
        $rowNum++;
    }
}

$htmlContent .= "
            <tr class='total-row'>
                <td colspan='5' class='text-right'><strong>ИТОГО:</strong></td>
                <td class='text-right'><strong>" . number_format($total, 2, ',', ' ') . "</strong></td>
            </tr>
            <tr>
                <td colspan='5' class='text-right'>В том числе НДС:</td>
                <td class='text-right'>—</td>
            </tr>
        </tbody>
    </table>

    <div class='bank-details'>
        <strong>БАНКОВСКИЕ РЕКВИЗИТЫ:</strong><br>
        Получатель: ООО «Ваша Компания»<br>
        ИНН 1234567890 / КПП 123456789<br>
        Р/с 40702810123456789012 в ПАО «БАНК» г. Москва<br>
        К/с 30101810145250000411, БИК 044525225
    </div>

    <div style='margin: 20px 0;'>
        <p><strong>Всего к оплате:</strong> " . number_format($total, 2, ',', ' ') . " руб.</p>
        <p><em>" . num2words($total) . "</em></p>
        <p>Счет действителен до: " . date('d.m.Y', strtotime('+3 days')) . "</p>
    </div>

    <div class='signature-line'>
        <div class='signature-item'>
            Руководитель ______________ / Иванов И.И. /
        </div>
        <div class='signature-item'>
            Главный бухгалтер ______________ / Петрова П.П. /
        </div>
    </div>

    <div style='margin-top: 20px; font-size: 12px;'>
        <p>М.П.</p>
    </div>

    <div class='footer'>
        <p style='text-align: center; margin-top: 30px;'>
            <a href='#' onclick='window.print(); return false;' class='print-btn'>🖨️ ПЕЧАТЬ / СОХРАНИТЬ В PDF</a>
        </p>
        <p style='font-size: 12px; margin-top: 10px;'>Счет-фактура является основанием для оплаты. При оплате укажите номер счета.</p>
    </div>
</div>

</body>
</html>
";

// Подключаем PHPMailer
require_once 'mailer.php';

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
