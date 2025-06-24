<?php
session_start();
include '../DB.php'; // Archivo de conexión a la base de datos

// Protección: solo admin puede acceder
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';

if ($product_id <= 0) {
    $message = "ID de producto no válido para eliminar.";
    $message_type = "danger";
    header('Location: products.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
    exit;
}

try {
    if ($conn instanceof mysqli) {
        // Iniciar una transacción para asegurar la integridad de los datos
        $conn->begin_transaction();

        // 1. Eliminar ítems de inventario asociados a este producto
        $stmt_inventario = $conn->prepare("DELETE FROM Inventario WHERE ID_Producto = ?");
        if (!$stmt_inventario) {
            throw new Exception("Error al preparar la eliminación de inventario: " . $conn->error);
        }
        $stmt_inventario->bind_param("i", $product_id);
        if (!$stmt_inventario->execute()) {
            throw new Exception("Error al eliminar ítems de inventario asociados: " . $stmt_inventario->error);
        }
        $stmt_inventario->close();

        // 2. Eliminar el producto de la tabla Producto
        $stmt_producto = $conn->prepare("DELETE FROM Producto WHERE ID_Producto = ?");
        if (!$stmt_producto) {
            throw new Exception("Error al preparar la eliminación del producto: " . $conn->error);
        }
        $stmt_producto->bind_param("i", $product_id);
        if ($stmt_producto->execute()) {
            // Confirmar la transacción si todo fue bien
            $conn->commit();
            $message = "Producto y sus ítems de inventario asociados eliminados exitosamente.";
            $message_type = "success";
        } else {
            throw new Exception("Error al eliminar el producto: " . $stmt_producto->error);
        }
        $stmt_producto->close();
    } else {
        throw new Exception("Conexión a la base de datos no válida o no disponible.");
    }
} catch (Exception $e) {
    // Si algo falla, revertir la transacción
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }
    error_log("Error al eliminar producto y/o inventario: " . $e->getMessage());
    $message = "Error al eliminar el producto: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
} finally {
    // Redirigir siempre al final
    header('Location: products.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
    exit;
}

// Cierra la conexión a la base de datos si está abierta y es MySQLi (aunque el exit lo hará antes)
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>