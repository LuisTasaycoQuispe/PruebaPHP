<?php
define('USER_1', 'noreply.fiestatoursperu@gmail.com');
define('PASS_1', 'ztcn lsxw sbwy mktw');

define('GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbwHQD1Djs9fR-dkY1ORNEH2TJp-On_mMXupgut0VtvGHJ0mTUVPAEdLBjx8D8IfvUKSPA/exec');

function getTransporter($user = USER_1, $pass = PASS_1) {
    require_once __DIR__ . '/../vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // TLS en puerto 587
    $mail->Port       = 587;
    
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    return $mail;
}