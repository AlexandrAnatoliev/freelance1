<?php

session_start();
$items = $_SESSION['items_session'];
$addons = $_SESSION['addons_session'];

require_once 'configs/bankDetailsSettings.php';
$bankDetails  = getBankDetailsSettings();

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

function getCurrentRussianDate()
{
    $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

    return date('j') . ' ' . $months[date('n') - 1] . ' ' . date('Y') . ' г.';
}

function getInvoice($tariffKey, $selectedAddons, $quantity, $customerName, $orderNumber)
{
    global $items;
    global $addons;
    global $bankDetails;
    $htmlInvoice = '<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Счёт на оплату · банковские реквизиты</title>
    <style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

    body {
      font-family: Arial, Helvetica, sans-serif;
      color: #000000;
      background: #f8f9fa;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 16px;
    }

    .table-wrapper {
      background: #ffffff;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 20px 20px 24px 20px;
      max-width: 1000px;
      width: 100%;
    }

    /* заголовок счёта */
    .invoice-header {
      font-family: Arial, Helvetica, sans-serif;
      font-weight: bold;
      font-size: 1.4rem;
      color: #000000;
      text-align: left;
      margin-bottom: 8px;
      letter-spacing: 0.3px;
    }

    /* пустая строка */
    .empty-line {
      height: 12px;
    }

    /* разделительная полоса */
    .divider {
      width: 100%;
      height: 2px;
      background-color: #000000;
      margin-bottom: 20px;
    }

    /* главная таблица — пропорции 9 : 1 : 3 */
    .main-table {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 0.95rem;
      color: #000000;
      text-align: center;
      table-layout: fixed;
    }

    /* ширина колонок */
    .main-table colgroup col:first-child {
      width: 69.23%;
    }
    .main-table colgroup col:nth-child(2) {
      width: 7.69%;
    }
    .main-table colgroup col:nth-child(3) {
      width: 23.08%;
    }

    /* все ячейки главной таблицы */
    .main-table td {
      border: 2px solid #000000;
      padding: 0;
      background-color: #ffffff;
      vertical-align: middle;
    }

    /* обычные ячейки — уменьшенные вертикальные отступы */
    .main-table td:not(.split-cell-vertical):not(.split-cell-horizontal) {
      padding: 8px 10px;
    }

    /* ячейка A1 */
    .cell-a1 {
      padding: 8px 12px !important;
      line-height: 1.4;
      text-align: left !important;
      font-size: 0.95rem;
    }

    /* ячейка A3 */
    .cell-a3 {
      padding: 8px 12px !important;
      line-height: 1.4;
      text-align: left !important;
      font-size: 0.95rem;
    }

    /* ячейка C1 */
    .cell-c1 {
      padding: 0 !important;
    }

    /* объединённые ячейки */
    .merged-cell {
      padding: 8px 10px !important;
      font-size: 0.95rem;
      vertical-align: top !important;
    }

    /* ячейка для счёта получателя — прижато к верху */
    .account-cell {
      padding: 8px 12px !important;
      font-size: 0.95rem;
      letter-spacing: 0.3px;
      vertical-align: top !important;
    }

    /* ячейки со вложенными таблицами */
    .split-cell-horizontal,
    .split-cell-vertical {
      padding: 0 !important;
    }

    /* ===== ВЛОЖЕННАЯ ТАБЛИЦА B1 ===== */
    .inner-table-b1 {
      width: 100%;
      height: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif;
      color: #000000;
      font-size: 0.95rem;
      table-layout: fixed;
    }

    .inner-table-b1 td {
      border: none;
      padding: 6px 4px;
      text-align: center;
      vertical-align: middle;
      background-color: #ffffff;
      font-size: 0.95rem;
    }

    .inner-table-b1 tr:first-child td {
      border-bottom: 2px solid #000000;
    }

    /* ===== ВЛОЖЕННАЯ ТАБЛИЦА C1 ===== */
    .inner-table-c1 {
      width: 100%;
      height: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif;
      color: #000000;
      font-size: 0.95rem;
      table-layout: fixed;
    }

    .inner-table-c1 td {
      border: none;
      padding: 6px 8px;
      text-align: left;
      vertical-align: middle;
      background-color: #ffffff;
      letter-spacing: 0.2px;
      font-size: 0.95rem;
    }

    .inner-table-c1 tr:first-child td {
      border-bottom: 2px solid #000000;
    }

    /* ===== ВЛОЖЕННАЯ ТАБЛИЦА A2 ===== */
    .inner-table-a2 {
      width: 100%;
      height: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif;
      color: #000000;
      font-size: 0.95rem;
      table-layout: fixed;
    }

    .inner-table-a2 td {
      border: none;
      padding: 8px 6px;
      text-align: center;
      vertical-align: middle;
      background-color: #ffffff;
      font-size: 0.95rem;
    }

    .inner-table-a2 td:first-child {
      border-right: 2px solid #000000;
    }

    /* ===== НИЖНЯЯ ТАБЛИЦА 2x3 (без видимых рамок) ===== */
    .bottom-table {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 0.95rem;
      color: #000000;
      table-layout: fixed;
      margin-top: 8px;
    }

    /* ширина колонок 1 : 7 */
    .bottom-table colgroup col:first-child {
      width: 12.5%;  /* 1 / 8 = 12.5% */
    }
    .bottom-table colgroup col:nth-child(2) {
      width: 87.5%;  /* 7 / 8 = 87.5% */
    }

    .bottom-table td {
      border: none;
      padding: 6px 8px;
      background-color: transparent;
      vertical-align: top;
      line-height: 1.45;
    }

    /* первый столбец — обычный шрифт */
    .bottom-table td:first-child {
      font-weight: normal;
      text-align: left;
    }

    /* второй столбец — жирный шрифт */
    .bottom-table td:nth-child(2) {
      font-weight: bold;
      text-align: left;
    }

    /* ховер */
    .main-table td:hover,
    .inner-table-b1 td:hover,
    .inner-table-c1 td:hover,
    .inner-table-a2 td:hover {
      background-color: #f4f4f4;
    }

    /* адаптивность */
    @media (max-width: 700px) {
      .invoice-header {
        font-size: 1.2rem;
      }
      .main-table {
        font-size: 0.75rem;
      }
      .cell-a1, .cell-a3 {
        font-size: 0.75rem !important;
        padding: 6px 6px !important;
      }
      .inner-table-b1 td,
      .inner-table-c1 td {
        padding: 5px 3px;
        font-size: 0.75rem;
      }
      .merged-cell, .account-cell {
        padding: 8px 6px !important;
        font-size: 0.75rem !important;
      }
      .inner-table-a2 td {
        padding: 6px 3px;
        font-size: 0.75rem;
      }
      .bottom-table {
        font-size: 0.75rem;
      }
      .bottom-table td {
        padding: 5px 6px;
      }
    }
    /* Стили для таблицы с товарами/услугами */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 0.95rem;
      color: #000000;
      margin-top: 24px;
      table-layout: auto;
      border: 2px solid #000000;
    }

    .items-table th,
    .items-table td {
      border: 1px solid #000000;
      padding: 10px 8px;
      vertical-align: top;
      background-color: #ffffff;
    }

    .items-table thead tr {
      background-color: #ffffff;
    }

    .items-table th {
      font-weight: bold;
      text-align: center;
      background-color: #f2f2f2;
    }

    .items-table td {
      font-weight: normal;
    }

    /* Выравнивание числовых колонок по правому краю */
    .items-table td:nth-child(1),
    .items-table td:nth-child(3),
    .items-table td:nth-child(5),
    .items-table td:nth-child(6) {
      text-align: right;
    }

    .items-table td:nth-child(2) {
      text-align: left;
    }

    .items-table td:nth-child(4) {
      text-align: center;
    }

    /* Итоговые строки */
    .items-table tfoot td {
      background-color: #ffffff;
      padding: 10px 8px;
    }

    /* Адаптивность */
    @media (max-width: 700px) {
      .items-table {
        font-size: 0.75rem;
      }
      .items-table th,
      .items-table td {
        padding: 6px 4px;
      }
    }
    </style>
  </head>';

    $htmlInvoice .= '
  <body>
    <div class="table-wrapper">
      <!-- ПЕРВАЯ ТАБЛИЦА — банковские реквизиты -->
      <table class="main-table">
        <colgroup>
          <col style="width: 69.23%">
          <col style="width: 7.69%">
          <col style="width: 23.08%">
        </colgroup>

        <!-- строка 1 -->
        <tr>
          <td class="cell-a1">' . $bankDetails['recipient_bank'] . '<br><br>
            Банк получателя
          </td>

          <td class="split-cell-horizontal">
            <table class="inner-table-b1">
              <tr><td>БИК</td></tr>
              <tr><td>Сч. №</td></tr>
            </table>
          </td>

          <td class="cell-c1">
            <table class="inner-table-c1">
              <tr><td>' . $bankDetails['bank_identification_code'] . '</td></tr>
              <tr><td>' . $bankDetails['correspondent_bank_account'] . '</td></tr>
            </table>
          </td>
        </tr>

        <!-- строка 2 -->
        <tr>
          <td class="split-cell-vertical">
            <table class="inner-table-a2">
              <tr>
                <td>ИНН</td>
                <td>КПП</td>
              </tr>
            </table>
          </td>

          <td class="merged-cell" rowspan="2">Сч. №</td>
          <td class="account-cell" rowspan="2">' . $bankDetails['recipients_bank_account'] . '</td>
        </tr>

        <!-- строка 3 -->
        <tr>
          <td class="cell-a3">
            ' . $bankDetails['ip_name'] . '<br><br>
            Получатель
          </td>
        </tr>
      </table>

      <!-- пустая строка -->
      <div class="empty-line"></div>';

    $dateSpacer = str_repeat('&nbsp;', 14);
    $htmlInvoice .= '
      <!-- Счёт на оплату № 21 от 30 июня 2025 г. -->
      <div class="invoice-header"21>
        Счет на оплату № ' . $orderNumber . $dateSpacer . ' от ' . getCurrentRussianDate() . '
      </div>

      <!-- пустая строка -->
      <div class="empty-line"></div>

      <!-- разделительная полоса -->
      <div class="divider"></div>

      <!-- ВТОРАЯ ТАБЛИЦА — 2 столбца, 3 строки, без рамок, пропорции 1:7 -->
      <table class="bottom-table">
        <colgroup>
          <col style="width: 12.5%">
          <col style="width: 87.5%">
        </colgroup>
        <tr>
          <td>Поставщик<br>(Исполнитель):</td>
          <td>ИП Шибицкий Александр, ИНН 743005310292, 456658, Челябинская область, г.о. Копейский, г Копейск, ул Гагарина, д. 12, кв./оф. 16, тел.: +7 9000866698</td>
        </tr>';

    $htmlInvoice .= '
        <tr>
          <td>Покупатель<br>(Заказчик):</td>
          <td>' . $customerName . '</td>
        </tr>
        <tr>
          <td>Основание:</td>
          <td>Основной договор</td>
        </tr>
      </table>
      <!-- ТАБЛИЦА С ГРАНИЦАМИ (6 колонок) -->
      <!-- Первая строка — жирный шрифт, остальные — обычный -->
      <table class="items-table">
        <thead>
          <tr>
            <th>№</th>
            <th>Товары (работы, услуги)</th>
            <th>Кол-во</th>
            <th>Ед.</th>
            <th>Цена</th>
            <th>Сумма</th>
          </tr>
        </thead>';

    $htmlInvoice .= '
    <tbody>
          <tr>
            <td>1</td>
            <td>' . $items[$tariffKey]['name'] . '</td>
            <td>' . $quantity . '</td>
            <td>усл.</td>
            <td>' . number_format($items[$tariffKey]['price'], 2, ',', ' ') . '</td>
            <td>' . number_format($items[$tariffKey]['price'] * $quantity, 2, ',', ' ') . '</td>
          </tr>
          <tr>';

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
            <td>' . $rowNumber . '</td>
            <td>' . htmlspecialchars($addons[$addonKey]['name']) . '</td>
            <td>' . $quantity . '</td>
            <td>усл.</td>
            <td>' . number_format($addonPrice, 2, ',', ' ') . '</td>
            <td>' . number_format($addonSum, 2, ',', ' ') . '</td>
          </tr>';
        }
    }

    $htmlInvoice .= '
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

      <!-- пустая строка -->
      <div class="empty-line"></div>';

    $totalInWords = num2words($total);

    $htmlInvoice .= '
      <p>Всего наименований ' . $rowNumber . ', на сумму ' . number_format($total, 2, ',', ' ') . ' руб<br>
      (' . $totalInWords . ')</p>

      <!-- пустая строка -->
      <div class="empty-line"></div>';

    // Текущая дата + 3 дня
    $htmlInvoice .= '
      <p>Оплатить не позднее ' . date('d.m.Y', strtotime('+3 days')) . ' <br>
      Оплата данного счета означает согласие с условиями поставки товара.<br>
      Уведомление об оплате обязательно, в противном случае не гарантируется наличие товара на складе. <br>
      Товар отпускается по факту прихода денег на р/с Поставщика, самовывозом, при наличии доверенности и  
      паспорта.</p>

      <!-- пустая строка -->
      <div class="empty-line"></div>

      <!-- разделительная полоса -->
      <div class="divider"></div>

      <p>Предприниматель______________________________________________Шибицкий А.</p>

    </div>
  </body>
</html>';
    return $htmlInvoice;
}
