<?php

require_once "functions.php";
if (!$u)
    diez();

require_once "output.php";
PrintHeader();

if ($CanHostOthers == 0)
    diez();



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;    
require_once "vendor/autoload.php";
if (array_key_exists("uid",$_POST))
{
    printf("Ο administrator έχει λάβει το αίτημά σας και θα σας ενημερώσει.");
    $x = print_r($_POST,true);
    $mail = new PHPMailer(true);
    $mail->CharSet = "UTF-8";
    $mail->setFrom($topmail,$title);
    $mail->isMail();
    $mail->Subject = "Αίτηση Για νέο Φορέα";
    $mail->isHTML(true);
    $mail->addAddress("chourdakismichael@gmail.com", "Michael Chourdakis");
    $message = $x;
    $mail->DKIM_domain = MAIL_DOMAIN;
    $mail->DKIM_private = MAIL_RSA_PRIV;
    $mail->DKIM_selector = MAIL_SELECTOR;
    $mail->DKIM_passphrase = MAIL_RSA_PASSPHRASE;
    $mail->DKIM_identity = MAIL_IDENTITY;
    $mail->Body = $message;
    $mail->send();
    echo '<br><br><button href="index.php" class="autobutton button is-danger">Πίσω</button>';
    die;
}

?>

<form method="POST" action="applyoid.php">
    <input type="hidden" name="uid" value="<?= $u->uid ?>" >

    Όνομα φορέα που επιθυμείτε να δημιουργήσετε:
    <input type="text" class="input" name="name" required/>
<br><br>
Το e-mail σας:
    <input type="email" class="input" name="email" required/>
    <br><br><br>


<button class="button is-primary">Υποβολή</button>
</form>
<button href="index.php" class="autobutton button is-danger">Άκυρο</button>
