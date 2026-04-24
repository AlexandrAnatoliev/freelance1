
<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function getBankDetailsSettings()
{
    $defaultValue = 'Заполнить настройки!';

    return [
        'recipient_bank'              => $_ENV['BANK_DETAILS_RECIPIENT_BANK']
          ?: getenv('BANK_DETAILS_RECIPIENT_BANK')
          ?: $defaultValue,
        'bank_identification_code'    => $_ENV['BANK_DETAILS_BANK_IDENTIFICATION_CODE']
          ?: getenv('BANK_DETAILS_BANK_IDENTIFICATION_CODE')
          ?: $defaultValue,
        'correspondent_bank_account'  => $_ENV['BANK_DETAILS_CORRESPONDENT_BANK_ACCOUNT']
          ?: getenv('BANK_DETAILS_CORRESPONDENT_BANK_ACCOUNT')
          ?: $defaultValue,
        'recipients_bank_account'     => $_ENV['BANK_DETAILS_RECIPIENTS_BANK_ACCOUNT']
          ?: getenv('BANK_DETAILS_RECIPIENTS_BANK_ACCOUNT')
          ?: $defaultValue,
        'ip_name'                     => $_ENV['BANK_DETAILS_IP_NAME']
          ?: getenv('BANK_DETAILS_IP_NAME')
          ?: $defaultValue,
        'ip_full_name'                => $_ENV['BANK_DETAILS_IP_FULL_NAME']
          ?: getenv('BANK_DETAILS_IP_FULL_NAME')
          ?: $defaultValue,
        'payment_basis'               => $_ENV['BANK_DETAILS_PAYMENT_BASIS']
          ?: getenv('BANK_DETAILS_PAYMENT_BASIS')
          ?: $defaultValue,

        'entrepreneurs_surname'       => $_ENV['BANK_DETAILS_ENTREPRENEURS_SURNAME']
          ?: getenv('BANK_DETAILS_ENTREPRENEURS_SURNAME')
          ?: $defaultValue,
    ];
}
