<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos
 
// Protección de la página de administración
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html'); // Redirige si no está logueado o no es admin
    exit;
}
 
// Verifica si se proporcionó un ID de producto para eliminar
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = $_GET['id'];
 
    // Prepara la consulta para eliminar el producto
    // ¡Asegúrate de que la tabla sea 'Producto' como en tu DB!
    $delete_query = "DELETE FROM Producto WHERE ID_Producto = ?";
 
    try {
        // Usa sentencias preparadas para seguridad (evitar inyección SQL)
        if (isset($conn) && $conn instanceof mysqli) {
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $product_id); // "i" para entero (integer)
            $stmt->execute();
 
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = "Producto eliminado exitosamente.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "No se encontró el producto o no se pudo eliminar.";
                $_SESSION['message_type'] = "warning";
            }
            $stmt->close();
        } else {
            throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
        }
    } catch (Exception $e) {
        error_log("Error al eliminar producto (ID: $product_id): " . $e->getMessage());
        $_SESSION['message'] = "Error al eliminar el producto: " . htmlspecialchars($e->getMessage());
        $_SESSION['message_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "ID de producto no proporcionado para eliminar.";
    $_SESSION['message_type'] = "danger";
}
 
// Redirige de vuelta a la página de gestión de productos
header('Location: products.php');
exit;
 
// Cierra la conexión a la base de datos si está abierta y es MySQLi
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>