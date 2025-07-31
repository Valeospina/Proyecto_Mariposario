<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ajusta estas rutas si tienes PHPMailer en otro sitio:
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

class FacturaEmailService
{
    /**
     * Envía la factura en PDF al cliente
     *
     * @param array $datos ['nombre' => 'Nombre Cliente', 'email' => 'cliente@correo.com']
     * @param string $rutaFactura Ruta completa del archivo PDF
     * @return bool
     */
    public function enviarFactura(array $datos, string $rutaFactura): bool
    {
        $mail = new PHPMailer(true);
        try {
            //  Configuración SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ecomariposa7@gmail.com';
            $mail->Password   = 'pjzp zldn thrp tisa'; // Usa una clave de aplicación segura
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            //  Remitente y destinatarios
            $mail->setFrom('ecomariposa7@gmail.com', 'EcoMariposa');
            $mail->addAddress($datos['email'], $datos['nombre']);
            $mail->addBCC('ecomariposa7@gmail.com'); // Copia oculta interna

            //  Adjuntar factura PDF
            if (file_exists($rutaFactura)) {
                $mail->addAttachment($rutaFactura, 'Factura_EcoMariposa.pdf');
            }

            //  Logo embebido (opcional)
            $logoPath = __DIR__ . '/img/logo.png';
            if (file_exists($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'logoimg');
            }

            //  Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Factura de tu compra en EcoMariposa';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; color:#333;'>
                    <div style='text-align:center; padding:20px; background:#f5f5f5;'>
                        <img src='cid:logoimg' alt='EcoMariposa' style='max-width:150px;'>
                    </div>
                    <h2 style='color:#198754; text-align:center;'>¡Gracias por tu compra!</h2>
                    <p>Hola <strong>{$datos['nombre']}</strong>,</p>
                    <p>Adjuntamos la factura en PDF correspondiente a tu compra en <strong>EcoMariposa</strong>.</p>
                    <p style='margin-top:15px;'>Si tienes alguna consulta, responde a este correo.</p>
                </div>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Error enviando factura: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
