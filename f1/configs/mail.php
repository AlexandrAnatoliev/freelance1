<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

// загрузка .env файла
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function getMailSettings() {
  return [
    'username' => $_ENV['MAILER_USERNAME'] ?? getenv('MAILER_USERNAME'),
    'password' => $_ENV['MAILER_PASSWORD'] ?? getenv('MAILER_PASSWORD'),
    'host' => $_ENV['MAILER_HOST'] ?? 'smtp.gmail.com',
    'port' => $_ENV['MAILER_PORT'] ?? 587,
    'encryption' => $_ENV['MAILER_ENCRYPTION'] === 'ssl'
    ? PHPMailer::ENCRYPTION_SMTPS
    : PHPMailer::ENCRYPTION_STARTTLS,
    'charset' => $_ENV['MAILER_CHARSET'] ?? 'UTF-8',
  ];
}

