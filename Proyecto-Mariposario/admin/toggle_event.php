<?php
session_start();
include '../DB.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

$event_id = intval($_GET['id'] ?? 0);
$state = intval($_GET['state'] ?? 1); // 1 = activar, 0 = desactivar

if ($event_id <= 0) {
    header('Location: eventoAdmin.php?message=ID inválido&type=danger');
    exit;
}

$stmt = $conn->prepare("UPDATE Evento SET Activo = ? WHERE ID_Evento = ?");
$stmt->bind_param("ii", $state, $event_id);
$stmt->execute();
$stmt->close();

$mensaje = $state == 1 ? "Evento activado correctamente" : "Evento desactivado";
header("Location: eventoAdmin.php?message=" . urlencode($mensaje) . "&type=success");
exit;
?>
