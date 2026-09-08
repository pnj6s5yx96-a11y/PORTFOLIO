<?php
declare(strict_types=1);

namespace Portfolio\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    public static function send(string $to, string $subject, string $html, string $text): bool
    {
        if (!class_exists(PHPMailer::class)) {
            error_log('[portfolio] PHPMailer is missing. Run composer install before production.');
            return false;
        }
        $mailConfig = config('mail');
        if ($mailConfig['host'] === '' || $mailConfig['from_email'] === '') {
            error_log('[portfolio] SMTP is not configured. Email was not sent.');
            return false;
        }
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->Port = $mailConfig['port'];
            $mail->SMTPAuth = $mailConfig['username'] !== '';
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->CharSet = 'UTF-8';
            if ($mailConfig['encryption'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($mailConfig['encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;
            return $mail->send();
        } catch (Exception $exception) {
            error_log('[portfolio] Mail failed: ' . $exception->getMessage());
            return false;
        }
    }
}
