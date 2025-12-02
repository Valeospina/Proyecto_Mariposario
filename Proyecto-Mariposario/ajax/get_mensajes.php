<?php
require_once "../DB.php";

$id_consulta = $_GET['id'] ?? $_GET['id_consulta'] ?? 0;

if (!$id_consulta) {
    exit(json_encode([]));
}

$sql = "SELECT id_usuario, tipo, contenido as text, ruta_archivo, fecha_envio,
        CASE 
            WHEN id_usuario = 0 OR tipo = 'sistema' THEN 'Admin'
            ELSE 'Cliente'
        END as role
        FROM chat_mensajes 
        WHERE id_consulta = ? 
        ORDER BY id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_consulta);
$stmt->execute();
$result = $stmt->get_result();

$mensajes = [];
while ($row = $result->fetch_assoc()) {
    $mensajes[] = $row;
}

header('Content-Type: application/json');
echo json_encode($mensajes);
?>