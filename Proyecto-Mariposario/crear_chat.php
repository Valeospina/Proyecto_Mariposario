<?php
session_start();
include 'DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tema = $_POST['tema'] ?? '';
    $consultaId = $_POST['consultaId'] ?? '';
    $userId = $_SESSION['user_id'] ?? null;

    if (!$tema || !$consultaId || !$userId) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        exit;
    }

    $sql = "INSERT INTO Consulta (ID_Consulta, ID_Usuario, Tema, Estado, Canal, Mensajes, Fecha)
            VALUES (?, ?, ?, 'Pendiente', 'Chat', '[]', NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $consultaId, $userId, $tema);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
?>
