<?php
require_once 'ReservaManager.php';

// Configura conexión PDO a base de datos
$pdo = new PDO('mysql:host=localhost;dbname=mariposario', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$reservaManager = new ReservaManager($pdo);

// Crear reserva con datos de formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evento_id = $_POST['evento_id'];
    $fecha = $_POST['fecha_reserva'];
    $usuario = $_POST['usuario'];
    $cantidad = $_POST['cantidad_personas'];

    $resultado = $reservaManager->crearReserva($evento_id, $fecha, $usuario, $cantidad);

    if ($resultado['success']) {
        echo $resultado['message'];
    } else {
        echo "Error: " . $resultado['message'];
    }
}

?>
