<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './vendor/autoload.php';

function sendRecoveryEmail($to, $token) {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0;
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ecomariposa7@gmail.com';
        $mail->Password = 'tmzoonhaaonwdwag';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('ecomariposa7@gmail.com', 'Mariposario');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Recuperación de contraseña';

        $resetLink = "http://localhost/proyecto_Mariposario/Proyecto-Mariposario/reset.php?token=$token";
        $html = file_get_contents('./correo_template.html');
        $html = str_replace('{{ENLACE}}', $resetLink, $html);

        $mail->Body = $html;
        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        //echo "Error al enviar correo: {$mail->ErrorInfo}";
    }
}
?>
