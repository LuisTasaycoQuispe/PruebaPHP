<?php
// api/config/mailers.php

// Definimos las constantes globales de credenciales
define('USER_1', 'luistasayco3030@gmail.com');
define('PASS_1', 'xkii szmn wopp rqdr');

define('USER_2', 'noreply.terraandina@gmail.com');
define('PASS_2', 'vvkh jkzc vozm jbji');

define('EMAIL_USER', 'noreply.fiestatoursperu@gmail.com');

define('SECRET_KEY_TURNSTILE_PERU_LUXURY', '0x4AAAAAADPRDAWehbe_VFhuGTxSXn4SnK4');
define('SECRET_KEY_TURNSTILE', '0x4AAAAAACw3a24bV1FooWeaaH8KsZdr_cE');

define('GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbwHQD1Djs9fR-dkY1ORNEH2TJp-On_mMXupgut0VtvGHJ0mTUVPAEdLBjx8D8IfvUKSPA/exec');

function getTransporter($user = USER_1, $pass = PASS_1) {
    require_once __DIR__ . '/../vendor/autoload.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // TLS
    $mail->Port       = 587;
    
    // Equivalente a tls: { rejectUnauthorized: false } en Node
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    $mail->CharSet = 'UTF-8';
    return $mail;
}