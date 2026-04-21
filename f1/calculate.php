<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php');
  exit;
}

require_once 'configs/items.php';
require_once 'configs/addons.php';

// Функция для преобразования числа в сумму прописью
function num2words($num) {
  $nul = 'ноль';
  $ten = [
    ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
    ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять']
  ];
  $a20 = ['десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать'];
  $tens = ['', '', 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто'];
  $hundred = ['', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот'];
  $unit = [
    ['копейка', 'копейки', 'копеек', 1],
    ['рубль', 'рубля', 'рублей', 0],
    ['тысяча', 'тысячи', 'тысяч', 1],
    ['миллион', 'миллиона', 'миллионов', 0],
    ['миллиард', 'миллиарда', 'миллиардов', 0]
  ];

  if (!is_numeric($num)) return 'ноль рублей 00 копеек';

  $num = round($num, 2);
  list($rub, $kop) = explode('.', sprintf("%015.2f", $num));

  $out = [];
  if (intval($rub) > 0) {
    foreach (str_split($rub, 3) as $uk => $v) {
      if (!intval($v)) continue;

      $uk = sizeof($unit) - $uk - 1;
      $gender = $unit[$uk][3];

      list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));

      $out[] = $hundred[$i1];
      if ($i2 > 1) {
        $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3];
      } else {
        $out[] = ($i2 > 0) ? $a20[$i3] : $ten[$gender][$i3];
      }

      if ($uk > 1) $out[] = morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
    }
  } else {
    $out[] = $nul;
  }

  $out[] = morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]);
  $out[] = $kop . ' ' . morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]);

  return trim(preg_replace('/ {2,}/', ' ', implode(' ', $out)));
}

function morph($n, $f1, $f2, $f5) {
  $n = abs(intval($n)) % 100;
  if ($n > 10 && $n < 20) return $f5;
  $n = $n % 10;
  if ($n > 1 && $n < 5) return $f2;
  if ($n == 1) return $f1;
  return $f5;
}

// Данные товаров
$items = getItems();
$addons = getAddons();

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
$itemsList = []; // Для хранения позиций счета

// Добавляем основной тариф
$itemsList[] = [
    'name' => $items[$tariffKey]['name'],
    'quantity' => $quantity,
    'unit' => 'мес.',
    'price' => $items[$tariffKey]['price'],
    'sum' => $items[$tariffKey]['price'] * $quantity
];

foreach ($selectedAddons as $addonKey) {
  if (isset($addons[$addonKey])) {
    $subtotal += $addons[$addonKey]['price'];
    $addonDetails[] = $addons[$addonKey]['name'] . ' (' . number_format($addons[$addonKey]['price'], 0, ',', ' ') . ' ₽)';
    
    $itemsList[] = [
        'name' => $addons[$addonKey]['name'],
        'quantity' => $quantity,
        'unit' => 'мес.',
        'price' => $addons[$addonKey]['price'],
        'sum' => $addons[$addonKey]['price'] * $quantity
    ];
  }
}
$total = $subtotal * $quantity;

$orderDate = date('d.m.Y');
$orderDateFull = date('d.m.Y H:i');
$orderNumber = 'INV-' . date('Ymd-His');
$dueDate = date('d.m.Y', strtotime('+3 days'));

// Формируем строку с товарами для счета
$itemsRows = '';
$rowNum = 1;
foreach ($itemsList as $item) {
    $itemsRows .= '
                    <div class="pdf24_01" style="left:3.62em;top:' . (23.9968 + ($rowNum-1)*2.16) . 'em;"><span class="pdf24_16 pdf24_08 pdf24_14">' . $rowNum . '</span></div>
                    <div class="pdf24_01" style="left:4.97em;top:' . (23.9968 + ($rowNum-1)*2.16) . 'em;"><span class="pdf24_16 pdf24_08 pdf24_32" style="word-spacing:0.0014em;">' . htmlspecialchars($item['name']) . '</span></div>
                    <div class="pdf24_01" style="left:27.77em;top:' . (23.9968 + ($rowNum-1)*2.16) . 'em;"><span class="pdf24_16 pdf24_08 pdf24_12" style="word-spacing:0.1776em;">' . $item['quantity'] . ' ' . $item['unit'] . '</span></div>
                    <div class="pdf24_01" style="left:34.51em;top:' . (23.9968 + ($rowNum-1)*2.16) . 'em;"><span class="pdf24_16 pdf24_08 pdf24_41">' . number_format($item['price'], 2, ',', ' ') . '</span></div>
                    <div class="pdf24_01" style="left:39.81em;top:' . (23.9968 + ($rowNum-1)*2.16) . 'em;"><span class="pdf24_16 pdf24_08 pdf24_41" style="word-spacing:0.0002em;">' . number_format($item['sum'], 2, ',', ' ') . '</span></div>';
    $rowNum++;
}

// Загружаем шаблон счета
$template = file_get_contents('shet_obrasez.html');

// Заменяем плейсхолдеры в шаблоне
$replacements = [
    // Номер счета и дата
    '№ 21' => '№ ' . $orderNumber,
    'от 30 июня 2025 г.' => 'от ' . date('d') . ' ' . getMonthName(date('m')) . ' ' . date('Y') . ' г.',
    
    // Данные покупателя
    'ИП Васильева Наталья Александровна, ИНН 745100161206, 454045, Челябинская' => htmlspecialchars($customerName) . ', ',
    'область, г Челябинск, ул Потребительская 2-я, д. 42' => '',
    
    // Суммы
    '10 880,00' => number_format($total, 2, ',', ' '),
    
    // Сумма прописью (обновим через поиск точной фразы)
    'Десять тысяч восемьсот восемьдесят рублей 00 копеек' => num2words($total),
    
    // Дата оплаты
    '03.07.2025' => $dueDate,
    
    // Количество наименований
    'Всего наименований 1' => 'Всего наименований ' . count($itemsList),
];

// Применяем замены
$htmlContent = str_replace(array_keys($replacements), array_values($replacements), $template);

// Заменяем блок с товарами - найдем позицию и вставим новые строки
// Упростим - создадим полную замену секции товаров
$pattern = '/<div class="pdf24_01" style="left:3.62em;top:23.9968em;">.*?<\/div>.*?<div class="pdf24_01" style="left:39.81em;top:23.9968em;">.*?<\/div>/s';
if (preg_match($pattern, $htmlContent)) {
    $htmlContent = preg_replace($pattern, $itemsRows, $htmlContent);
}

// Обновляем данные покупателя более точно
if (!empty($customerName)) {
    $htmlContent = preg_replace(
        '/<div class="pdf24_01" style="left:8.57em;top:18.631em;">.*?<\/div>/s',
        '<div class="pdf24_01" style="left:8.57em;top:18.631em;"><span class="pdf24_29 pdf24_08 pdf24_35" style="word-spacing:0.0012em;">' . htmlspecialchars($customerName) . '</span></div>',
        $htmlContent
    );
}

// Вспомогательная функция для названия месяца
function getMonthName($month) {
    $months = [
        '01' => 'января', '02' => 'февраля', '03' => 'марта', '04' => 'апреля',
        '05' => 'мая', '06' => 'июня', '07' => 'июля', '08' => 'августа',
        '09' => 'сентября', '10' => 'октября', '11' => 'ноября', '12' => 'декабря'
    ];
    return $months[$month] ?? '';
}

// Подключаем PHPMailer
require_once 'mailer.php';

$subject = "Счет на оплату №{$orderNumber} от " . date('d.m.Y');

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
