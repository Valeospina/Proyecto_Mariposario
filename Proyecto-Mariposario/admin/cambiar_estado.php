<?php
session_start();
include '../DB.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    exit(json_encode(['success' => false, 'error' => 'No autorizado']));
}

$id_consulta = $_POST['id'] ?? 0;
$nuevo_estado = $_POST['estado'] ?? '';

$estados_validos = ['Pendiente', 'En Proceso', 'Resuelto', 'Cerrado'];

if (!$id_consulta || !in_array($nuevo_estado, $estados_validos)) {
    exit(json_encode(['success' => false, 'error' => 'Datos inválidos']));
}

$sql = "UPDATE Consulta SET Estado = ?, Fecha_Actualizacion = NOW() WHERE ID_Consulta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $nuevo_estado, $id_consulta);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'estado' => $nuevo_estado]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>