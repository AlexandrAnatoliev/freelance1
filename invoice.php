<?php

declare(strict_types=1);

// раскомментировать для вывода ошибок на экран
require_once 'utils/debug.php';
require_once 'utils/session.php';

$items = $_SESSION['items_session'];
$addons = $_SESSION['addons_session'];

require_once 'configs/bankDetailsSettings.php';
$bankDetails  = getBankDetailsSettings();

/**
 * Преобразует число (сумму в рублях) в строку прописью.
 *
 * @param  $num - сумма, которую нужно преобразовать.
 *                Может быть числом с плавающей точкой
 *                (например, 1500.50) или строкой.
 * @return      - сумма прописью в формате:
 *                "одна тысяча пятьсот рублей 50 копеек"
 */
function num2words(float|int|string $num): string
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

/**
 * Вспомогательная функция для склонения слов в зависимости от числа.
 *
 * @param  $n   - число, для которого нужно подобрать форму слова
 * @param  $f1  - форма для числа 1 (рубль, копейка)
 * @param  $f2  - форма для чисел 2-4 (рубля, копейки)
 * @param  $f5  - форма для чисел 5-20 и 0 (рублей, копеек)
 * @return      - одна из трёх форм слова в зависимости от числа
 */
function morph(int|string $n, string $f1, string $f2, string $f5): string
{
    $n = abs(intval($n)) % 100;
    $answer = $f5;

    if ($n > 10 && $n < 20) {
        return $answer;
    }
    $n = $n % 10;
    if ($n > 1 && $n < 5) {
        $answer = $f2;
    } elseif ($n == 1) {
        $answer = $f1;
    }
    return $answer;
}

/**
 * Возвращает текущую дату в русском формате с названием месяца прописью.
 *
 * @return - дата в формате "25 апреля 2026 г."
 */
function getCurrentRussianDate(): string
{
    $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

    return date('j') . ' ' . $months[date('n') - 1] . ' ' . date('Y') . ' г.';
}

/**
 * Основная функция. Формирует полный HTML-документ счёта на оплату.
 *
 * @param  $tariffKey       - ключ выбранного тарифа (напр. 'standart')
 * @param  $selectedAddons  - массив ключей выбранных аддонов (напр. ['support'])
 * @param  $quantity        - количество месяцев
 * @param  $customerName    - название организации/имя покупателя
 * @param  $orderNumber     - номер счёта (напр. 'Б-20260425-153045')
 * @return                  - готовый HTML-документ счёта со всеми стилями и данными.
 *                            Может быть отправлен в письме или показан на странице.
 */
function getInvoice(
    string $tariffKey,
    array $selectedAddons,
    int $quantity,
    string $customerName,
    string $customerPhone,
    string $orderNumber
): string {
    global $items;
    global $addons;
    global $bankDetails;
    $htmlInvoice = '<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Счёт на оплату · банковские реквизиты</title>
  <style>';

    $htmlInvoice .= getBodyStyle();
    $htmlInvoice .= getMainTableStyle();
    $htmlInvoice .= getMiddleTableStyle();
    $htmlInvoice .= getItemsTableStyle();

    $htmlInvoice .= '
  </style>
</head>';

    $htmlInvoice .= '
<body>
<div class="table-wrapper">';

    $htmlInvoice .= getMainTableHTML($bankDetails);

    $htmlInvoice .= '
  <div class="empty-line"></div>

  <div class="invoice-header">
    Счет на оплату № ' . $orderNumber . ' от ' . getCurrentRussianDate() . '
  </div>

  <div class="empty-line"></div>

  <div class="divider"></div>';

    // '89261234567';
    $phone = preg_replace('/\D/', '', $customerPhone);          // 89261234567
    $phone = '+7' . substr($phone, 1);                     // +79261234567

    $formatted = sprintf(
        '+7 (%s) %s-%s-%s',
        substr($phone, 2, 3),   // 926
        substr($phone, 5, 3),   // 123
        substr($phone, 8, 2),   // 45
        substr($phone, 10, 2)   // 67
    );
    // +7 (926) 123-45-67

    $htmlInvoice .= '
  <table class="middle-table">
    <tr>
      <td class="label-cell">Поставщик<br>(Исполнитель):</td>
      <td class="value-cell">' . $bankDetails['ip_full_name'] . '</td>
    </tr>
    <tr>
      <td class="label-cell">Покупатель<br>(Заказчик):</td>
      <td class="value-cell">' . $customerName . ', тел: ' . $formatted . '</td>
    </tr>
    <tr>
      <td class="label-cell">Основание:</td>
      <td class="value-cell">' . $bankDetails['payment_basis'] . '</td>
    </tr>
  </table>';

    $htmlInvoice .= '
  <table class="items-table">
    <thead>
      <tr>
        <th class="col-right">№</th>
        <th class="col-left">Товары (работы, услуги)</th>
        <th class="col-right">Кол-во</th>
        <th class="col-center">Ед.</th>
        <th class="col-right">Цена</th>
        <th class="col-right">Сумма</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="col-right">1</td>
        <td class="col-left">' . $items[$tariffKey]['name'] . '</td>
        <td class="col-right">' . $quantity . '</td>
        <td class="col-center">шт.</td>
        <td class="col-right">' . number_format($items[$tariffKey]['price'], 2, ',', ' ') . '</td>
        <td class="col-right">' . number_format($items[$tariffKey]['price'] * $quantity, 2, ',', ' ') . '</td>
      </tr>';

    $total = $items[$tariffKey]['price'] * $quantity;
    $rowNumber = 1;

    foreach ($selectedAddons as $addonKey) {
        if (isset($addons[$addonKey])) {
            $rowNumber++;
            $addonPrice = $addons[$addonKey]['price'];
            $addonSum = $addonPrice * $quantity;
            $total += $addonSum;

            $htmlInvoice .= '
          <tr>
            <td class="col-right">' . $rowNumber . '</td>
            <td class="col-left">' . htmlspecialchars($addons[$addonKey]['name']) . '</td>
            <td class="col-right">' . $quantity . '</td>
            <td class="col-center">шт.</td>
            <td class="col-right">' . number_format($addonPrice, 2, ',', ' ') . '</td>
            <td class="col-right">' . number_format($addonSum, 2, ',', ' ') . '</td>
          </tr>';
        }
    }

    $htmlInvoice .= '
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" style="text-align:right; font-weight:bold;">Итого:</td>
        <td class="col-right" style="font-weight:bold;">' . number_format($total, 2, ',', ' ') . '</td>
      </tr>
      <tr>
        <td colspan="5" style="text-align:right; font-weight:bold;">В том числе НДС:</td>
        <td class="col-right" style="font-weight:bold;">—</td>
      </tr>
      <tr>
        <td colspan="5" style="text-align:right; font-weight:bold;">Всего к оплате:</td>
        <td class="col-right" style="font-weight:bold;">' . number_format($total, 2, ',', ' ') . '</td>
      </tr>
    </tfoot>
  </table>';

    $totalInWords = num2words($total);

    $htmlInvoice .= '
  <div class="empty-line"></div>

  <p>Всего наименований ' . $rowNumber . ', на сумму ' . number_format($total, 2, ',', ' ') . ' руб<br>
  (<b>' . $totalInWords . '</b>)</p>

  <div class="empty-line"></div>';

    // Текущая дата + 3 дня
    $htmlInvoice .= '
  <p>Оплатить не позднее ' . date('d.m.Y', strtotime('+3 days')) . '<br>
  Оплата данного счета означает согласие с условиями поставки товара.<br>
  Уведомление об оплате обязательно, в противном случае не гарантируется наличие товара на складе.<br>
  Товар отпускается по факту прихода денег на р/с Поставщика, самовывозом, при наличии доверенности и паспорта.</p>

  <div class="empty-line"></div>
  <div class="divider"></div>

  <p><b>Предприниматель</b>______________________________________________' . $bankDetails['entrepreneurs_surname'] . '</p>
</div>
</body>
</html>';
    return $htmlInvoice;
}

/**
 * Формирует email сообщение счёта на оплату.
 *
 * @param  $tariffKey       - ключ выбранного тарифа (напр. 'standart')
 * @param  $selectedAddons  - массив ключей выбранных аддонов (напр. ['support'])
 * @param  $quantity        - количество месяцев
 * @param  $orderNumber     - номер счёта (напр. 'Б-20260425-153045')
 * @return                  - готовый HTML-документ счёта со всеми стилями и данными.
 *                            Может быть отправлен в письме или показан на странице.
 */
function getEmailMessage(
    string $tariffKey,
    array $selectedAddons,
    int $quantity,
    string $orderNumber
): string {
    global $items;
    global $addons;
    global $bankDetails;
    $emailMessage = '<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Счёт на оплату · банковские реквизиты</title>
  <style>';

    $emailMessage .= getBodyStyle();
    $emailMessage .= getMiddleTableStyle();
    $emailMessage .= getItemsTableStyle();

    $emailMessage .= '
  </style>
</head>';

    $emailMessage .= '
<body>
<div class="table-wrapper">
  <div class="empty-line"></div>

  <div class="invoice-header">
    Счет на оплату № ' . $orderNumber . ' от ' . getCurrentRussianDate() . '
  </div>

  <div class="empty-line"></div>

  <div class="divider"></div>';

    $emailMessage .= '
  <table class="middle-table">
    <tr>
      <td class="label-cell">Поставщик<br>(Исполнитель):</td>
      <td class="value-cell">' . $bankDetails['ip_full_name'] . '</td>
    </tr>
    <tr>
  </table>';

    $emailMessage .= '
  <table class="items-table">
    <thead>
      <tr>
        <th class="col-right">№</th>
        <th class="col-left">Товары (работы, услуги)</th>
        <th class="col-right">Кол-во</th>
        <th class="col-right">Цена/шт.</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="col-right">1</td>
        <td class="col-left">' . $items[$tariffKey]['name'] . '</td>
        <td class="col-right">' . $quantity . ' шт.</td>
        <td class="col-right">' . number_format($items[$tariffKey]['price'], 2, ',', ' ') . '</td>
      </tr>';

    $total = $items[$tariffKey]['price'] * $quantity;
    $rowNumber = 1;

    foreach ($selectedAddons as $addonKey) {
        if (isset($addons[$addonKey])) {
            $rowNumber++;
            $addonPrice = $addons[$addonKey]['price'];
            $addonSum = $addonPrice * $quantity;
            $total += $addonSum;

            $emailMessage .= '
          <tr>
            <td class="col-right">' . $rowNumber . '</td>
            <td class="col-left">' . htmlspecialchars($addons[$addonKey]['name']) . '</td>
            <td class="col-right">' . $quantity . ' шт.</td>
            <td class="col-right">' . number_format($addonPrice, 2, ',', ' ') . '</td>
          </tr>';
        }
    }

    $emailMessage .= '
    </tbody>
  </table>';

    $totalInWords = num2words($total);

    $emailMessage .= '
  <div class="empty-line"></div>

  <p>Всего наименований ' . $rowNumber . ', на сумму ' . number_format($total, 2, ',', ' ') . ' руб<br>
  (' . $totalInWords . ')</p>

  <div class="empty-line"></div>';

    // Текущая дата + 3 дня
    $emailMessage .= '
  <p>Оплатить не позднее ' . date('d.m.Y', strtotime('+3 days')) . '<br>
  Оплата данного счета означает согласие с условиями поставки товара.<br>
  Уведомление об оплате обязательно, в противном случае не гарантируется наличие товара на складе.<br>
  Товар отпускается по факту прихода денег на р/с Поставщика, самовывозом, при наличии доверенности и паспорта.</p>
</div>
</body>
</html>';
    return $emailMessage;
}

/**
 * Основная функция. Формирует адаптивный HTML-документ счёта на оплату
 *
 * @param  $tariffKey       - ключ выбранного тарифа (напр. 'standart')
 * @param  $selectedAddons  - массив ключей выбранных аддонов (напр. ['support'])
 * @param  $quantity        - количество месяцев
 * @param  $customerName    - название организации/имя покупателя
 * @param  $orderNumber     - номер счёта (напр. 'Б-20260425-153045')
 * @return                  - готовый HTML-документ счёта со всеми стилями и данными.
 *                            Может быть отправлен в письме или показан на странице.
 */
function getResponsibleInvoice(
    string $tariffKey,
    array $selectedAddons,
    int $quantity,
    string $customerName,
    string $orderNumber
): string {
    global $items;
    global $addons;
    global $bankDetails;
    $responsibleInvoice = '<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Счёт на оплату · банковские реквизиты</title>
  <style>';

    $responsibleInvoice .= getBodyStyle();
    $responsibleInvoice .= getMainTableStyle();
    $responsibleInvoice .= getMiddleTableStyle();
    $responsibleInvoice .= getItemsTableStyle();

    $responsibleInvoice .= '
    .hide-on-mobile {
      /* По умолчанию показываем */
      display: block;
    }
    
    /* Скрываем на экранах меньше 768px */
    @media screen and (max-width: 767px) {
      .hide-on-mobile {
        display: none;
      }
    }
    
    /* Или наоборот - показываем только на мобильных */
    .show-only-mobile {
      display: none;
    }
    
    @media screen and (max-width: 767px) {
      .show-only-mobile {
        display: block;
      }
    }
  </style>
</head>';

    $responsibleInvoice .= '
<body>
<div class="table-wrapper">
  <div class="hide-on-mobile">';

    $responsibleInvoice .= getMainTableHTML($bankDetails);

    $responsibleInvoice .= '
  <div class="empty-line"></div>

  <div class="invoice-header">
    Счет на оплату № ' . $orderNumber . ' от ' . getCurrentRussianDate() . '
  </div>

  <div class="empty-line"></div>

  <div class="divider"></div>';

    $responsibleInvoice .= '
  <table class="middle-table">
    <tr>
      <td class="label-cell">Поставщик<br>(Исполнитель):</td>
      <td class="value-cell">' . $bankDetails['ip_full_name'] . '</td>
    </tr>
  </table>
  <table class="middle-table hide-on-mobile">
    <tr>
      <td class="label-cell">Покупатель<br>(Заказчик):</td>
      <td class="value-cell">' . $customerName . '</td>
    </tr>
    <tr>
      <td class="label-cell">Основание:</td>
      <td class="value-cell">' . $bankDetails['payment_basis'] . '</td>
    </tr>
  </table>';

    $responsibleInvoice .= '
  <div class="hide-on-mobile">
    <table class="items-table">
      <thead>
        <tr>
          <th class="col-right">№</th>
          <th class="col-left">Товары (работы, услуги)</th>
          <th class="col-right">Кол-во</th>
          <th class="col-center">Ед.</th>
          <th class="col-right">Цена</th>
          <th class="col-right">Сумма</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="col-right">1</td>
          <td class="col-left">' . $items[$tariffKey]['name'] . '</td>
          <td class="col-right">' . $quantity . '</td>
          <td class="col-center">шт.</td>
          <td class="col-right">' . number_format($items[$tariffKey]['price'], 2, ',', ' ') . '</td>
          <td class="col-right">' . number_format($items[$tariffKey]['price'] * $quantity, 2, ',', ' ') . '</td>
        </tr>';

    $total = $items[$tariffKey]['price'] * $quantity;
    $rowNumber = 1;

    foreach ($selectedAddons as $addonKey) {
        if (isset($addons[$addonKey])) {
            $rowNumber++;
            $addonPrice = $addons[$addonKey]['price'];
            $addonSum = $addonPrice * $quantity;
            $total += $addonSum;

            $responsibleInvoice .= '
          <tr>
            <td class="col-right">' . $rowNumber . '</td>
            <td class="col-left">' . htmlspecialchars($addons[$addonKey]['name']) . '</td>
            <td class="col-right">' . $quantity . '</td>
            <td class="col-center">шт.</td>
            <td class="col-right">' . number_format($addonPrice, 2, ',', ' ') . '</td>
            <td class="col-right">' . number_format($addonSum, 2, ',', ' ') . '</td>
          </tr>';
        }
    }

    $responsibleInvoice .= '
      </tbody>
      <tfoot>
        <tr>
          <td colspan="5" style="text-align:right; font-weight:bold;">Итого:</td>
          <td style="font-weight:bold;">' . number_format($total, 2, ',', ' ') . '</td>
        </tr>
        <tr>
          <td colspan="5" style="text-align:right; font-weight:bold;">В том числе НДС:</td>
          <td style="font-weight:bold;">—</td>
        </tr>
        <tr>
          <td colspan="5" style="text-align:right; font-weight:bold;">Всего к оплате:</td>
          <td style="font-weight:bold;">' . number_format($total, 2, ',', ' ') . '</td>
        </tr>
      </tfoot>
    </table>
  </div>';

    $responsibleInvoice .= '
  <div class="show-only-mobile">
    <table class="items-table">
      <thead>
        <tr>
          <th class="col-right">№</th>
          <th class="col-left">Товары (работы, услуги)</th>
          <th class="col-right">Кол-во</th>
          <th class="col-right">Цена/шт.</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="col-right">1</td>
          <td class="col-left">' . $items[$tariffKey]['name'] . '</td>
          <td class="col-right">' . $quantity . ' шт.</td>
          <td class="col-right">' . number_format($items[$tariffKey]['price'], 2, ',', ' ') . '</td>
        </tr>';

    $total = $items[$tariffKey]['price'] * $quantity;
    $rowNumber = 1;

    foreach ($selectedAddons as $addonKey) {
        if (isset($addons[$addonKey])) {
            $rowNumber++;
            $addonPrice = $addons[$addonKey]['price'];
            $addonSum = $addonPrice * $quantity;
            $total += $addonSum;

            $responsibleInvoice .= '
          <tr>
            <td class="col-right">' . $rowNumber . '</td>
            <td class="col-left">' . htmlspecialchars($addons[$addonKey]['name']) . '</td>
            <td class="col-right">' . $quantity . ' шт.</td>
            <td class="col-right">' . number_format($addonPrice, 2, ',', ' ') . '</td>
          </tr>';
        }
    }

    $responsibleInvoice .= '
      </tbody>
    </table>
  </div>';

    $totalInWords = num2words($total);

    $responsibleInvoice .= '
  <div class="empty-line"></div>

  <p>Всего наименований ' . $rowNumber . ', на сумму ' . number_format($total, 2, ',', ' ') . ' руб<br>
  (' . $totalInWords . ')</p>

  <div class="empty-line"></div>';

    // Текущая дата + 3 дня
    $responsibleInvoice .= '
  <div class="hide-on-mobile">
    <p>Оплатить не позднее ' . date('d.m.Y', strtotime('+3 days')) . '<br>
    Оплата данного счета означает согласие с условиями поставки товара.<br>
    Уведомление об оплате обязательно, в противном случае не гарантируется наличие товара на складе.<br>
    Товар отпускается по факту прихода денег на р/с Поставщика, самовывозом, при наличии доверенности и паспорта.</p>

    <div class="empty-line"></div>
    <div class="divider"></div>

    <p>Предприниматель______________________________________________' . $bankDetails['entrepreneurs_surname'] . '</p>
  </div>
</div>
</body>
</html>';
    return $responsibleInvoice;
}

function getBodyStyle(): string
{
    $bodyStyle = '
    * {
      margin: 0;
      padding: 0;
    }

    body {
      font-family: "DejaVu Sans", DejaVu, sans-serif;
      color: #000;
      background: #fff;
      margin: 10px;
    }

    .table-wrapper {
      background: #fff;
      padding: 10px;
      width: 90%;
      margin: 0 auto;
    }

    .empty-line {
      height: 20px;
    }

    .divider {
      width: 100%;
      height: 2px;
      background-color: #000;
      margin-bottom: 12px;
    }

    .invoice-header {
      font-weight: bold;
      font-size: 1.2rem;
      text-align: left;
      margin-bottom: 6px;
    }';

    return $bodyStyle;
}

function getMainTableStyle(): string
{
    $mainTableStyle = '
    .main-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }

    .main-table td {
      border: 2px solid #000;
      padding: 1px;
      background-color: #fff;
      vertical-align: middle;
    }

    .cell-bank-name {
      width: 67%;
      line-height: 1;
      text-align: left;
    }

    .cell-bik-label {
      width: 9%;
      text-align: left;
    }

    .cell-bik-value {
      width: 24%;
      text-align: left;
    }

    .cell-inn-kpp {
      text-align: center;
    }

    .inn-cell {
      display: inline-block;
      width: 59%;
      border-right: 2px solid #000;
      text-align: left;
    }

    .kpp-cell {
      display: inline-block;
      width: 39%;
      text-align: left;
    }

    .cell-account-label {
      vertical-align: top;
      text-align: left;
    }

    .cell-account-value {
      vertical-align: top;
      text-align: left;
    }

    .cell-recipient {
      line-height: 1.3;
      text-align: left;
    }

    p {
      font-size: 0.85rem;
      line-height: 1;
    }';

    return $mainTableStyle;
}

function getMiddleTableStyle(): string
{
    $middleTableStyle = '
    .middle-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
      margin-top: 6px;
    }

    .middle-table td {
      border: none;
      padding: 4px 6px;
      vertical-align: top;
      line-height: 1;
    }

    .label-cell {
      width: 14%;
      text-align: left;
    }

    .value-cell {
      width: 86%;
      font-weight: bold;
      text-align: left;
    }';

    return $middleTableStyle;
}

function getItemsTableStyle(): string
{
    $itemsTableStyle = '
    .items-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
      margin-top: 16px;
      border: 2px solid #000;
    }

    .items-table th,
    .items-table td {
      border: 1px solid #000;
      padding: 2px;
      vertical-align: top;
      background-color: #fff;
    }

    .items-table th {
      font-weight: bold;
      text-align: center;
      background-color: #f2f2f2;
    }

    .col-right {
      text-align: right;
    }

    .col-left {
      text-align: left;
    }

    .col-center {
      text-align: center;
    }';

    return $itemsTableStyle;
}

function getMainTableHTML(array $bankDetails): string
{
    $mainTableHTML = '
  <!-- ПЕРВАЯ ТАБЛИЦА — банковские реквизиты -->
  <table class="main-table">
    <tr>
      <td class="cell-bank-name" style="border-bottom: none;">
        ' . $bankDetails['recipient_bank'] . '<br><br>
      </td>
      <td class="cell-bik-label" style="vertical-align: top;">БИК</td>
      <td class="cell-bik-value" style="border-bottom: none; vertical-align: top;">
        ' . $bankDetails['bank_identification_code'] . '
      </td>
    </tr>
    <tr>
      <td class="cell-bank-name" style="border-top: none;">Банк получателя</td>
      <td class="cell-bik-label">Сч. №</td>
      <td class="cell-bik-value" style="border-top: none;">
        ' . $bankDetails['correspondent_bank_account'] . '
      </td>
    </tr>
    <tr>
      <td class="cell-inn-kpp">
        <span class="inn-cell">ИНН ' . $bankDetails['inn'] . '</span>
        <span class="kpp-cell">КПП ' . $bankDetails['kpp'] . '</span>
      </td>
      <td class="cell-account-label" style="vertical-align: top;" rowspan="2">Сч. №</td>
      <td class="cell-account-value" style="vertical-align: top;" rowspan="2">
        ' . $bankDetails['recipients_bank_account'] . '
      </td>
    </tr>
    <tr>
      <td class="cell-recipient">' . $bankDetails['ip_name'] . '<br><br>Получатель</td>
    </tr>
  </table>';

    return $mainTableHTML;
}
