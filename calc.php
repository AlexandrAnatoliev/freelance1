<?php

/**
 * =====================================================================
 * calc.php - Главная страница "Калькулятор заказа"
 * =====================================================================
 *
 * Как настраивать:
 *   См. массивы $items и $addons ниже. Изменяйте названия, цены,
 *   пути к картинкам. Ключ массива (например, 'ocean_pen') должен
 *   быть уникальным и использоваться как value в radio/checkbox.
 *
 * Зависимости:
 *   - utils/debug.php (настройки вывода ошибок PHP)
 *   - styles/calc.css (стили страницы)
 *   - img/* (изображения товаров)
 */

declare(strict_types=1);

// раскомментировать для вывода ошибок на экран
require_once 'utils/debug.php';
require_once 'utils/imagePath.php';
require_once 'utils/session.php';
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
$_SESSION['addon_prices_session'] = $addon_prices;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Калькулятор заказа</title>
    <link rel="stylesheet" href="styles/calc.css">
</head>
<body>
    <div class="calculator">
      <?php if (isset($_SESSION['error_message'])) : ?>
    <div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; background: #fff5f5;">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
            <?php unset($_SESSION['error_message']); ?>
      <?php endif; ?>
      
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
                <?= getSelector(min: 0, max: 1000, step: 50); ?>
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

            <!-- Предупреждение: нанесение без сувенира -->
            <div id="souvenirWarning" class="warning-box">
                ⚠️ Сначала выберите сувенир
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

    <!-- Передаём цены аддонов, зависящие от тиража -->
<script>
const addonPrices = <?= json_encode($addon_prices, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script>
// Получить цену аддона для заданного количества по логике из invoice.php
function getAddonPrice(addonKey, quantity) {
    if (!addonPrices[addonKey]) return 0;
    const tiers = addonPrices[addonKey];
    let price = tiers.price1.value; // значение по умолчанию
    for (const tierKey in tiers) {
        const tier = tiers[tierKey];
        price = tier.value;
        if (tier.circulation > quantity) break;
    }
    return price;
}

// Обновить отображаемую цену в карточках всех аддонов
function updateAddonPricesDisplay() {
    const qty = parseInt(document.getElementById('quantity').value) || 50;
    const addonCards = document.querySelectorAll('.checkbox-group .card');
    addonCards.forEach(card => {
        const checkbox = card.querySelector('input[type="checkbox"]');
        if (!checkbox) return;
        const addonKey = checkbox.value;
        const price = getAddonPrice(addonKey, qty);
        const priceSpan = card.querySelector('.price');
        if (priceSpan) {
            priceSpan.textContent = '+' + new Intl.NumberFormat('ru-RU').format(price) + ' ₽';
        }
    });
}

// Обновление списка выбранных позиций с динамическими ценами
function updateSelectedItems() {
    const selectedItems = [];
    const qty = parseInt(document.getElementById('quantity').value) || 50;

    // Выбранный тариф
    const itemRadio = document.querySelector('input[name="itemName"]:checked');
    if (itemRadio) {
        selectedItems.push({
            name: itemRadio.dataset.name,
            price: parseFloat(itemRadio.dataset.price) || 0
        });
    }

    // Выбранные аддоны
    const checkedAddons = document.querySelectorAll('input[name="addons[]"]:checked');
    checkedAddons.forEach(cb => {
        const addonKey = cb.value;
        const price = getAddonPrice(addonKey, qty);
        selectedItems.push({
            name: cb.dataset.name,
            price: price
        });
    });

    const selectedList = document.getElementById('selectedList');
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

// Расчёт итоговой суммы с учётом динамических цен аддонов
function calculateTotal() {
    let total = 0;
    const qty = parseInt(document.getElementById('quantity').value) || 50;

    // Тариф
    const itemRadio = document.querySelector('input[name="itemName"]:checked');
    if (itemRadio) {
        total += (parseFloat(itemRadio.dataset.price) || 0) * qty;
    }

    // Аддоны
    const checkedAddons = document.querySelectorAll('input[name="addons[]"]:checked');
    checkedAddons.forEach(cb => {
        const addonKey = cb.value;
        const unitPrice = getAddonPrice(addonKey, qty);
        total += unitPrice * qty;
    });

    document.getElementById('totalPrice').textContent = new Intl.NumberFormat('ru-RU').format(total);
}

// Обработчики событий
const form      = document.getElementById('orderForm');
const qtySelect = document.getElementById('quantity');

form.addEventListener('change', function(e) {
    // При изменении любого radio/checkbox обновляем список, цены и итог
    updateSelectedItems();
    calculateTotal();
    // Если изменилось количество, обновим отображаемые цены аддонов
    if (e.target && e.target.id === 'quantity') {
        updateAddonPricesDisplay();
    }
});

qtySelect.addEventListener('change', function() {
    updateAddonPricesDisplay();
    updateSelectedItems();
    calculateTotal();
});

// Инициализация при загрузке
window.addEventListener('DOMContentLoaded', function() {
    updateAddonPricesDisplay();
    updateSelectedItems();
    calculateTotal();
});

form.addEventListener('submit', function(e) {
    // --- Валидация Email ---
    const emailInput = form.querySelector('input[name="customer_email"]');
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(emailInput.value.trim())) {
        e.preventDefault();
        emailInput.classList.add('error');
        alert('Введите корректный Email адрес.');
        emailInput.focus();
        return false;
    }
    emailInput.classList.remove('error');

    // --- Валидация Телефона ---
    const phoneInput = form.querySelector('input[name="customer_phone"]');
    const phoneDigits = phoneInput.value.replace(/\D/g, '');
    if (phoneDigits.length < 10) {
        e.preventDefault();
        phoneInput.classList.add('error');
        alert('Номер телефона должен содержать не менее 10 цифр.');
        phoneInput.focus();
        return false;
    }
    phoneInput.classList.remove('error');

    // --- Проверка капчи
    const captchaInput = document.getElementById('captchaInput');
    const captchaValue = parseInt(captchaInput.value);

    if (isNaN(captchaValue)) {
        e.preventDefault();
        captchaInput.classList.add('error');
        alert('Пожалуйста, введите ответ на проверочный вопрос цифрой');
        captchaInput.focus();
        return false;
    }

    captchaInput.classList.remove('error');
});
</script>
</body>
</html>
