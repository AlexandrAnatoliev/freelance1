<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// загрузка .env файла
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function sendInvoiceEmail($toEmail, $toName, $subject, $htmlBody) {
  $mail = new PHPMailer(true);

  $username = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME');
  $password = $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD');

  try {
    $mail->SMTPDebug = SMTP::DEBUG_OFF; 
    // для диагностики включить DEBUG_SERVER
    // вывод диагностики в браузер
    $mail->Debugoutput = function($str, $level) {
      echo "<pre style='background:#f5f5f5; padding:3px; margin:2px; font-family:monospace;'>"
        . htmlspecialchars($str) . "</pre>";
    };

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $username;
    $mail->Password   = $password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

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
?>
