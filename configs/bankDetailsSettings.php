
<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function envOr(string $key, string $default = 'Заполнить настройки!'): string
{
    return $_ENV[$key] ?: getenv($key) ?: $default;
}

function getBankDetailsSettings(): array
{
    return [
        'recipient_bank'             => envOr('BANK_DETAILS_RECIPIENT_BANK'),
        'bank_identification_code'   => envOr('BANK_DETAILS_BANK_IDENTIFICATION_CODE'),
        'correspondent_bank_account' => envOr('BANK_DETAILS_CORRESPONDENT_BANK_ACCOUNT'),
        'recipients_bank_account'    => envOr('BANK_DETAILS_RECIPIENTS_BANK_ACCOUNT'),
        'ip_name'                    => envOr('BANK_DETAILS_IP_NAME'),
        'ip_full_name'               => envOr('BANK_DETAILS_IP_FULL_NAME'),
        'payment_basis'              => envOr('BANK_DETAILS_PAYMENT_BASIS'),
        'entrepreneurs_surname'      => envOr('BANK_DETAILS_ENTREPRENEURS_SURNAME'),
    ];
}
