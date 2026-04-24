
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
    ];
}
