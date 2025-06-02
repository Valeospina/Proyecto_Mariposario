
<?php
require_once 'ReservaManager.php';
include 'DB.php';


// Crear instancia del gestor de reservas
$reservaManager = new ReservaManager($pdo);

// Validar eventos disponibles
$eventosValidos = [
    "Taller de Mariposas" => 1,
    "Visita Guiada" => 2,
    "Charla de Orquídeas" => 3
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $fecha = $_POST['fecha'];
    $cantidad = $_POST['personas'];
    $eventoNombre = $_POST['evento'];

    if (!isset($eventosValidos[$eventoNombre])) {
        echo "<div style='color: red; text-align: center; font-weight: bold;'>Evento no válido.</div>";
        exit;
    }

    $evento_id = $eventosValidos[$eventoNombre];
    $usuario = "$nombre ($correo)";

    $resultado = $reservaManager->crearReserva($evento_id, $fecha, $usuario, $cantidad);

    if ($resultado['success']) {
        echo "<div style='color: green; text-align: center; font-weight: bold;'>¡Reserva realizada correctamente!</div>";
    } else {
        echo "<div style='color: red; text-align: center;'>Error: " . $resultado['message'] . "</div>";
    }
}
?>
