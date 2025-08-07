
<?php
require './mailer.php';
require './DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT ID_Usuario FROM Usuario WHERE Correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $exp = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $insert = $conn->prepare("INSERT INTO Recuperacion_Contrasena (ID_Usuario, Token, Expiracion) VALUES (?, ?, ?)");
        $insert->bind_param("iss", $user['ID_Usuario'], $token, $exp);
        $insert->execute();

        sendRecoveryEmail($email, $token);
        $msg = "Revisa tu correo para restablecer la contraseña.";
    } else {
        $msg = "Correo no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperación de Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/recuperacion.css">
</head>
<body>
<div class="d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="card shadow-lg p-4 form-card">
        <div class="text-center mb-4">
            <img src="https://cdn-icons-png.flaticon.com/512/295/295128.png" alt="icono" width="70">
            <h3 class="mt-2">¿Olvidaste tu contraseña?</h3>
            <p class="text-muted">Ingresa tu correo electrónico para recibir un enlace de recuperación.</p>
        </div>
        <?php if (!empty($msg)) echo "<div class='alert alert-info text-center'>$msg</div>"; ?>
        <form method="POST" id="recuperacionForm">
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" name="email" id="email" required placeholder="ejemplo@correo.com">
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Enviar enlace</button>
                <a href="logind.php" class="btn btn-outline-secondary">Volver al inicio de sesión</a>
            </div>
        </form>
    </div>
</div>
<script src="./js/recuperacion.js"></script>
</body>
</html>
