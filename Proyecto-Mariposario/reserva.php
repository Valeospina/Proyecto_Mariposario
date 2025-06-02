<?php
session_start(); // Inicia la sesión para poder acceder a $_SESSION

// Incluimos la conexión
require_once 'DB.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validar que el usuario esté logueado y tenga el ID en sesión
    if (!isset($_SESSION['ID_Usuario'])) {
        die("Error: Debes iniciar sesión para reservar.");
    }

    $id_usuario = $_SESSION['ID_Usuario']; // Obtener el ID del usuario de la sesión

    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $evento_id = intval($_POST['evento']); // Asegurarse de que es un número entero
    $fecha = $_POST['fecha'];
    $personas = intval($_POST['personas']); // Convertir a entero
    $mensaje = $_POST['mensaje'];

    // Validar evento
    $eventos_validos = [1, 2, 3];
    if (!in_array($evento_id, $eventos_validos)) {
        die("Error: El evento seleccionado no es válido.");
    }

    // Verificar si ya existe una reserva en esa fecha para ese evento
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Reserva WHERE Fecha_Reserva = ? AND ID_Evento = ?");
    $stmt->bind_param("si", $fecha, $evento_id);
    $stmt->execute();
    $stmt->bind_result($existe);
    $stmt->fetch();
    $stmt->close();

    if ($existe > 0) {
        echo "<h3>Lo sentimos, la fecha ya está reservada para este evento.</h3>";
    } else {

        echo "ID_Evento recibido: " . $evento_id . "<br>";

        // Insertar la reserva con el ID_Usuario de sesión
        $stmt = $conn->prepare("INSERT INTO Reserva (ID_Usuario, ID_Evento, cantidad_personas, Fecha_Reserva) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $id_usuario, $evento_id, $personas, $fecha);

        if ($stmt->execute()) {
            echo "<h3>¡Reserva realizada exitosamente!</h3>";
            echo "<ul>
                    <li><strong>Nombre:</strong> $nombre</li>
                    <li><strong>Correo:</strong> $email</li>
                    <li><strong>Teléfono:</strong> $telefono</li>
                    <li><strong>Evento:</strong> $evento_id</li>
                    <li><strong>Fecha:</strong> $fecha</li>
                    <li><strong>Personas:</strong> $personas</li>
                  </ul>";
        } else {
            echo "Error al guardar la reserva: " . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();
}
?>

