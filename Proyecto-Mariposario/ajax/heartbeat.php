<?php
require_once "../DB.php";
session_start();

$id_usuario = $_SESSION['user_id'] ?? null;
$id_consulta = $_POST['id_consulta'] ?? 0;

if (!$id_usuario || !$id_consulta) {
    exit(json_encode(["ok" => false]));
}

// Actualizar última actividad del usuario en la consulta
$sql = "UPDATE Consulta SET Fecha_Actualizacion = NOW() 
        WHERE ID_Consulta = ? AND ID_Usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $id_consulta, $id_usuario);
$stmt->execute();

echo json_encode(["ok" => true]);
?>