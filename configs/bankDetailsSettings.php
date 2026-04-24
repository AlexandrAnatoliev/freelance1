
<?php
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function getBankDetailsSettings()
{
    return [
        'recipient_bank'  => $_ENV['BANK_DETAILS_RECIPIENT_BANK']
      ?: getenv('BANK_DETAILS_RECIPIENT_BANK')
    ?: '!!! Заполните в настройках',
    ];
}
