<?php

require_once 'vendor/autoload.php';

// загрузка .env файла
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function getAddons() {
  return [
    'support' => [
      'name' => $_ENV['ADDONS_SUPPORT_NAME'] ?? 'Поддержка 24/7',
      'price' => $_ENV['ADDONS_SUPPORT_PRICE'] ?? 500,
    ],
    'backup' => [
      'name' => $_ENV['ADDONS_BACKUP_NAME'] ?? 'Резервное копирование',
      'price' => $_ENV['ADDONS_BACKUP_PRICE'] ?? 300,
    ],
    'seo' => [
      'name' => $_ENV['ADDONS_SEO_NAME'] ?? 'SEO-аудит',
      'price' => $_ENV['ADDONS_SEO_PRICE'] ?? 700,
    ],
  ];
}
