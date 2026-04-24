
<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function getBankDetailsSettings()
{
    $defaultValue = '!!! Заполните в настройках';
    return [
        'recipient_bank'  => $_ENV['BANK_DETAILS_RECIPIENT_BANK']
      ?: getenv('BANK_DETAILS_RECIPIENT_BANK')
    ?: $defaultValue,
    ];
}
