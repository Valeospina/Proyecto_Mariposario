<?php
// Incluimos la conexión
require_once 'DB.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Obtener y sanitizar los datos del formulario
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telefono = htmlspecialchars(trim($_POST['telefono']));
    $evento_id = intval($_POST['evento']);
    $fecha = $_POST['fecha'];
    $personas = intval($_POST['personas']);
    $mensaje = htmlspecialchars(trim($_POST['mensaje']));

    // Validar evento
    $eventos_validos = [1, 2, 3];
    if (!in_array($evento_id, $eventos_validos)) {
        die("<div style='color: red; font-weight: bold;'>Error: El evento seleccionado no es válido.</div>");
    }

    // Verificar si ya existe una reserva en esa fecha para ese evento
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Reserva WHERE Fecha_Reserva = ? AND ID_Evento = ?");
    $stmt->bind_param("si", $fecha, $evento_id);
    $stmt->execute();
    $stmt->bind_result($existe);
    $stmt->fetch();
    $stmt->close();

    if ($existe > 0) {
        echo "<div style='background-color: #ffe6e6; padding: 15px; border-radius: 10px; border: 1px solid red;'>
                <h3 style='color: red;'>Lo sentimos, ya hay una reserva para ese evento en la fecha seleccionada.</h3>
                <p>Por favor, elige otra fecha o contáctanos para más información.</p>
                <a href='menu.php' style='display: inline-block; margin-top: 15px; background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Volver al Menú</a>
              </div>";
    } else {
        // Insertar la reserva
        $stmt = $conn->prepare("INSERT INTO Reserva (ID_Evento, cantidad_personas, Fecha_Reserva) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $evento_id, $personas, $fecha);

        if ($stmt->execute()) {
            echo '
            <div style="
                background: #f9f9f9;
                border: 1px solid #d1e7dd;
                border-left: 5px solid #198754;
                padding: 30px;
                border-radius: 10px;
                max-width: 700px;
                margin: 30px auto;
                font-family: Arial, sans-serif;
                box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            ">
                <h2 style="color: #198754; margin-bottom: 10px;">
                    <img src="https://img.icons8.com/color/48/000000/ok--v1.png" alt="check" style="vertical-align: middle; width: 30px; height: 30px;"/>
                    Reserva Confirmada
                </h2>
                <p style="font-size: 16px; color: #333;">
                    ¡Gracias por reservar con nosotros! A continuación se muestran los detalles de tu reserva:
                </p>
                <hr style="border: none; border-top: 1px solid #ccc; margin: 15px 0;">
                <ul style="list-style: none; padding: 0; font-size: 15px; color: #555;">
                    <li><strong> Nombre:</strong> ' . $nombre . '</li>
                    <li><strong> Correo Electrónico:</strong> ' . $email . '</li>
                    <li><strong> Teléfono:</strong> ' . $telefono . '</li>
                    <li><strong> Fecha del Evento:</strong> ' . date("d/m/Y", strtotime($fecha)) . '</li>
                    <li><strong> Tipo de Evento:</strong> ' . getNombreEvento($evento_id) . '</li>
                    <li><strong> Cantidad de Personas:</strong> ' . $personas . '</li>
                    <li><strong> Comentarios Adicionales:</strong> ' . (!empty($mensaje) ? nl2br($mensaje) : 'Ninguno') . '</li>
                </ul>
                <hr style="border: none; border-top: 1px solid #ccc; margin: 15px 0;">
                <p style="font-size: 14px; color: #666;">
                    Nos pondremos en contacto contigo por correo o teléfono para confirmar los detalles finales.
                </p>
                <p style="font-size: 14px; color: #666;">
                    ¿Tienes dudas? <a href="contacto.php" style="color: #198754; text-decoration: none;">Contáctanos aquí</a>.
                </p>
                <a href="index.html" style="
                    display: inline-block;
                    margin-top: 20px;
                    background-color: #198754;
                    color: white;
                    padding: 10px 20px;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: bold;
                ">Volver al inicio</a>
            </div>';
        } else {
            echo "<div style='color: red;'>Error al guardar la reserva: " . $stmt->error . "</div>";
        }

        $stmt->close();
    }

    $conn->close();
}

// Función para mostrar el nombre del evento según su ID
function getNombreEvento($id) {
    $eventos = [
        1 => "Taller de Mariposas",
        2 => "Visita Guiada",
        3 => "Charla de Orquídeas"
    ];
    return $eventos[$id] ?? "Evento desconocido";
}
?>