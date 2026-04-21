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
    <title>Заказ оформлен - Счет на оплату</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Основные стили страницы */
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

        .success-message p {
            margin: 5px 0;
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

        .warning {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            border-radius: 5px;
        }

        /* Стили для кнопок */
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

        .btn-new {
            background-color: #ff9800;
            color: white;
        }

        .btn-new:hover {
            background-color: #e68900;
        }

        /* Контейнер для счета */
        .invoice-preview {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            overflow-x: auto;
            border: 1px solid #e0e0e0;
        }

        .invoice-preview h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .print-note {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-style: italic;
        }

        /* Стили для печати */
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
            .email-status,
            .invoice-preview h2 {
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
            <p>Счет отправлен на <strong><?= htmlspecialchars($customerEmail) ?></strong></p>
            <?php if (!empty($customerPhone)): ?>
                <p>Номер телефона: <strong><?= htmlspecialchars($customerPhone) ?></strong></p>
            <?php endif; ?>
        </div>

        <?php if (!$resultCustomer): ?>
            <div class="email-status email-error">
                <strong>⚠ Внимание!</strong> Письмо не было отправлено. Проверьте настройки почты или свяжитесь с нами.
            </div>
        <?php else: ?>
            <div class="email-status email-success">
                <strong>✓ Письмо успешно отправлено!</strong> Проверьте папку «Спам», если не видите письма во входящих.
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <button class="btn btn-print" onclick="window.print()">
                🖨️ Распечатать / Сохранить как PDF
            </button>
            <a href="index.php" class="btn btn-back">
                ← Вернуться к калькулятору
            </a>
            <button class="btn btn-new" onclick="window.location.href='index.php'">
                ✨ Создать новый заказ
            </button>
        </div>

        <div class="invoice-preview">
            <h2>Счет на оплату №<?= date('Ymd-His') ?></h2>
            <?= $htmlContent ?>
        </div>

        <div class="print-note">
            <p>💡 Для сохранения в PDF: нажмите кнопку "Распечатать" и выберите "Сохранить как PDF" в списке принтеров.</p>
            <p>📧 Счет также отправлен на вашу электронную почту.</p>
        </div>
    </div>

    <script>
        // Дополнительный скрипт для улучшения взаимодействия
        document.addEventListener('DOMContentLoaded', function() {
            // Можно добавить автоматическое открытие диалога печати через 1 секунду
            // setTimeout(function() {
            //     window.print();
            // }, 1000);

            // Или просто оставить для ручного нажатия
        });

        // Обработка печати
        window.onbeforeprint = function() {
            // Перед печатью можно что-то подготовить
            console.log('Подготовка к печати...');
        };

        window.onafterprint = function() {
            // После печати
            console.log('Печать завершена');
        };
    </script>
</body>
</html>
