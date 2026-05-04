<?php

require_once 'vendor/autoload.php';

// загрузка .env файла
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * Возвращает настройки в виде ассоциативного массива.
 *
 * Функция пытается прочитать значения из переменных окружения
 * (загруженных из .env файла). Если переменная не найдена или
 * пуста — подставляет значение по умолчанию.
 *
 * Структура возвращаемого массива:
 *   [
 *     'debug' => bool - Вывод ощибок PHP в браузере
*                        По умолчанию: false
 *   ]
 *
 * @return array  Ассоциативный массив
 */
function getSettings(): array
{
    return [
        'debug' => $_ENV['DEBUG'] ?? false,
    ];
}
