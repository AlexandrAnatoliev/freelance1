<?php

require_once 'vendor/autoload.php';

// загрузка .env файла
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function getAdminSettings(): array
{
    return [
        'email' => $_ENV['ADMIN_EMAIL'] ?? 'admin@gmail.com',
        'name'  => $_ENV['ADMIN_NAME'] ?? 'Администратор',
    ];
}
