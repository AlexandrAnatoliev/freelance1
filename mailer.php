<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';
require_once 'configs/mail.php';

function sendInvoiceEmail($toEmail, $toName, $subject, $htmlBody)
{
    $mail         = new PHPMailer(true);
    $mailSettings = getMailSettings();

    try {
        // для вывода диагностики в браузер включить DEBUG_SERVER
        $mail->SMTPDebug    = SMTP::DEBUG_OFF;
        $mail->Debugoutput  = function ($str, $level) {
            $colors = [
                SMTP::DEBUG_CLIENT     => '#3498db',
                SMTP::DEBUG_SERVER     => '#2ecc71',
                SMTP::DEBUG_CONNECTION => '#9b59b6',
                SMTP::DEBUG_LOWLEVEL   => '#e74c3c',
            ];

            $color = $colors[$level] ?? '#f5f5f5';

            echo "<pre style='background:{$color}; padding:3px; margin:2px; font-family:monospace;'>"
              . htmlspecialchars($str) . "</pre>";
        };

        $mail->isSMTP();
        $mail->Host         = $mailSettings['host'];
        $mail->SMTPAuth     = true;
        $mail->Username     = $mailSettings['username'];
        $mail->Password     = $mailSettings['password'];
        $mail->SMTPSecure   = $mailSettings['encryption'];
        $mail->Port         = $mailSettings['port'];
        $mail->CharSet      = $mailSettings['charset'];
        $mail->SMTPOptions  = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom($mailSettings['username'], 'Калькулятор заказа');
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($mailSettings['username'], 'Поддержка');
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
