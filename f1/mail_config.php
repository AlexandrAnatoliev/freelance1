<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

// загрузка .env файла
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function sendInvoiceEmail($toEmail, $toName, $subject, $htmlBody) {
  $mail = new PHPMailer(true);

  $username = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME');
  $password = $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD');
  $host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
  $port = $_ENV['MAIL_PORT'] ?? 587;
  $encryption = $_ENV['MAIL_ENCRYPTION'] === 'ssl'
    ? PHPMailer::ENCRYPTION_SMTPS
    : PHPMailer::ENCRYPTION_STARTTLS;
  $charset = $_ENV['MAIL_CHARSET'] ?? 'UTF-8';

  try {
    // для вывода диагностики в браузер включить DEBUG_SERVER
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->Debugoutput = function($str, $level) {
      $colors = [
        SMTP::DEBUG_CLIENT     => '#3498db', // Синий
        SMTP::DEBUG_SERVER     => '#2ecc71', // Зеленый
        SMTP::DEBUG_CONNECTION => '#9b59b6', // Фиолетовый
        SMTP::DEBUG_LOWLEVEL   => '#e74c3c', // Красный
      ];

      $color = $colors[$level] ?? '#f5f5f5';

      echo "<pre style='background:#f5f5f5; padding:3px; margin:2px; font-family:monospace;'>"
        . htmlspecialchars($str) . "</pre>";
    };

    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $username;
    $mail->Password   = $password;
    $mail->SMTPSecure = $encryption;
    $mail->Port       = $port;
    $mail->CharSet    = $charset;

    $mail->SMTPOptions = [
      'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
      ]
    ];

    $mail->setFrom($username, 'Калькулятор заказа');
    $mail->addAddress($toEmail, $toName);
    $mail->addReplyTo($username, 'Поддержка');

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = strip_tags($htmlBody);

    $mail->send();
    return true;

  } catch (Exception $e) {
    echo "<div style='color:red; padding:10px; border:1px solid red;'>";
    echo "<strong>Ошибка отправки:</strong><br>";
    echo $mail->ErrorInfo;
    echo "</div>";
    return false;
  }
}
