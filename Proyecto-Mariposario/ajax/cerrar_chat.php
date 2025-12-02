<?php
require_once "../DB.php";
session_start();

$id_consulta = $_POST['id_consulta'] ?? 0;
$id_usuario = $_SESSION['user_id'] ?? null;

if (!$id_consulta || !$id_usuario) {
    exit(json_encode(['cerrado' => false, 'error' => 'Datos incompletos']));
}

// Verificar que la consulta pertenece al usuario
$sql = "UPDATE Consulta SET Estado = 'Cerrado', Fecha_Cierre = NOW() 
        WHERE ID_Consulta = ? AND ID_Usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $id_consulta, $id_usuario);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['cerrado' => true]);
} else {
    echo json_encode(['cerrado' => false, 'error' => 'No se pudo cerrar la consulta']);
}
?>