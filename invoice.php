<?php

declare(strict_types=1);

// раскомментировать для вывода ошибок на экран
require_once 'utils/debug.php';

session_start();
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
  <style>
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

    .invoice-header {
      font-weight: bold;
      font-size: 1.2rem;
      text-align: left;
      margin-bottom: 6px;
    }

    .empty-line {
      height: 8px;
    }

    .divider {
      width: 100%;
      height: 2px;
      background-color: #000;
      margin-bottom: 12px;
    }

    /* ГЛАВНАЯ ТАБЛИЦА */
    .main-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }

    .main-table td {
      border: 2px solid #000;
      padding: 6px 8px;
      background-color: #fff;
      vertical-align: middle;
    }

    .cell-bank-name {
      width: 67%;
      line-height: 1.3;
      text-align: left;
    }

    .cell-bik-label {
      width: 9%;
      text-align: center;
    }

    .cell-bik-value {
      width: 24%;
      text-align: left;
    }

    .cell-inn-kpp {
      padding: 5px 4px;
      text-align: center;
    }

    .inn-cell {
      display: inline-block;
      width: 54%;
      padding-right: 8px;
      border-right: 2px solid #000;
      text-align: center;
    }

    .kpp-cell {
      display: inline-block;
      width: 40%;
      text-align: center;
    }

    .cell-account-label {
      vertical-align: top;
      text-align: center;
      padding: 6px 4px;
    }

    .cell-account-value {
      vertical-align: top;
      text-align: left;
    }

    .cell-recipient {
      line-height: 1.3;
      text-align: left;
    }

    /* ТАБЛИЦА С ТОВАРАМИ */
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
      padding: 6px 5px;
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
    }

    /* НИЖНЯЯ ТАБЛИЦА */
    .bottom-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
      margin-top: 6px;
    }

    .bottom-table td {
      border: none;
      padding: 4px 6px;
      vertical-align: top;
      line-height: 1.35;
    }

    .label-cell {
      width: 14%;
      text-align: left;
    }

    .value-cell {
      width: 86%;
      font-weight: bold;
      text-align: left;
    }

    p {
      font-size: 0.85rem;
      line-height: 1.3;
    }
  </style>
</head>';

    $htmlInvoice .= '
<body>
<div class="table-wrapper">

  <!-- ПЕРВАЯ ТАБЛИЦА — банковские реквизиты -->
  <table class="main-table">
    <tr>
    <td class="cell-bank-name">' . $bankDetails['recipient_bank'] . '<br><br>
      Банк получателя
    </td>
      <td class="cell-bik-label">БИК</td>
      <td class="cell-bik-value">' . $bankDetails['bank_identification_code'] . '</td>
    </tr>
    <tr>
      <td class="cell-bank-name" style="border-bottom: none;">&nbsp;</td>
      <td class="cell-bik-label">Сч. №</td>
      <td class="cell-bik-value">' . $bankDetails['correspondent_bank_account'] . '</td>
    </tr>
    <tr>
      <td class="cell-inn-kpp">
        <span class="inn-cell">ИНН</span><span class="kpp-cell">КПП</span>
      </td>
      <td class="cell-account-label" rowspan="2">Сч. №</td>
      <td class="cell-account-value" rowspan="2">' . $bankDetails['recipients_bank_account'] . '</td>
    </tr>
    <tr>
      <td class="cell-recipient">' . $bankDetails['ip_name'] . '<br><br>Получатель</td>
    </tr>
  </table>

  <div class="empty-line"></div>

  <div class="invoice-header">
    Счет на оплату № Б-20260425-153045&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; от 25 апреля 2026 г.
  </div>

  <div class="empty-line"></div>
  <div class="divider"></div>

  <table class="bottom-table">
    <tr>
      <td class="label-cell">Поставщик<br>(Исполнитель):</td>
      <td class="value-cell">ИП Иванов Иван Иванович, ИНН 770207013900, ОГРНИП 320774600000000</td>
    </tr>
    <tr>
      <td class="label-cell">Покупатель<br>(Заказчик):</td>
      <td class="value-cell">ООО "Ромашка"</td>
    </tr>
    <tr>
      <td class="label-cell">Основание:</td>
      <td class="value-cell">Договор № 15 от 01.01.2026</td>
    </tr>
  </table>

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
        <td class="col-left">Тариф "Стандарт" — доступ к платформе</td>
        <td class="col-right">6</td>
        <td class="col-center">усл.</td>
        <td class="col-right">5 000,00</td>
        <td class="col-right">30 000,00</td>
      </tr>
      <tr>
        <td class="col-right">2</td>
        <td class="col-left">Дополнительная техническая поддержка 24/7</td>
        <td class="col-right">6</td>
        <td class="col-center">усл.</td>
        <td class="col-right">2 000,00</td>
        <td class="col-right">12 000,00</td>
      </tr>
      <tr>
        <td class="col-right">3</td>
        <td class="col-left">Расширенное облачное хранилище 100 ГБ</td>
        <td class="col-right">6</td>
        <td class="col-center">усл.</td>
        <td class="col-right">1 500,00</td>
        <td class="col-right">9 000,00</td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" style="text-align:right; font-weight:bold;">Итого:</td>
        <td style="font-weight:bold;">51 000,00</td>
      </tr>
      <tr>
        <td colspan="5" style="text-align:right; font-weight:bold;">В том числе НДС:</td>
        <td style="font-weight:bold;">—</td>
      </tr>
      <tr>
        <td colspan="5" style="text-align:right; font-weight:bold;">Всего к оплате:</td>
        <td style="font-weight:bold;">51 000,00</td>
      </tr>
    </tfoot>
  </table>

  <div class="empty-line"></div>

  <p>Всего наименований 3, на сумму 51 000,00 руб<br>
  (пятьдесят одна тысяча рублей 00 копеек)</p>

  <div class="empty-line"></div>

  <p>Оплатить не позднее 28.04.2026<br>
  Оплата данного счета означает согласие с условиями поставки товара.<br>
  Уведомление об оплате обязательно, в противном случае не гарантируется наличие товара на складе.<br>
  Товар отпускается по факту прихода денег на р/с Поставщика, самовывозом, при наличии доверенности и паспорта.</p>

  <div class="empty-line"></div>
  <div class="divider"></div>

  <p>Предприниматель______________________________________________Иванов И.И.</p>
</div>
</body>
</html>';
    return $htmlInvoice;
}
