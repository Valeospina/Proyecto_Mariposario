<?php
require_once 'DB.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telefono = htmlspecialchars(trim($_POST['telefono']));
    $evento_id = intval($_POST['evento']);
    $personas = intval($_POST['personas']);
    $mensaje = htmlspecialchars(trim($_POST['mensaje'])); // descripción

    // Obtener nombre y fecha del evento desde la base de datos
    $stmt = $conn->prepare("SELECT Nombre, Fecha FROM Evento WHERE ID_Evento = ?");
    $stmt->bind_param("i", $evento_id);
    $stmt->execute();
    $stmt->bind_result($nombre_evento, $fecha_evento);
    $evento_valido = $stmt->fetch();
    $stmt->close();

    if (!$evento_valido) {
        die("<div style='color: red; font-weight: bold;'>Error: El evento seleccionado no es válido.</div>");
    }

    // Verificar si ya existe una reserva en esa fecha para ese evento
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Reserva WHERE Fecha_Reserva = ? AND ID_Evento = ?");
    $stmt->bind_param("si", $fecha_evento, $evento_id);
    $stmt->execute();
    $stmt->bind_result($existe);
    $stmt->fetch();
    $stmt->close();

    if ($existe > 0) {
        echo "<div style='background-color: #ffe6e6; padding: 15px; border-radius: 10px; border: 1px solid red;'>
                <h3 style='color: red;'>Ya hay una reserva para ese evento en esa fecha.</h3>
                <a href='menu.php' style='display: inline-block; margin-top: 15px; background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Volver al Menú</a>
              </div>";
    } else {
        // Insertar la reserva con la fecha obtenida del evento
        $stmt = $conn->prepare("INSERT INTO Reserva (ID_Evento, cantidad_personas, Fecha_Reserva, telefono, correo, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $evento_id, $personas, $fecha_evento, $telefono, $email, $mensaje);

        if ($stmt->execute()) {
            echo "
            <div style='background: #f9f9f9; border: 1px solid #d1e7dd; border-left: 5px solid #198754; padding: 30px; border-radius: 10px; max-width: 700px; margin: 30px auto; font-family: Arial, sans-serif; box-shadow: 0 4px 8px rgba(0,0,0,0.05);'>
                <h2 style='color: #198754; margin-bottom: 10px;'>
                    <img src='https://img.icons8.com/color/48/000000/ok--v1.png' style='vertical-align: middle; width: 30px; height: 30px;'/>
                    Reserva Confirmada
                </h2>
                <ul style='list-style: none; padding: 0; font-size: 15px; color: #555;'>
                    <li><strong>Nombre:</strong> $nombre</li>
                    <li><strong>Email:</strong> $email</li>
                    <li><strong>Teléfono:</strong> $telefono</li>
                    <li><strong>Fecha del Evento:</strong> " . date("d/m/Y", strtotime($fecha_evento)) . "</li>
                    <li><strong>Tipo de Evento:</strong> $nombre_evento</li>
                    <li><strong>Personas:</strong> $personas</li>
                    <li><strong>Comentarios:</strong> " . (!empty($mensaje) ? nl2br($mensaje) : 'Ninguno') . "</li>
                </ul>
                <a href='index.php' style='display: inline-block; margin-top: 20px; background-color: #198754; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Volver al inicio</a>
            </div>";
        } else {
            echo "<div style='color: red;'>Error al guardar la reserva: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }

    $conn->close();
}
?>

