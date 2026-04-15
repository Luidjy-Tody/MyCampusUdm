<?php

require_once __DIR__ . '/../mail_config.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendPlatformEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $plainBody = ''): bool
{
    $toEmail = trim($toEmail);
    $toName = trim($toName);
    $subject = trim($subject);
    $htmlBody = trim($htmlBody);
    $plainBody = trim($plainBody);

    if ($toEmail === '' || $subject === '' || $htmlBody === '')
    {
        return false;
    }

    $mail = new PHPMailer(true);

    try
    {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(MAIL_USERNAME, 'MyCampusClubsUDM');
        $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $plainBody !== '' ? $plainBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        return $mail->send();
    }
    catch (Exception $e)
    {
        return false;
    }
}