<?php

/**
 * =====================================================================
 * index.php - Главная страница "Калькулятор заказа"
 * =====================================================================
 *
 * Как настраивать:
 *   См. массивы $items и $addons ниже. Изменяйте названия, цены,
 *   пути к картинкам. Ключ массива (например, 'ocean_pen') должен
 *   быть уникальным и использоваться как value в radio/checkbox.
 *
 * Зависимости:
 *   - utils/debug.php (настройки вывода ошибок PHP)
 *   - styles/index.css (стили страницы)
 *   - img/* (изображения товаров)
 */

declare(strict_types=1);

// раскомментировать для вывода ошибок на экран
require_once 'utils/debug.php';
require_once 'utils/imagePath.php';
require_once 'utils/selector.php';
require_once 'captcha.php';

// Генерируем капчу при загрузке страницы
$captcha = generateCaptcha();

// ------------------------------------------------------------------
// КОНФИГУРАЦИЯ ТОВАРОВ (ОСНОВНЫЕ ТАРИФЫ)
//
// Здесь администратор задаёт основные услуги/тарифы.
// Каждый элемент - это radio-кнопка в форме.
//
// СТРУКТУРА ОДНОГО ТАРИФА:
//   Ключ массива (напр. 'ocean_pen') - уникальный идентификатор,
//       используется в value radio-кнопки и для поиска цены
//   'name'  - название тарифа (отображается пользователю)
//   'price' - цена в рублях за 1 единицу (число, не строка)
//   'img'   - путь к файлу картинки относительно корня проекта
//
// КАК ИЗМЕНИТЬ / ДОБАВИТЬ ТАРИФ:
//   - Чтобы изменить цену, поменяйте число в 'price'
//   - Чтобы изменить картинку, замените файл в папке img/
//       или укажите новый путь в 'img'
//   - Чтобы добавить новый тариф, скопируйте любую строку,
//       замените ключ, name, price и img
//   - Чтобы удалить тариф, удалите соответствующую строку
// ------------------------------------------------------------------
$items = [
    'ocean_pen' => [
        'name'  => 'Ручка Океан',
        'price' => 16,
        'img'   => 'img/ocean_pen.jpg',
    ],
    'senator_pen' => [
        'name'  => 'Ручка Сенатор',
        'price' => 19,
        'img'   => 'img/senator_pen.jpg',
    ],
    'lychee_pen' => [
        'name'  => 'Ручка Личи',
        'price' => 15,
        'img'   => 'img/lychee_pen.jpg',
    ],
];
// ------------------------------------------------------------------
// КОНФИГУРАЦИЯ ДОПОЛНИТЕЛЬНЫХ УСЛУГ (АДДОНЫ)
//
// Аддоны отображаются как checkbox-ы. Пользователь может выбрать
// несколько (или ни одного). Структура точно такая же, как у тарифов.
//
// КАК ИЗМЕНИТЬ / ДОБАВИТЬ АДДОН: аналогично тарифам выше.
// ------------------------------------------------------------------
$addons = [
    'print_on_clip'   => [
        'name'  => 'Нанесение на клип',
        'price' => 46,
        'img'   => 'img/print_on_clip.png',
    ],
    'print_on_colored_case'    => [
        'name'  => 'Нанесение на цветной корпус',
        'price' => 43,
        'img'   => 'img/print_on_colored_case.png',
    ],
    'print_on_white_case'       => [
        'name'  => 'Нанесение на белый корпус',
        'price' => 33,
        'img'   => 'img/print_on_white_case.png',
    ],
];

function getPrice(
    array $addons,
    array $addon_prices,
    string $addonName,
    int $circulation
): int {
    $addon = $addons[$addonName];
    $price = $addon['price1']['value'];

    foreach ($addon as $prices) {
        $price = $prices['value'];
        if ($prices['circulation'] > $circulation) {
            break;
        }
    }
    return $price;
}
// ------------------------------------------------------------------
// КОНФИГУРАЦИЯ ЦЕН НА ДОПОЛНИТЕЛЬНЫЕ УСЛУГИ
// ------------------------------------------------------------------
$addon_prices = [
    'print_on_clip' => [
        'price1' => [
            'value'       => 46,
            'circulation' => 100,
        ],
        'price2' => [
            'value'       => 36,
            'circulation' => 200,
        ],
        'price3' => [
            'value'       => 34,
            'circulation' => 300,
        ],
        'price4' => [
            'value'       => 31,
            'circulation' => 500,
        ],
        'price5' => [
            'value'       => 28,
            'circulation' => 1000,
        ],
    ],
    'print_on_colored_case' => [
        'price1' => [
            'value'       => 43,
            'circulation' => 100,
        ],
        'price2' => [
            'value'       => 34,
            'circulation' => 200,
        ],
        'price3' => [
            'value'       => 31,
            'circulation' => 300,
        ],
        'price4' => [
            'value'       => 29,
            'circulation' => 500,
        ],
        'price5' => [
            'value'       => 26,
            'circulation' => 1000,
        ],
    ],
    'print_on_white_case' => [
        'price1' => [
            'value'       => 33,
            'circulation' => 100,
        ],
        'price2' => [
            'value'       => 26,
            'circulation' => 200,
        ],
        'price3' => [
            'value'       => 24,
            'circulation' => 300,
        ],
        'price4' => [
            'value'       => 22,
            'circulation' => 500,
        ],
        'price5' => [
            'value'       => 20,
            'circulation' => 1000,
        ],
    ],
];

$_SESSION['items_session'] = $items;
$_SESSION['addons_session'] = $addons;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Калькулятор заказа</title>
    <link rel="stylesheet" href="styles/index.css">
</head>
<body>
    <div class="calculator">
        <h1>Калькулятор услуг</h1>
        <form id="orderForm" action="checkout.php" method="post">

            <!-- Блок выбора основного тарифа (Радио) -->
            <h2>1. Выберите сувенир</h2>
            <div class="radio-group">
                <?php foreach ($items as $key => $item) : ?>
                <label class="card">
                    <input type="radio" name="itemName" value="<?= $key ?>" data-price="<?= $item['price'] ?>" data-name="<?= htmlspecialchars($item['name']) ?>" required>
                    <img src="<?= getImagePath($item['img']) ?>" alt="<?= $item['name'] ?>">
                    <span class="title"><?= $item['name'] ?></span>
                    <span class="price"><?= number_format($item['price'], 0, ',', ' ') ?> ₽</span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Количество / Срок -->
            <h2>2. Нужное количество</h2>
            <div class="quantity-block">
              <select id="quantity" name="quantity" required>
                <?= getSelector(min: 50, max: 1000, step: 50); ?>
              </select>
            </div>

            <!-- Блок дополнительных услуг (Чекбоксы) -->
            <h2>3. Выберите нанесение</h2>
            <div class="checkbox-group">
                <?php foreach ($addons as $key => $addon) : ?>
                <label class="card small">
                    <input type="checkbox" name="addons[]" value="<?= $key ?>" data-price="<?= $addon['price'] ?>" data-name="<?= htmlspecialchars($addon['name']) ?>">
                    <img src="<?= getImagePath($addon['img']) ?>" alt="<?= $addon['name'] ?>">
                    <span class="title"><?= $addon['name'] ?></span>
                    <span class="price">+<?= number_format($addon['price'], 0, ',', ' ') ?> ₽</span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Блок "Выбрано:" -->
            <div class="selected-items">
                <h3>📋 Выбрано:</h3>
                <ul class="selected-list" id="selectedList">
                    <li class="empty-selection">Ничего не выбрано</li>
                </ul>
            </div>

            <!-- Итог -->
            <div class="total-block">
                Итого: <span id="totalPrice">0</span> ₽
            </div>

            <!-- Данные покупателя -->
            <h2>4. Ваши данные для получения счёта на оплату на почту</h2>
            <input type="text" name="customer_name" placeholder="Наименование организации для счёта" required>
            <input type="email" name="customer_email" placeholder="Email для отправки счета" required>
            <input type="tel" name="customer_phone" placeholder="Телефон контакта" required>

            <!-- CAPTCHA -->
            <h2>5. Проверка: вы не робот?</h2>
            <div class="captcha-block">
                <div class="captcha-question">
                    🤔 Сколько будет: <?= htmlspecialchars($captcha['question']) ?>?
                </div>
                <input
                    type="number"
                    name="captcha"
                    class="captcha-input"
                    placeholder="Введите ответ цифрой"
                    required
                    id="captchaInput"
                >
                <div class="captcha-hint">
                    💡 Введите результат математического выражения цифрой
                </div>
            </div>

            <button type="submit">Заказать и получить счёт на оплату на email</button>
        </form>
    </div>

<script>
// Калькуляция на лету
const form      = document.getElementById('orderForm');
const totalSpan = document.getElementById('totalPrice');
const qtyInput  = document.getElementById('quantity');
const selectedList = document.getElementById('selectedList');

function updateSelectedItems() {
    const selectedItems = [];
    
    // Проверяем выбранный тариф
    const itemNameRadio = document.querySelector('input[name="itemName"]:checked');
    if (itemNameRadio) {
        selectedItems.push({
            name: itemNameRadio.dataset.name,
            price: parseFloat(itemNameRadio.dataset.price) || 0
        });
    }
    
    // Проверяем выбранные аддоны
    const checkedAddons = document.querySelectorAll('input[name="addons[]"]:checked');
    checkedAddons.forEach(cb => {
        selectedItems.push({
            name: cb.dataset.name,
            price: parseFloat(cb.dataset.price) || 0
        });
    });
    
    // Обновляем список
    if (selectedItems.length === 0) {
        selectedList.innerHTML = '<li class="empty-selection">Ничего не выбрано</li>';
    } else {
        selectedList.innerHTML = selectedItems.map(item =>
            `<li>
                <span class="item-name">${item.name}</span>
                <span class="item-price">${new Intl.NumberFormat('ru-RU').format(item.price)} руб.</span>
            </li>`
        ).join('');
    }
}

function calculateTotal() {
    let total = 0;

    // Тариф
    const itemNameRadio = document.querySelector('input[name="itemName"]:checked');
    if (itemNameRadio) {
        total += parseFloat(itemNameRadio.dataset.price) || 0;
    }

    // Аддоны
    const checkedAddons = document.querySelectorAll('input[name="addons[]"]:checked');
    checkedAddons.forEach(cb => {
        total += parseFloat(cb.dataset.price) || 0;
    });

    // Умножаем на количество
    const qty = parseInt(qtyInput.value) || 1;
    total = total * qty;

    totalSpan.textContent = new Intl.NumberFormat('ru-RU').format(total);
}

// Валидация капчи перед отправкой
form.addEventListener('submit', function(e) {
    const captchaInput = document.getElementById('captchaInput');
    const captchaValue = parseInt(captchaInput.value);
    
    // Простая клиентская проверка (основная проверка на сервере в checkout.php)
    if (isNaN(captchaValue)) {
        e.preventDefault();
        captchaInput.classList.add('error');
        alert('Пожалуйста, введите ответ на проверочный вопрос цифрой');
        captchaInput.focus();
        return false;
    }
    
    captchaInput.classList.remove('error');
});

form.addEventListener('change', function() {
    updateSelectedItems();
    calculateTotal();
});

qtyInput.addEventListener('input', calculateTotal);
window.addEventListener('DOMContentLoaded', function() {
    updateSelectedItems();
    calculateTotal();
});
</script>
</body>
</html>
