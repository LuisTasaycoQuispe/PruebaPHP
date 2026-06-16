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

$nombreContacto = trim($input['nombre'] ?? '');
$emailContacto  = trim($input['email'] ?? '');
$correoDestino  = 'dw@fiestatoursperu.com, dw1@fiestatoursperu.com';
$referencia     = $input['referencia'] ?? '';

if (empty($nombreContacto) || empty($emailContacto)) {
    echo json_encode(["ok" => false, "mensaje" => "El nombre y el email son requeridos."]);
    exit;
}

if (!filter_var($emailContacto, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["ok" => false, "mensaje" => "El email proporcionado no es válido."]);
    exit;
}

date_default_timezone_set('America/Lima');

function esc(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$datosAEnviar = [
    "nombre"                  => esc($nombreContacto),
    "email"                   => esc($emailContacto),
    "referencia"              => esc($referencia),
    "fecha"                   => esc($input['fecha'] ?? ''),
    "hotelTransfer"           => $input['hotelTransfer'] ?? [],
    "restaurantes"            => $input['restaurantes'] ?? [],
    "tours"                   => $input['tours'] ?? [],
    "hotel"                   => $input['hotel'] ?? [],
    "comentarioHotelTransfer" => esc($input['comentarioHotelTransfer'] ?? '-'),
    "comentarioRestaurante"   => esc($input['comentarioRestaurante'] ?? '-'),
    "comentarioHotel"         => esc($input['comentarioHotel'] ?? '-'),
    "comentariosToursGuia"    => esc($input['comentariosToursGuia'] ?? '-'),
    "comentario"              => esc($input['comentario'] ?? '-'),
    "calificacion"            => esc($input['calificacion'] ?? '-'),
];

function buildSections(array $datos, bool $esPDF = true): string
{
    $html = '';

    $sectionClass  = $esPDF ? "section-title"    : "title-subtitle_feature";
    $tableClass    = $esPDF ? "data-table"        : "data-table";
    $commentClass  = $esPDF ? "comment-box"       : "container-comentarios";
    $commentInner  = $esPDF ? ""                  : "<p>%s</p>";

    $renderComment = function(string $texto, string $label) use ($commentClass, $commentInner, $esPDF): string {
        if ($texto === '-') return '';
        $contenido = $esPDF
            ? "{$label}: {$texto}"
            : sprintf("<p>{$label}: {$texto}</p>");
        return "<div class='{$commentClass}'>{$contenido}</div>";
    };

    if (!empty($datos['hotelTransfer'])) {
        $html .= "<div style='color: #2a4e33; margin-top:20px; font-weight:800' class='{$sectionClass} section-tittle_feature'>Hotel Transfer</div>";
        $html .= "<table class='{$tableClass}'>";
        foreach ($datos['hotelTransfer'] as $ht) {
            $nombre = esc((string)($ht['hotelTransfer_name'] ?? ''));
            $calif  = esc((string)($ht['hotelTransfer_calificacion'] ?? ''));
            $html .= "<tr>
                <td class='val-name'>{$nombre}</td>
                <td class='val-rating'>{$calif}</td>
            </tr>";
        }
        $html .= "</table>";
        $html .= $renderComment($datos['comentarioHotelTransfer'], 'Comentario Hotel Transfer');
    }

    if (!empty($datos['tours'])) {
        $html .= "<div style='color: #2a4e33; margin-top:20px; font-weight:800' class='{$sectionClass}  section-tittle_feature'>Tours y Guías</div>";
        $html .= "<table class='{$tableClass}'>";
        foreach ($datos['tours'] as $t) {
            $nombre = esc((string)($t['tours_name'] ?? ''));
            $calif  = esc((string)($t['tours_calificacion'] ?? ''));
            $html .= "<tr>
                <td class='val-name'>{$nombre}</td>
                <td class='val-rating'>{$calif}</td>
            </tr>";
        }
        $html .= "</table>";
        $html .= $renderComment($datos['comentariosToursGuia'], "<div class='container-comentario'><span style='color: #2a4e33; margin-top:20px; font-weight:800'>Comentario Tours y Guías</span></div>");
    }

    if (!empty($datos['hotel'])) {
        $html .= "<div class='{$sectionClass}'  style='color: #2a4e33; margin-top:20px; font-weight:800'>Hoteles</div>";
        $html .= "<table class='{$tableClass}'>";
        foreach ($datos['hotel'] as $h) {
            $nombre = esc((string)($h['hotel_name'] ?? ''));
            $ubi    = !empty($h['hotel_ubicacion']) ? '(' . esc((string)$h['hotel_ubicacion']) . ')' : '';
            $calif  = esc((string)($h['hotel_calificacion'] ?? ''));
            $html .= "<tr>
                <td class='val-name'>{$nombre} {$ubi}</td>
                <td class='val-rating'>{$calif}</td>
            </tr>";
        }
        $html .= "</table>";
        $html .= $renderComment($datos['comentarioHotel'], 'Comentario Hotel');
    }

    if (!empty($datos['restaurantes'])) {
        $html .= "<div class='{$sectionClass}  section-tittle_feature'  style='color: #2a4e33; margin-top:20px; font-weight:800' >Restaurantes</div>";
        $html .= "<table class='{$tableClass}'>";
        foreach ($datos['restaurantes'] as $r) {
            $nombre = esc((string)($r['restaurante_name'] ?? ''));
            $ubi    = !empty($r['restaurante_ubicacion']) ? '(' . esc((string)$r['restaurante_ubicacion']) . ')' : '';
            $calif  = esc((string)($r['restaurante_calificacion'] ?? ''));
            $html .= "<tr>
                <td class='val-name'>{$nombre} {$ubi}</td>
                <td class='val-rating'>{$calif}</td>
            </tr>";
        }
        $html .= "</table>";
        $html .= $renderComment($datos['comentarioRestaurante'], 'Comentario Restaurante');
    }

    if ($datos['comentario'] !== '-') {
        $html .= "<div class='{$sectionClass}  section-tittle_feature' style='color: #2a4e33; margin-top:20px; font-weight:800'>Comentario General</div>";
        if ($esPDF) {
            $html .= "<div class='{$commentClass}'>{$datos['comentario']}</div>";
        } else {
            $html .= "<div class='{$commentClass}'><p>{$datos['comentario']}</p></div>";
        }
    }

    return $html;
}

try {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Arial');

    $dompdf   = new Dompdf($options);
    $anioActual = date('Y');

  
    $htmlPDF = "
    <html>
    <head>
        <style>
            body { font-family: 'Helvetica', sans-serif; margin: 10px 20px; color: #2c2c2c; }
            .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            .title { color: #2a4e33; font-size: 24px; font-weight: bold; text-transform: uppercase; }
            .info-table { width: 100%; margin-bottom: 20px; }
            .info-table td { padding: 4px 0; border: none; font-size: 13px; }
            .info-label { color: #2a4e33; font-weight: bold; width: 80px; }

            . section-tittle_feature{color: #2a4e33; font-weight:800; border-left: 10px solid red}
            .section-title { background: #ffffff; color: #2a4e33; padding: 5px 0; border-left: 4px solid #2a4e33; padding-left: 10px; font-size: 14px; font-weight: bold; margin-top: 25px; margin-bottom: 8px; }
            table.data-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
            table.data-table td { padding: 8px 5px; border-bottom: 1px solid #e8e8e8; font-size: 13px; }
            table.data-table td.val-name { text-align: left; color: #333; text-transform: lowercase; }
            table.data-table td.val-rating { text-align: right; font-weight: bold; color: #000; }
            .comment-box { background-color: #faf7f7; padding: 10px; border-left: 3px solid #617068; font-size: 12px; color: #555; margin-top: 8px; font-style: italic; }
            .footer { position: fixed; bottom: 10px; width: 100%; text-align: center; font-size: 9px; color: #555555; }
        </style>
    </head>
    <body>

        <table class='header-table' style='border-bottom: 2px solid #2a4e33; padding-bottom: 10px;'>
            <tr>
                <td style='vertical-align: middle;'>
                    <span class='title'>Resumen de Evaluación</span>
                </td>
                <td style='text-align: right; vertical-align: middle;'>
                    <img style='width: 300px; height: auto;' src='https://res.cloudinary.com/dlgeap8h0/image/upload/f_auto,q_auto/v1776726875/Group_22_vtvrk3.png' alt='logo'/>
                </td>
            </tr>
        </table>

        <table class='info-table'>
            <tr>
                <td class='info-label'>Nombre:</td>
                <td>{$datosAEnviar['nombre']}</td>
            </tr>
            <tr>
                <td class='info-label'>Fecha:</td>
                <td>{$datosAEnviar['fecha']}</td>
            </tr>
        </table>

        " . buildSections($datosAEnviar, true) . "

        <div class='footer'>Fiesta Tours Perú &copy; {$anioActual} - Todos los derechos reservados.</div>
    </body>
    </html>";

    $dompdf->loadHtml($htmlPDF);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfBuffer = $dompdf->output();

    $cleanedName = preg_replace('/\s+/', '_', $nombreContacto);
    $filename    = "Evaluacion_{$cleanedName}.pdf";

    $htmlEjecutivo = "
    <!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
    <html xmlns=\"http://www.w3.org/1999/xhtml\">
    <head>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />
        <style type=\"text/css\">
            body, table, td { font-family: Arial, sans-serif; font-size: 14px; color: #2c2c2c; margin: 0; padding: 0; }
            .wrapper { width: 100%;padding: 20px 0; }
            .container { width: 700px; margin: 0 auto; background-color: #ffffff; }
            .header {padding: 14px 20px; text-align: center; }
            .header span { color: #2a4e33; font-size: 22px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
            .content { padding: 20px 24px; }
            .info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
            .info-table td { font-size: 12px; padding: 4px 0; }
            .lbl { color: #2a4e33; font-weight: bold; width: 70px; }
            .val { color: #333333; }
            .section-title { font-size: 12px; font-weight: 800; color: #2a4e33; border-bottom: 2px solid #2a4e33; padding-bottom: 4px; margin-top: 16px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
            .data-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
            .data-table td { font-size: 12px; padding: 6px 4px; border-bottom: 1px solid #eeeeee; }
            .val-name { text-align: left; color: #444444; }
            .val-rating { text-align: right; font-weight: bold; color: #2a4e33; white-space: nowrap; }
            .comment-box { background-color: #f5f5f5; border-left: 3px solid #2a4e33; padding: 8px 10px; font-size: 11px; color: #666666; font-style: italic; margin-bottom: 12px; }
            .footer-row { background-color: #f9f9f9; border-top: 1px solid #eeeeee; padding: 10px 24px; text-align: center; font-size: 10px; color: #999999; }
        </style>
    </head>
    <body>
    <table class=\"wrapper\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">
    <tr><td align=\"center\">
    <table class=\"container\" cellpadding=\"0\" cellspacing=\"0\">

        <tr><td class=\"header\">
            <span>Resumen de Evaluaci&oacute;n</span>
        </td></tr>

        <tr><td class=\"content\">
            <table class=\"info-table\">
                <tr>
                    <td class=\"lbl\">Nombre:</td>
                    <td class=\"val\">{$datosAEnviar['nombre']}</td>
                </tr>
                <tr>
                    <td class=\"lbl\">Fecha:</td>
                    <td class=\"val\">{$datosAEnviar['fecha']}</td>
                </tr>
            </table>

            " . buildSections($datosAEnviar, false) . "

        </td></tr>

        <tr><td class=\"footer-row\">
            Fiesta Tours Per&uacute; &copy; {$anioActual} &mdash; Todos los derechos reservados.
        </td></tr>

    </table>
    </td></tr>
    </table>
    </body>
    </html>
    ";


    $mailEjecutivo = getTransporter();
    $mailEjecutivo->setFrom(USER_1, 'Fiesta Tours Peru - Evaluaciones');
    $correos = explode(',', $correoDestino);
    foreach ($correos as $correo) {
        $mailEjecutivo->addAddress(trim($correo));
    }
    $mailEjecutivo->addReplyTo($emailContacto, $nombreContacto);
    $mailEjecutivo->Subject = "Nuevo Registro - {$nombreContacto}";
    $mailEjecutivo->isHTML(true);
    $mailEjecutivo->Body    = $htmlEjecutivo;
    $mailEjecutivo->AltBody = "Resumen de Evaluacion\n\nNombre: {$nombreContacto}\nFecha: {$datosAEnviar['fecha']}\n\nRevise el PDF adjunto para ver el detalle completo.";
    $mailEjecutivo->addStringAttachment($pdfBuffer, $filename, 'base64', 'application/pdf');
    $mailEjecutivo->send();

    echo json_encode([
        "ok"       => true,
        "receptor" => $correoDestino,
        "mensaje"  => "Datos y PDF enviados correctamente."
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "ok"    => false,
        "error" => $e->getMessage()
    ]);
}