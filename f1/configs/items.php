<?php

require_once 'vendor/autoload.php';

// загрузка .env файла
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function getItems() {
  return [
    'standart' => [
      $name => $_ENV['ITEMS_STANDART_NAME'] ?? 'Тариф Стандарт',
      $price => $_ENV['ITEMS_STANDART_PRICE'] ?? 1000,
    ],
    'pro' => [
      $name => $_ENV['ITEMS_PRO_NAME'] ?? 'Тариф Про',
      $price => $_ENV['ITEMS_PRO_PRICE'] ?? 2500,
    ],
    'vip' => [
      $name => $_ENV['ITEMS_VIP_NAME'] ?? 'Тариф VIP',
      $price => $_ENV['ITEMS_VIP_PRICE'] ?? 5000,
    ],
  ];
}
