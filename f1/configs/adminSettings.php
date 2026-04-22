<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

// загрузка .env файла
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function getAdminSettings() {
  return [
    'email' => $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?? 'admin@gmail.com',
  ];
}
