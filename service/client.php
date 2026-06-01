<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/mailers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["ok" => false, "mensaje" => "No se recibieron datos válidos."]);
    exit;
}

$nombreContacto = $input['nombreContacto'] ?? '';
$emailContacto  = $input['emailContacto'] ?? '';
$correoDestino  = $input['correoDestino'] ?? ''; // ejecutivo
$referencia     = $input['referencia'] ?? '';

if (empty(trim($nombreContacto)) || empty(trim($emailContacto))) {
    echo json_encode(["ok" => false, "mensaje" => "El nombre y el email son requeridos."]);
    exit;
}

date_default_timezone_set('America/Lima');
$datosAEnviar = [
    "fecha"            => date('d/m/Y, h:i:s A'),
    "nombreContacto"   => $nombreContacto,
    "emailContacto"    => $emailContacto,
    "ejecutivo"        => $correoDestino,
    "referencia"       => $referencia,
    "doble_dos_camas"  => $input['doble_dos_camas'] ?? '-',
    "dobleMatrimonial" => $input['dobleMatrimonial'] ?? '-',
    "suiteFamiliar"    => $input['suiteFamiliar'] ?? '-',
    "individual"       => $input['individual'] ?? '-',
    "adultos"          => $input['adultos'] ?? '1',
    "ninos"            => $input['ninos'] ?? '0',
    "aceptaCheck"      => !empty($input['aceptaCheck']) ? 'Si' : 'No',
    "copiaPasaporte"   => !empty($input['copiaPasaporte']) ? 'Si' : 'No',
    "pasajeros"        => $input['pasajeros'] ?? []
];

try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);        
    $options->set('isHtml5ParserEnabled', true);  
    $options->set('defaultFont', 'Arial');
    
    $dompdf = new Dompdf($options);

    $htmlPasajerosRows = '';
    foreach ($datosAEnviar['pasajeros'] as $index => $p) {
        $bg = ($index % 2 === 0) ? '#ffffff' : '#fafafa';
        $htmlPasajerosRows .= "
        <tr style='background-color: {$bg}; font-size: 11px;'>
            <td style='padding: 6px; border-bottom: 1px solid #e8e8e8;'>".($p['titulo'] ?? '—')."</td>
            <td style='padding: 6px; border-bottom: 1px solid #e8e8e8;'>".($p['nombre'] ?? '—')."</td>
            <td style='padding: 6px; border-bottom: 1px solid #e8e8e8;'>".($p['apellido'] ?? '—')."</td>
            <td style='padding: 6px; border-bottom: 1px solid #e8e8e8;'>".($p['pasaporte'] ?? '—')."</td>
            <td style='padding: 6px; border-bottom: 1px solid #e8e8e8;'>".($p['nacionalidad'] ?? '—')."</td>
            <td style='padding: 6px; border-bottom: 1px solid #e8e8e8;'>".($p['fechaNac'] ?? '—')."</td>
        </tr>";
    }

    $anioActual = date('Y');
    $htmlPDF = "
    <html>
    <head>
        <style>
            body { font-family: 'Helvetica', sans-serif; margin: 20px; color: #2c2c2c; }
                
            .header-table { width: 100%;padding-bottom: 10px; margin-bottom: 20px; }
            .header-logo { text-align: right; }
        
            .title { color: #2a4e33; font-size: 30px; font-weight: bold; }
            .section-title--feature{ background: #ffffff; color: #1a1a1a; padding: 5px 10px; border-left:5px solid #93a89c; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-top: 20px; }
            .section-title { background: #93a89c; color: #1a1a1a; padding: 5px 10px; font-size: 12px; font-weight: bold; text-transform: uppercase; margin-top: 20px; }
            .sombra{background-color: #faf7f7;}
            table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            th { background: #9dc5ae; color: #1a1a1a; text-align: left; padding: 6px; font-size: 11px; }
            td { padding: 7px; border-bottom: 1px solid #e8e8e8; font-size: 12px; }
            .label { color: #888888; width: 35%; }
            .value { font-weight: bold; }
            .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 9px; color: #555555; }
        </style>
    </head>
    <body>
             <table class='header-table'>
            <tr>
                <td style='border: none; padding: 0; vertical-align: middle;'>
                    <span class='title'>Registro de Cliente</span>
                </td>
                <td class='header-logo' style='border: none; padding: 0; vertical-align: middle;'>
                    <img style='width: 300px; height: auto;' src='https://res.cloudinary.com/dlgeap8h0/image/upload/f_auto,q_auto/v1776726875/Group_22_vtvrk3.png' alt='logo'/>
                </td>
            </tr>
        </table>
        
        <div class='section-title--feature'>Datos de Contacto</div>
        <table>
            <tr><td class='label'>Fecha</td><td class='value'>{$datosAEnviar['fecha']}</td></tr>
            <tr class='sombra'><td class='label'>Nombre</td><td class='value'>{$datosAEnviar['nombreContacto']}</td></tr>
            <tr><td class='label'>Email</td><td class='value'>{$datosAEnviar['emailContacto']}</td></tr>
            <tr class='sombra'><td class='label'>Ejecutivo</td><td class='value'>{$datosAEnviar['ejecutivo']}</td></tr>
            <tr><td class='label'>Referencia</td><td class='value'>{$datosAEnviar['referencia']}</td></tr>
        </table>

        <div class='section-title--feature'>Habitaciones</div>
        <p style='font-size: 12px; margin: 10px 0;'>Adultos: <b>{$datosAEnviar['adultos']}</b> | Niños: <b>{$datosAEnviar['ninos']}</b></p>
        <table>
            <tr><td class='label'>Doble (dos camas)</td><td class='value'>{$datosAEnviar['doble_dos_camas']}</td></tr>
            <tr class='sombra'><td class='label'>Doble matrimonial</td><td class='value'>{$datosAEnviar['dobleMatrimonial']}</td></tr>
            <tr><td class='label'>Suite familiar</td><td class='value'>{$datosAEnviar['suiteFamiliar']}</td></tr>
            <tr class='sombra'><td class='label'>Individual</td><td class='value'>{$datosAEnviar['individual']}</td></tr>
        </table>";

    if (!empty($datosAEnviar['pasajeros'])) {
        $htmlPDF .= "
        <div class='section-title--feature'>Pasajeros</div>
        <table>
            <thead>
                <tr><th>Tít.</th><th>Nombre</th><th>Apellido</th><th>Pasaporte</th><th>Nacionalidad</th><th>F. Nacimiento</th></tr>
            </thead>
            <tbody>{$htmlPasajerosRows}</tbody>
        </table>";
    }

    $htmlPDF .= "
        <div class='section-title--feature'>Validación</div>
        <table>
            <tr><td class='label'>Acepta traer varias copias de sus pasaportes</td><td class='value'>{$datosAEnviar['aceptaCheck']}</td></tr>
            <tr  class='sombra'><td class='label'>Pasaportes vigentes durante el viaje</td><td class='value'>{$datosAEnviar['copiaPasaporte']}</td></tr>
        </table>
        
        <div class='footer'>© {$anioActual} Fiesta Tours Peru — Documento generado automáticamente</div>
    </body>
    </html>";

    $dompdf->loadHtml($htmlPDF);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfBuffer = $dompdf->output(); 

    $ch = curl_init(GOOGLE_SCRIPT_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datosAEnviar));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    $sheetResponse = curl_exec($ch);
    $sheetResult = json_decode($sheetResponse, true);
    curl_close($ch);

    if (!function_exists('emailFila')) {
        function emailFila($label, $valor) {
            if (!$valor || $valor === '-' || $valor === '0') return '';
            return "
            <tr>
                <td style='padding:9px 0;border-bottom:1px solid #f0f0f0;'>
                    <span style='color:#999;font-size:10px;letter-spacing:2px;text-transform:uppercase;display:block;'>{$label}</span>
                    <span style='color:#1a1a1a;font-size:14px;'>{$valor}</span>
                </td>
            </tr>";
        }
    }

    if (!function_exists('emailBase')) {
        function emailBase($titulo, $subtitulo, $cuerpo) {
            $anio = date('Y');
            return "
            <html>
            <body style='margin:0;padding:0;background:#f4f4f4;font-family:Georgia,serif;'>
                <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:40px 0;'>
                    <tr><td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 14px rgba(0,0,0,0.09);'>
                            <tr><td style='background:#54705d;padding:30px 40px;'>
                                <p style='margin:0;color:#f3ca5b;font-size:12px;letter-spacing:3px;text-transform:uppercase;'>Fiesta Tours Peru</p>
                                <h1 style='margin:8px 0 0;color:#fff;font-size:22px;font-weight:normal;letter-spacing:1px;'>{$titulo}</h1>
                                <p style='margin:6px 0 0;color:#fdfdfd;font-size:12px;'>{$subtitulo}</p>
                            </td></tr>
                            <tr><td style='padding:36px 40px;'>{$cuerpo}</td></tr>
                            <tr><td style='background:#f9f9f9;padding:18px 40px;border-top:1px solid #eee;text-align:center;'>
                                <p style='margin:0;color:#bbb;font-size:10px;'>© {$anio} Fiesta Tours Peru — Notificación automática</p>
                            </td></tr>
                        </table>
                    </td></tr>
                </table>
            </body>
            </html>";
        }
    }

    $cuerpoEjecutivo = "
    <p style='margin:0 0 20px;color:#555;font-size:15px;line-height:1.7;'>Un nuevo cliente ha completado su registro. El detalle completo está adjunto en PDF.</p>
    <table width='100%' cellpadding='0' cellspacing='0' style='background:#f9f9f9;border-left:4px solid #645939;border-radius:4px;margin-bottom:24px;'>
        <tr><td style='padding:22px 26px;'>
            <table width='100%' cellpadding='0' cellspacing='0'>
                ".emailFila('Nombre', $datosAEnviar['nombreContacto'])."
                ".emailFila('Email', "<a href='mailto:{$datosAEnviar['emailContacto']}' style='color:#3d6951;text-decoration:none;'>{$datosAEnviar['emailContacto']}</a>")."
                ".emailFila('Referencia', $datosAEnviar['referencia'])."
                ".emailFila('Fecha', $datosAEnviar['fecha'])."
            </table>
        </td></tr>
    </table>
    <p style='margin:0;color:#888;font-size:12px;'>Adultos: <b>{$datosAEnviar['adultos']}</b> | Niños: <b>{$datosAEnviar['ninos']}</b> | Pasajeros: <b>".count($datosAEnviar['pasajeros'])."</b></p>";
    
    $htmlEjecutivo = emailBase('Nuevo Registro de Cliente', "Referencia: " . ($datosAEnviar['referencia'] ?: '—'), $cuerpoEjecutivo);

    // Email Cliente HTML
    $filasHab = '';
    foreach ([['Doble (dos camas)', $datosAEnviar['doble_dos_camas']], ['Doble matrimonial', $datosAEnviar['dobleMatrimonial']], ['Suite familiar', $datosAEnviar['suiteFamiliar']], ['Individual', $datosAEnviar['individual']]] as $h) {
        $filasHab .= emailFila($h[0], $h[1]);
    }
    if(empty($filasHab)) $filasHab = "<tr><td style='padding:9px 0;color:#888;font-size:13px;'>Sin habitaciones seleccionadas</td></tr>";

    $filasPax = '';
    foreach ($datosAEnviar['pasajeros'] as $i => $p) {
        $idx = $i + 1;
        $fullname = implode(' ', array_filter([$p['titulo'] ?? '', $p['nombre'] ?? '', $p['apellido'] ?? '']));
        $paxDetails = !empty($p['pasaporte']) ? "<br/><span style='color:#888;font-size:12px;'>Pasaporte: {$p['pasaporte']}</span>" : "";
        $paxDetails .= !empty($p['nacionalidad']) ? "<span style='color:#888;font-size:12px;'> · {$p['nacionalidad']}</span>" : "";
        
        $filasPax .= "
        <tr><td style='padding:10px 0;border-bottom:1px solid #f0f0f0;'>
            <span style='color:#D9B244;font-size:10px;letter-spacing:2px;text-transform:uppercase;'>Pasajero {$idx}</span><br/>
            <span style='color:#1a1a1a;font-size:14px;'><b>{$fullname}</b></span>{$paxDetails}
        </td></tr>";
    }
    if(empty($filasPax)) $filasPax = "<tr><td style='padding:9px 0;color:#888;font-size:13px;'>Sin pasajeros registrados</td></tr>";

    $cuerpoCliente = "
    <p style='margin:0 0 6px;color:#1a1a1a;font-size:16px;'>Estimado/a <b>{$datosAEnviar['nombreContacto']}</b>,</p>
    <p style='margin:0 0 28px;color:#555;font-size:14px;'>Hemos recibido tu registro correctamente. A continuación encontrarás un resumen. El documento completo está adjunto en PDF.</p>
    
    <p style='margin:0 0 8px;color:#4f632f;font-size:11px;font-weight:900;letter-spacing:3px;text-transform:uppercase;'>Datos generales</p>
    <table width='100%' style='background:#fafafa;border-left:4px solid #795c41;margin-bottom:28px;'>
        <tr><td style='padding:20px 24px;'><table width='100%'>
            ".emailFila('Fecha de registro', $datosAEnviar['fecha'])."
            ".emailFila('Referencia', $datosAEnviar['referencia'])."
            ".emailFila('Adultos', $datosAEnviar['adultos'])."
            ".emailFila('Niños', $datosAEnviar['ninos'])."
        </table></td></tr>
    </table>
    
    <p style='margin:0 0 8px;color:#4f632f;font-size:11px;font-weight:900;letter-spacing:3px;text-transform:uppercase;'>Habitaciones solicitadas</p>
    <table width='100%' style='background:#fafafa;border-left:4px solid #795c41;margin-bottom:28px;'>
        <tr><td style='padding:20px 24px;'><table width='100%'>{$filasHab}</table></td></tr>
    </table>
    
    <p style='margin:0 0 8px;color:#4f632f;font-size:11px;font-weight:900;letter-spacing:3px;text-transform:uppercase;'>Pasajeros</p>
    <table width='100%' style='background:#fafafa;border-left:4px solid #795c41;margin-bottom:28px;'>
        <tr><td style='padding:20px 24px;'><table width='100%'>{$filasPax}</table></td></tr>
    </table>";

    $htmlCliente = emailBase('Confirmación de Registro', "Folio: " . ($datosAEnviar['referencia'] ?: $datosAEnviar['nombreContacto']), $cuerpoCliente);

    // ---- DESPACHO DE ENVIOS POR SMTP (PHPMailer) ----
    $cleanedName = preg_replace('/\s+/', '_', $nombreContacto);
    $filename = "registro_{$cleanedName}.pdf";

    $mailEjecutivo = getTransporter(); 
    $mailEjecutivo->setFrom(USER_1, 'Fiesta Tours Peru');
    $mailEjecutivo->addAddress($correoDestino);
    $mailEjecutivo->Subject = "Nuevo Registro — {$nombreContacto}";
    $mailEjecutivo->isHTML(true);
    $mailEjecutivo->Body = $htmlEjecutivo;
    $mailEjecutivo->addStringAttachment($pdfBuffer, $filename, 'base64', 'application/pdf'); 
    $mailEjecutivo->send();

    $mailCliente = getTransporter();
    $mailCliente->setFrom(USER_1, 'Fiesta Tours Peru');
    $mailCliente->addAddress($emailContacto);
    $mailCliente->Subject = "Confirmación de tu registro — " . ($referencia ?: $nombreContacto);
    $mailCliente->isHTML(true);
    $mailCliente->Body = $htmlCliente;
    $mailCliente->addStringAttachment($pdfBuffer, $filename, 'base64', 'application/pdf');
    $mailCliente->send();

    echo json_encode([
        "ok" => true,
        "receptor" => $correoDestino,
        "mensaje" => "Datos enviados correctamente.",
        "resultado" => $sheetResult
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ]);
}