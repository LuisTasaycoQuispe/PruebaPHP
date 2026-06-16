<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('USER_1', $_ENV['SMTP_USER']);
define('PASS_1', $_ENV['SMTP_PASS']);

function getTransporter($user = USER_1, $pass = PASS_1) {
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST']; 
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;   
    $mail->Password   = $pass;         
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
    $mail->Port       = $_ENV['SMTP_PORT'];                          
    
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->XMailer = 'Fiesta Tours Peru';

    return $mail;
}