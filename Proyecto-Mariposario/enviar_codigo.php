<?php
session_start();

// Asegúrate de tener estos datos
$usuario_email = $_SESSION['user_email'] ?? '';
$usuario_nombre = $_SESSION['user_name'] ?? 'Usuario';

if (!$usuario_email) {
    echo json_encode(['success' => false, 'message' => 'No se encontró el correo del usuario']);
    exit;
}

// Generar código aleatorio de 6 dígitos
$codigo = rand(100000, 999999);

// Guardar el código en sesión (también podrías guardarlo en la base de datos si lo prefieres)
$_SESSION['codigo_verificacion'] = $codigo;

// Configurar envío de correo
$to = $usuario_email;
$subject = "Código de seguridad para acceder a PayPal";
$message = "
<html>
<head>
  <title>Código de Seguridad</title>
</head>
<body>
  <p>Hola $usuario_nombre,</p>
  <p>Tu código de verificación es: <strong style='font-size: 20px;'>$codigo</strong></p>
  <p>Ingresa este código en la página para continuar con tu acceso.</p>
  <br>
  <p>Si no solicitaste este código, por favor ignora este mensaje.</p>
</body>
</html>
";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: EcoMariposas <no-reply@ecomariposas.com>" . "\r\n";

// Enviar el correo
if (mail($to, $subject, $message, $headers)) {
    echo json_encode(['success' => true, 'message' => 'Correo enviado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al enviar el correo']);
}
?>