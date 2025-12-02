<?php
require_once "../DB.php";
session_start();

$id_usuario = $_SESSION['user_id'] ?? null;
$tema = $_POST['tema'] ?? 'Consulta';
$consultaId = $_POST['consultaId'] ?? null;

if (!$id_usuario) {
    exit(json_encode(['error' => 'No autenticado']));
}

// Verificar si ya existe una consulta con este ID
if ($consultaId) {
    $sql = "SELECT ID_Consulta FROM Consulta WHERE ID_Consulta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $consultaId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            'status' => 'ok',
            'id_consulta' => $consultaId,
            'existe' => true
        ]);
        exit;
    }
}

// Crear nueva consulta
$sql = "INSERT INTO Consulta (ID_Consulta, ID_Usuario, Tema, Estado, Canal, Fecha_Creacion) 
        VALUES (?, ?, ?, 'Pendiente', 'Chat', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iis', $consultaId, $id_usuario, $tema);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'ok',
        'id_consulta' => $consultaId
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => $conn->error
    ]);
}
?>