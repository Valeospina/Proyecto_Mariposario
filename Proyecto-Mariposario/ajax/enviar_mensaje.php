<?php
require_once "../DB.php";
session_start();

$id_usuario = $_SESSION['user_id'] ?? null;
$id_consulta = $_POST['id'] ?? $_POST['id_consulta'] ?? null;
$mensaje = $_POST['text'] ?? $_POST['mensaje'] ?? '';
$role = $_POST['role'] ?? 'Cliente';
$hayArchivo = isset($_FILES['archivo']);

if (!$id_usuario || !$id_consulta) {
    exit(json_encode(['success' => false, 'error' => 'Datos incompletos']));
}

$tipo = "texto";
$ruta_archivo = null;

/* ============================================================
   VALIDACIÓN Y SUBIDA DE ARCHIVOS
============================================================ */
if ($hayArchivo) {
    $permitidos = [
        "image/jpeg" => "imagen",
        "image/png" => "imagen",
        "image/gif" => "imagen",
        "video/mp4" => "video",
        "video/quicktime" => "video",
        "application/pdf" => "documento",
        "application/msword" => "documento",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "documento"
    ];

    $mime = $_FILES['archivo']['type'];
    $size = $_FILES['archivo']['size'];

    if ($size > 10 * 1024 * 1024) {
        exit(json_encode(['success' => false, 'error' => 'Archivo excede los 10MB']));
    }

    if (!isset($permitidos[$mime])) {
        exit(json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']));
    }

    $tipo = $permitidos[$mime];

    // Directorio
    $dir = "chat/uploads/" . $id_consulta . "/";
    if (!file_exists($dir)) {
        mkdir($dir, 0775, true);
    }

    $extension = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
    $nuevoNombre = time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;
    $destino = $dir . $nuevoNombre;

    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
        $ruta_archivo = "chat/uploads/$id_consulta/$nuevoNombre";
    }
}

/* ============================================================
   INSERTAR MENSAJE
============================================================ */
$sql = "INSERT INTO chat_mensajes (id_consulta, id_usuario, tipo, contenido, ruta_archivo, fecha_envio)
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iisss', $id_consulta, $id_usuario, $tipo, htmlspecialchars($mensaje), $ruta_archivo);

if ($stmt->execute()) {
    // Actualizar estado de la consulta
    $updateSql = "UPDATE Consulta SET Estado = 'En Proceso', Fecha_Actualizacion = NOW() WHERE ID_Consulta = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param('i', $id_consulta);
    $updateStmt->execute();
    
    echo json_encode([
        'success' => true,
        'id_mensaje' => $conn->insert_id,
        'archivo' => $ruta_archivo
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $conn->error
    ]);
}
?>