<?php
// Настройки товаров
$items = [
  'standart' => ['name' => 'Тариф Стандарт', 'price' => 1000, 'img' => 'img/standart.jpg'],
  'pro'      => ['name' => 'Тариф Про', 'price' => 2500, 'img' => 'img/pro.jpg'],
  'vip'      => ['name' => 'Тариф VIP', 'price' => 5000, 'img' => 'img/vip.jpg'],
];
$addons = [
  'support' => ['name' => 'Поддержка 24/7', 'price' => 500, 'img' => 'img/support.png'],
  'backup'  => ['name' => 'Резервное копирование', 'price' => 300, 'img' => 'img/backup.png'],
  'seo'     => ['name' => 'SEO-аудит', 'price' => 700, 'img' => 'img/seo.png'],
];

function getImagePath($path, $placeholder = 'img/placeholder.jpg') {
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="calculator">
        <h1>Калькулятор услуг</h1>
        <form id="orderForm" action="calculate.php" method="post">

            <!-- Блок выбора основного тарифа (Радио) -->
            <h2>1. Выберите тариф</h2>
            <div class="radio-group">
                <?php foreach ($items as $key => $item): ?>
                <label class="card">
                    <input type="radio" name="tariff" value="<?= $key ?>" data-price="<?= $item['price'] ?>" required>
                    <img src="<?= getImagePath($item['img']) ?>" alt="<?= $item['name'] ?>">
                    <span class="title"><?= $item['name'] ?></span>
                    <span class="price"><?= number_format($item['price'], 0, ',', ' ') ?> ₽</span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Блок дополнительных услуг (Чекбоксы) -->
            <h2>2. Дополнительные услуги</h2>
            <div class="checkbox-group">
                <?php foreach ($addons as $key => $addon): ?>
                <label class="card small">
                    <input type="checkbox" name="addons[]" value="<?= $key ?>" data-price="<?= $addon['price'] ?>">
                    <img src="<?= getImagePath($addon['img']) ?>" alt="<?= $addon['name'] ?>">
                    <span class="title"><?= $addon['name'] ?></span>
                    <span class="price">+<?= number_format($addon['price'], 0, ',', ' ') ?> ₽</span>
                </label>
                <?php endforeach; ?>
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
const form = document.getElementById('orderForm');
const totalSpan = document.getElementById('totalPrice');
const qtyInput = document.getElementById('quantity');

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

form.addEventListener('change', calculateTotal);
qtyInput.addEventListener('input', calculateTotal);
window.addEventListener('DOMContentLoaded', calculateTotal);
</script>
</body>
</html>
