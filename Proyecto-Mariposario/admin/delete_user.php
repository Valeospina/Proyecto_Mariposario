<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección: solo admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html');
    exit;
}

$message = '';
$message_type = '';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Prevención: No permitir que un admin se elimine a sí mismo (opcional pero recomendado)
    if ($user_id == $_SESSION['user_id']) {
        $message = "No puedes eliminar tu propia cuenta de administrador.";
        $message_type = "danger";
        header('Location: users.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
        exit;
    }

    // Prepara la consulta para eliminar el usuario
    $delete_query = "DELETE FROM Usuario WHERE ID_Usuario = ?";

    try {
        if (isset($conn) && $conn instanceof mysqli) {
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $message = "Usuario eliminado exitosamente.";
                $message_type = "success";
            } else {
                $message = "No se pudo eliminar el usuario o no se encontró.";
                $message_type = "warning";
            }
            $stmt->close();
        } else {
            throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
        }
    } catch (Exception $e) {
        error_log("Error al eliminar usuario (ID: $user_id): " . $e->getMessage());
        $message = "Error al eliminar el usuario: " . htmlspecialchars($e->getMessage());
        $message_type = "danger";
    }
} else {
    $message = "ID de usuario no proporcionado para eliminar.";
    $message_type = "danger";
}

// Redirigir de vuelta a la página de usuarios con el mensaje
header('Location: users.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
exit;

// Cierra la conexión a la base de datos (aunque la redirección ya detendría el script)
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>