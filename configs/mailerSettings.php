<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once 'vendor/autoload.php';

// загрузка .env файла
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Возвращает полный набор настроек SMTP-соединения.
 *
 * Функция собирает все параметры, необходимые для подключения
 * к почтовому серверу и отправки писем. Обязательные параметры
 * (логин и пароль) должны быть указаны в .env файле.
 * Опциональные параметры имеют значения по умолчанию для Gmail.
 *
 * Структура возвращаемого массива:
 *   [
 *     'username'    => string  Логин для SMTP-аутентификации.
 *                              Обычно совпадает с email-адресом отправителя.
 *                              Для Gmail: your-email@gmail.com
 *                              Для Яндекс: your-email@yandex.ru
 *                              Берётся из MAILER_USERNAME
 *     'password'    => string  Пароль для SMTP-аутентификации.
 *                              НЕ обычный пароль от почты, а специальный
 *                              "пароль приложения" (app password).
 *                              Где получить:
 *                              - Gmail: myaccount.google.com/apppasswords
 *                              - Yandex: passport.yandex.ru/profile
 *                              - Mail.ru: account.mail.ru/user/settings
 *                              Берётся из MAILER_PASSWORD
 *     'host'        => string  Адрес SMTP-сервера.
 *                              По умолчанию: 'smtp.gmail.com'
 *                              Яндекс: 'smtp.yandex.ru'
 *                              Mail.ru: 'smtp.mail.ru'
 *                              Берётся из MAILER_HOST
 *     'port'        => int     Порт SMTP-сервера.
 *                              По умолчанию: 587 (TLS)
 *                              465 — для SSL
 *                              25  — устаревший, без шифрования
 *                              Берётся из MAILER_PORT
 *     'charset'     => string  Кодировка писем.
 *                              По умолчанию: 'UTF-8'
 *                              Не меняйте, если не уверены.
 *                              UTF-8 нужен для корректного отображения
 *                              русского текста в письмах.
 *                              Берётся из MAILER_CHARSET
 *     'encryption'  => string  Тип шифрования (константа PHPMailer).
 *                              'tls' -> PHPMailer::ENCRYPTION_STARTTLS
 *                              'ssl' -> PHPMailer::ENCRYPTION_SMTPS
 *                              По умолчанию: STARTTLS (для порта 587)
 *                              Берётся из MAILER_ENCRYPTION
 *   ]
 *
 * @return Ассоциативный массив со всеми настройками SMTP.
 */
function getMailSettings(): array
{
    return [
        'username'    => $_ENV['MAILER_USERNAME'] ?? getenv('MAILER_USERNAME'),
        'password'    => $_ENV['MAILER_PASSWORD'] ?? getenv('MAILER_PASSWORD'),
        'host'        => $_ENV['MAILER_HOST'] ?? 'smtp.gmail.com',
        'port'        => $_ENV['MAILER_PORT'] ?? 587,
        'charset'     => $_ENV['MAILER_CHARSET'] ?? 'UTF-8',
        'encryption'  => $_ENV['MAILER_ENCRYPTION'] === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS,
    ];
}
