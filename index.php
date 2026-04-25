<?php

/**
 * =====================================================================
 * index.php - Главная страница "Калькулятор заказа"
 * =====================================================================
 *
 * Как настраивать:
 *   См. массивы $items и $addons ниже. Изменяйте названия, цены,
 *   пути к картинкам. Ключ массива (например, 'standart') должен
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

session_start();

// ------------------------------------------------------------------
// КОНФИГУРАЦИЯ ТОВАРОВ (ОСНОВНЫЕ ТАРИФЫ)
//
// Здесь администратор задаёт основные услуги/тарифы.
// Каждый элемент - это radio-кнопка в форме.
//
// СТРУКТУРА ОДНОГО ТАРИФА:
//   Ключ массива (напр. 'standart') - уникальный идентификатор,
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
    'standart' => ['name' => 'Тариф Стандарт', 'price'  => 1000, 'img' => 'img/standart.jpg'],
    'pro'      => ['name' => 'Тариф Про', 'price'       => 2500, 'img' => 'img/pro.jpg'],
    'vip'      => ['name' => 'Тариф VIP', 'price'       => 5000, 'img' => 'img/vip.jpg'],
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
    'support' => ['name' => 'Поддержка 24/7', 'price'         => 500, 'img' => 'img/support.png'],
    'backup'  => ['name' => 'Резервное копирование', 'price'  => 300, 'img' => 'img/backup.png'],
    'seo'     => ['name' => 'SEO-аудит', 'price'              => 700, 'img' => 'img/seo.png'],
];

$_SESSION['items_session'] = $items;
$_SESSION['addons_session'] = $addons;

/**
 * Проверяет, существует ли файл изображения на сервере.
 * Если файла нет — возвращает путь к заглушке (placeholder),
 * чтобы не показывать битую иконку в браузере.
 *
 * @param  $path        - путь к проверяемому изображению
 * @param  $placeholder - путь к заглушке (по умолчанию img/placeholder.jpg)
 * @return
 */
function getImagePath(string $path, string $placeholder = 'img/placeholder.jpg'): string
{
    if (!empty($path) && file_exists($path)) {
        return $path;
    }
    return $placeholder;
}
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
            <h2>1. Выберите тариф</h2>
            <div class="radio-group">
                <?php foreach ($items as $key => $item) : ?>
                <label class="card">
                    <input type="radio" name="tariff" value="<?= $key ?>" data-price="<?= $item['price'] ?>" data-name="<?= htmlspecialchars($item['name']) ?>" required>
                    <img src="<?= getImagePath($item['img']) ?>" alt="<?= $item['name'] ?>">
                    <span class="title"><?= $item['name'] ?></span>
                    <span class="price"><?= number_format($item['price'], 0, ',', ' ') ?> ₽</span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Блок дополнительных услуг (Чекбоксы) -->
            <h2>2. Дополнительные услуги</h2>
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

            <!-- Количество / Срок -->
            <h2>3. Количество месяцев</h2>
            <div class="quantity-block">
                <input type="number" id="quantity" name="quantity" min="1" max="12" value="1" step="1" required>
                <label for="quantity">мес.</label>
            </div>

            <!-- Итог -->
            <div class="total-block">
                Итого: <span id="totalPrice">0</span> ₽
            </div>

            <!-- Данные покупателя -->
            <h2>4. Ваши контакты для счета</h2>
            <input type="text" name="customer_name" placeholder="Ваше имя / Организация" required>
            <input type="email" name="customer_email" placeholder="Email для отправки счета" required>
            <input type="tel" name="customer_phone" placeholder="Телефон">

            <button type="submit">Отправить заказ и получить счет</button>
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
    const tariffRadio = document.querySelector('input[name="tariff"]:checked');
    if (tariffRadio) {
        selectedItems.push({
            name: tariffRadio.dataset.name,
            price: parseFloat(tariffRadio.dataset.price) || 0
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
    const tariffRadio = document.querySelector('input[name="tariff"]:checked');
    if (tariffRadio) {
        total += parseFloat(tariffRadio.dataset.price) || 0;
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
