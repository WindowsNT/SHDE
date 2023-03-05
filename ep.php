
<?php
require_once "functions.php";

require_once "vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;    

try
{
    $mail = new PHPMailer(true);
    $mail->CharSet = "UTF-8";
    $mail->setFrom($topmail,"Test EP");
    $mail->isMail();

    $mail->Subject = "Test EP";
    $mail->isHTML(true);

    $message = print_r($req,true);

    $mail->DKIM_domain = MAIL_DOMAIN;
    $mail->DKIM_private = MAIL_RSA_PRIV;
    $mail->DKIM_selector = MAIL_SELECTOR;
    $mail->DKIM_passphrase = MAIL_RSA_PASSPHRASE;
    $mail->DKIM_identity = MAIL_IDENTITY;

    $mail->addAddress("windowssnt@gmail.com","Michael Chourdakis");

    $mail->Body = $message;
    $mail->send();
}

catch(phpmailerException $e)
{
    printf($e->errorMessage().'<br>');
    $err = 1;
}
