<?php

declare(strict_types=1);

// раскомментировать для вывода ошибок на экран
require_once 'utils/debug.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';
require_once 'configs/mailerSettings.php';

/**
 * Отправляет HTML-письмо получателю через настроенный SMTP-сервер
 * с прикреплённым PDF-файлом счёта.
 *
 * @param  string $toEmail      - Email получателя
 * @param  string $toName       - Имя получателя
 * @param  string $subject      - Тема письма
 * @param  string $htmlBody     - HTML-содержимое письма
 * @param  string $pdfContent   - Бинарное содержимое PDF (опционально)
 * @param  string $pdfFilename  - Имя PDF-файла (опционально)
 * @return true|false
 */
function sendInvoiceEmail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $pdfContent = '',
    string $pdfFilename = 'invoice.pdf'
): bool {
    $mail         = new PHPMailer(true);
    $mailSettings = getMailSettings();

    try {
        $mail->SMTPDebug    = SMTP::DEBUG_OFF;
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

        // Прикрепляем PDF, если он передан
        if (!empty($pdfContent)) {
            $mail->addStringAttachment(
                $pdfContent,
                $pdfFilename,
                'base64',
                'application/pdf'
            );
        }

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
