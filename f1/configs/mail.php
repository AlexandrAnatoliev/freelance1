<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

// загрузка .env файла
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$username = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME');
$password = $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD');
$host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
$port = $_ENV['MAIL_PORT'] ?? 587;
$encryption = $_ENV['MAIL_ENCRYPTION'] === 'ssl'
  ? PHPMailer::ENCRYPTION_SMTPS
  : PHPMailer::ENCRYPTION_STARTTLS;
$charset = $_ENV['MAIL_CHARSET'] ?? 'UTF-8';

function getMailSettings() {
  return [
    'username' => $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME'),
    'password' => $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD'),
    'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
    'port' => $_ENV['MAIL_PORT'] ?? 587,
    'encryption' => $_ENV['MAIL_ENCRYPTION'] === 'ssl'
    ? PHPMailer::ENCRYPTION_SMTPS
    : PHPMailer::ENCRYPTION_STARTTLS,
    'charset' => $_ENV['MAIL_CHARSET'] ?? 'UTF-8',
  ];
}

