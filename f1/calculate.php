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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .result-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        h1 {
            color: #1e8449;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            text-align: center;
            margin-bottom: 20px;
        }

        .email-status {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }

        .email-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .email-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .action-buttons {
            margin: 30px 0 20px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-print {
            background-color: #4CAF50;
            color: white;
        }

        .btn-print:hover {
            background-color: #45a049;
        }

        .btn-back {
            background-color: #2196F3;
            color: white;
        }

        .btn-back:hover {
            background-color: #0b7dda;
        }

        .invoice-preview {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            overflow-x: auto;
            border: 1px solid #e0e0e0;
        }

        .print-note {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-style: italic;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .result-container {
                box-shadow: none;
                padding: 0;
                max-width: none;
            }

            .action-buttons,
            .print-note,
            h1,
            .success-message,
            .email-status {
                display: none !important;
            }

            .invoice-preview {
                border: none;
                padding: 0;
                overflow: visible;
            }
        }
    </style>
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
