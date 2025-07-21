<?php
include 'DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_evento = intval($_POST['id_evento']);
    $usuario = trim($_POST['usuario']);
    $resena = trim($_POST['resena']);

    if ($conn && !$conn->connect_error && $id_evento > 0 && $usuario && $resena) {
        $stmt = $conn->prepare("INSERT INTO resenas_evento (ID_Evento, Usuario, Reseña, Fecha) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $id_evento, $usuario, $resena);
        $stmt->execute();
        $stmt->close();
    }

    // Redirigir de vuelta
    header("Location: Eventos.php");
    exit();
}
?>
