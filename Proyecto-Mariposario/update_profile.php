<?php
session_start();
include 'DB.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No has iniciado sesión.']);
    exit;
}

$userId = $_SESSION['user_id'];
$nombre = $_POST['nombre'] ?? '';
$apellido = $_POST['apellido'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$direccion = $_POST['direccion'] ?? '';

$fotoPath = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $fotoPath = 'uploads/perfil_' . $userId . '.' . $ext;
    move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath);
}

$query = "UPDATE Usuario SET Nombre=?, Apellido=?, Telefono=?, Direccion=?";
$params = [$nombre, $apellido, $telefono, $direccion];
$types = "ssss";

if ($fotoPath) {
    $query .= ", Foto_Perfil=?";
    $params[] = $fotoPath;
    $types .= "s";
}

$query .= " WHERE ID_Usuario=?";
$params[] = $userId;
$types .= "i";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar.']);
}
