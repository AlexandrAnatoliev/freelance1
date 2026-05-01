<?php

declare(strict_types=1);

/**
 * Проверяет, не превышен ли лимит запросов с заданного IP-адреса.
 *
 * Использует простой JSON-файл в системной временной папке.
 * Запись выполняется атомарно через временный файл и rename.
 *
 * @param string      $ip         IP-адрес клиента (например, $_SERVER['REMOTE_ADDR'])
 * @param int         $interval   Минимальный интервал между запросами в секундах (по умолчанию 30)
 * @param string|null $storageFile Путь к файлу хранилища (если null — sys_get_temp_dir())
 * @return bool true — превышен лимит (запрос нужно заблокировать), false — можно продолжать
 */
function isIpRateLimited(string $ip, int $interval = 30, ?string $storageFile = null): bool
{
    if ($storageFile === null) {
        $storageFile = sys_get_temp_dir() . '/invoice_rate_limit.json';
    }

    // Читаем текущее состояние
    $data = [];
    if (file_exists($storageFile)) {
        $json = file_get_contents($storageFile);
        if ($json !== false) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
    }

    $now = time();

    // Удаляем устаревшие записи (старше заданного интервала)
    foreach ($data as $storedIp => $timestamp) {
        if (!is_int($timestamp) || ($now - $timestamp) > $interval) {
            unset($data[$storedIp]);
        }
    }

    // Проверяем текущий IP
    if (isset($data[$ip]) && is_int($data[$ip]) && ($now - $data[$ip]) < $interval) {
        return true; // лимит превышен
    }

    // Регистрируем новую отметку времени
    $data[$ip] = $now;

    // Атомарная запись: пишем во временный файл, затем переименовываем
    $tmpFile = $storageFile . '.tmp.' . uniqid('', true);
    $written = file_put_contents($tmpFile, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);

    if ($written === false) {
        // При ошибке записи не блокируем, чтобы не мешать работе сервиса
        return false;
    }

    if (!rename($tmpFile, $storageFile)) {
        @unlink($tmpFile);
        return false;
    }

    return false;
}
