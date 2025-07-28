<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function enviarCorreoConfirmacion($datos) {
    $mail = new PHPMailer(true);
    try {
        // SMTP Configuración
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ecomariposa7@gmail.com'; 
        $mail->Password   = 'TU_APP_PASSWORD'; // Usa App Password seguro
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Remitente
        $mail->setFrom('ecomariposa7@gmail.com', 'EcoMariposa');
        $mail->addAddress($datos['email'], $datos['nombre']); // Cliente
        $mail->addBCC('ecomariposa7@gmail.com'); // Admin recibe copia

        // Embebemos el logo
        $logoPath = '/ruta/a/logo.png'; // Ajusta ruta real
        $mail->AddEmbeddedImage($logoPath, 'logoimg');

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = "✅ Confirmación de tu reserva en EcoMariposa";

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; color:#333;'>
            <div style='text-align:center; padding:20px; background:#f5f5f5;'>
                <img src='cid:logoimg' alt='EcoMariposa' style='max-width:150px;'>
            </div>
            <h2 style='color:#198754; text-align:center;'>¡Reserva Confirmada!</h2>
            <p>Hola <strong>{$datos['nombre']}</strong>,</p>
            <p>Gracias por reservar con <strong>EcoMariposa</strong>. Aquí tienes los detalles:</p>
            <table style='width:100%; border-collapse:collapse; font-size:14px;'>
                <tr><td style='padding:8px; border-bottom:1px solid #ddd;'>Evento:</td><td>{$datos['nombre_evento']}</td></tr>
                <tr><td style='padding:8px; border-bottom:1px solid #ddd;'>Fecha:</td><td>" . date("d/m/Y", strtotime($datos['fecha_evento'])) . "</td></tr>
                <tr><td style='padding:8px; border-bottom:1px solid #ddd;'>Personas:</td><td>{$datos['personas']}</td></tr>
                <tr><td style='padding:8px; border-bottom:1px solid #ddd;'>Teléfono:</td><td>{$datos['telefono']}</td></tr>
                <tr><td style='padding:8px;'>Comentarios:</td><td>" . (!empty($datos['mensaje']) ? nl2br($datos['mensaje']) : 'Ninguno') . "</td></tr>
            </table>
            <p style='margin-top:20px;'>Si tienes dudas, responde a este correo.</p>
            <div style='text-align:center; margin-top:20px;'>
                <a href='https://tusitio.com' style='background:#198754; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px;'>Visitar Sitio</a>
            </div>
            <p style='font-size:12px; color:#777; text-align:center; margin-top:20px;'>© " . date("Y") . " EcoMariposa. Todos los derechos reservados.</p>
        </div>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando correo: {$mail->ErrorInfo}");
    }
}
