<?php
require_once 'DB.php';

// Iniciar sesión para obtener el ID del usuario logueado
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre      = htmlspecialchars(trim($_POST['nombre']));
    $email       = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telefono    = htmlspecialchars(trim($_POST['telefono']));
    $evento_id   = intval($_POST['evento']);
    $personas    = intval($_POST['personas']);
    $mensaje     = htmlspecialchars(trim($_POST['mensaje'])); // descripción

if (session_status() === PHP_SESSION_NONE) session_start();

$id_usuario = null;
if (isset($_SESSION['user_id']))      $id_usuario = (int)$_SESSION['user_id'];
elseif (isset($_SESSION['ID_Usuario'])) $id_usuario = (int)$_SESSION['ID_Usuario'];

if (!$id_usuario) {
    die("<div style='color:red;font-weight:bold;'>Error: Debes estar logueado para hacer una reserva. <a href='loginD.php'>Iniciar sesión</a></div>");
}


    // 1) Obtener nombre y fecha del evento
    $stmt = $conn->prepare("SELECT Nombre, Fecha FROM Evento WHERE ID_Evento = ?");
    $stmt->bind_param("i", $evento_id);
    $stmt->execute();
    $stmt->bind_result($nombre_evento, $fecha_evento);
    $evento_valido = $stmt->fetch();
    $stmt->close();

    if (!$evento_valido) {
        die("<div style='color: red; font-weight: bold;'>Error: El evento seleccionado no es válido.</div>");
    }

    // 2) Calcular cuántas plazas ya están ocupadas en esa fecha para este evento
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(cantidad_personas), 0) 
          AS total_personas
        FROM Reserva
        WHERE Fecha_Reserva = ?
          AND ID_Evento = ?
    ");
    $stmt->bind_param("si", $fecha_evento, $evento_id);
    $stmt->execute();
    $stmt->bind_result($total_personas);
    $stmt->fetch();
    $stmt->close();

    // 3) Comprobar si al añadir esta reserva se supera el máximo de 10
    if ($total_personas + $personas > 10) {
        echo "
        <div style='background-color: #ffe6e6; padding: 15px; border-radius: 10px; border: 1px solid red;'>
            <h3 style='color: red;'>Lo siento, ya no hay cupo para este día (máximo 10 personas).</h3>
            <p>Plazas ocupadas: <strong>{$total_personas}</strong>. Intentas añadir: <strong>{$personas}</strong>.</p>
            <a href='eventos.php' style='display: inline-block; margin-top: 15px; background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Volver al Menú</a>
        </div>";
    } else {
        // 4) Insertar la nueva reserva CON el ID_Usuario
        $stmt = $conn->prepare("
            INSERT INTO Reserva 
                (ID_Evento, ID_Usuario, cantidad_personas, Fecha_Reserva, telefono, correo, descripcion) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiissss", $evento_id, $id_usuario, $personas, $fecha_evento, $telefono, $email, $mensaje);

        if ($stmt->execute()) {
            echo "
            <div style='background: #f9f9f9; border: 1px solid #d1e7dd; border-left: 5px solid #198754; padding: 30px; border-radius: 10px; max-width: 700px; margin: 30px auto; font-family: Arial, sans-serif; box-shadow: 0 4px 8px rgba(0,0,0,0.05);'>
                <h2 style='color: #198754; margin-bottom: 10px;'>
                    <img src='https://img.icons8.com/color/48/000000/ok--v1.png' style='vertical-align: middle; width: 30px; height: 30px;'/>
                    Reserva Confirmada
                </h2>
                <ul style='list-style: none; padding: 0; font-size: 15px; color: #555;'>
                    <li><strong>Nombre:</strong> {$nombre}</li>
                    <li><strong>Email:</strong> {$email}</li>
                    <li><strong>Teléfono:</strong> {$telefono}</li>
                    <li><strong>Fecha del Evento:</strong> " . date("d/m/Y", strtotime($fecha_evento)) . "</li>
                    <li><strong>Tipo de Evento:</strong> {$nombre_evento}</li>
                    <li><strong>Personas:</strong> {$personas}</li>
                    <li><strong>Comentarios:</strong> " . (!empty($mensaje) ? nl2br($mensaje) : 'Ninguno') . "</li>
                </ul>
                <a href='index.php' style='display: inline-block; margin-top: 20px; background-color: #198754; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Volver al inicio</a>
            </div>";

            require_once __DIR__ . '/EmailService.php'; // ruta correcta
            $emailService = new EmailService();
            $emailService->enviarCorreoConfirmacion([
                'nombre'        => $nombre,
                'email'         => $email,
                'telefono'      => $telefono,
                'fecha_evento'  => $fecha_evento,
                'nombre_evento' => $nombre_evento,
                'personas'      => $personas,
                'mensaje'       => $mensaje
            ]);
        } else {
            echo "<div style='color: red;'>Error al guardar la reserva: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }

    $conn->close();
}
?>
			
			<!-- jquery Min JS -->
			<script src="js/jquery.min.js"></script>
			<!-- jquery Migrate JS -->
			<script src="js/jquery-migrate-3.0.0.js"></script>
			<!-- jquery Ui JS -->
			<script src="js/jquery-ui.min.js"></script>
			<!-- Easing JS -->
			<script src="js/easing.js"></script>
			<!-- Color JS -->
			<script src="js/colors.js"></script>
			<!-- Popper JS -->
			<script src="js/popper.min.js"></script>
			<!-- Bootstrap Datepicker JS -->
			<script src="js/bootstrap-datepicker.js"></script>
			<!-- Jquery Nav JS -->
			<script src="js/jquery.nav.js"></script>
			<!-- Slicknav JS -->
			<script src="js/slicknav.min.js"></script>
			<!-- ScrollUp JS -->
			<script src="js/jquery.scrollUp.min.js"></script>
			<!-- Niceselect JS -->
			<script src="js/niceselect.js"></script>
			<!-- Tilt Jquery JS -->
			<script src="js/tilt.jquery.min.js"></script>
			<!-- Owl Carousel JS -->
			<script src="js/owl-carousel.js"></script>
			<!-- counterup JS -->
			<script src="js/jquery.counterup.min.js"></script>
			<!-- Steller JS -->
			<script src="js/steller.js"></script>
			<!-- Wow JS -->
			<script src="js/wow.min.js"></script>
			<!-- Magnific Popup JS -->
			<script src="js/jquery.magnific-popup.min.js"></script>
			<!-- Counter Up CDN JS -->
			<script src="http://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
			<!-- Bootstrap JS -->
			<script src="js/bootstrap.min.js"></script>
			<!-- Main JS -->
			<script src="js/main.js"></script>
		</body>
	</html>