<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección de la página de administración:
// 1. Verifica si el usuario ha iniciado sesión.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// 2. Verifica si el rol del usuario es administrador (ID_Rol = 1).
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php'); // Redirige si no es administrador
    exit;
}

$message = '';
$message_type = '';
$redirect_url = 'gestion_empleados.php'; // URL a la que redirigir después de la operación

if (isset($_GET['id'])) {
    $empleado_usuario_id = $_GET['id']; // Este es el ID_Usuario del empleado a eliminar

    // 1. Evitar que un administrador se elimine a sí mismo
    if ($empleado_usuario_id == $_SESSION['user_id']) {
        $message = "No puedes eliminar tu propia cuenta de administrador.";
        $message_type = "danger";
    } else {
        try {
            // Iniciar transacción para asegurar que ambos registros (Usuario y Empleado) se eliminen o ninguno
            $conn->begin_transaction();

            // 2. Eliminar de la tabla Empleado primero (si existe un registro allí)
            // Es importante eliminar de tablas con FK que referencian a Empleado (ej. Horario, Asistencia, Pago_Empleado, Registro_Actividad)
            // antes de eliminar de Empleado, a menos que tengas ON DELETE CASCADE bien configurado en todas.
            // Dada tu estructura, Registro_Actividad, Horario, Pago_Empleado y Asistencia referencian a Empleado.
            // Vamos a eliminar de esas tablas primero para evitar errores de FK.

            $stmt_delete_reg_act = $conn->prepare("DELETE FROM Registro_Actividad WHERE ID_Empleado = (SELECT ID_Empleado FROM Empleado WHERE ID_Usuario = ?)");
            $stmt_delete_reg_act->bind_param("i", $empleado_usuario_id);
            $stmt_delete_reg_act->execute();
            $stmt_delete_reg_act->close();

            $stmt_delete_horario = $conn->prepare("DELETE FROM Horario WHERE ID_Empleado = (SELECT ID_Empleado FROM Empleado WHERE ID_Usuario = ?)");
            $stmt_delete_horario->bind_param("i", $empleado_usuario_id);
            $stmt_delete_horario->execute();
            $stmt_delete_horario->close();
            
            $stmt_delete_pago = $conn->prepare("DELETE FROM Pago_Empleado WHERE ID_Empleado = (SELECT ID_Empleado FROM Empleado WHERE ID_Usuario = ?)");
            $stmt_delete_pago->bind_param("i", $empleado_usuario_id);
            $stmt_delete_pago->execute();
            $stmt_delete_pago->close();

            $stmt_delete_asistencia = $conn->prepare("DELETE FROM Asistencia WHERE ID_Empleado = (SELECT ID_Empleado FROM Empleado WHERE ID_Usuario = ?)");
            $stmt_delete_asistencia->bind_param("i", $empleado_usuario_id);
            $stmt_delete_asistencia->execute();
            $stmt_delete_asistencia->close();


            // Ahora sí, eliminar de la tabla Empleado
            $stmt_empleado = $conn->prepare("DELETE FROM Empleado WHERE ID_Usuario = ?");
            $stmt_empleado->bind_param("i", $empleado_usuario_id);
            $stmt_empleado->execute();
            $stmt_empleado->close();
            // No verificamos affected_rows aquí porque un usuario podría no tener un registro en 'Empleado' aún.

            // 3. Eliminar de la tabla Usuario
            $stmt_usuario = $conn->prepare("DELETE FROM Usuario WHERE ID_Usuario = ?");
            $stmt_usuario->bind_param("i", $empleado_usuario_id);
            if ($stmt_usuario->execute()) {
                if ($stmt_usuario->affected_rows > 0) {
                    $conn->commit(); // Confirma la transacción si la eliminación de Usuario fue exitosa
                    $message = "Empleado eliminado exitosamente.";
                    $message_type = "success";
                } else {
                    $conn->rollback(); // Revierte si no se encontró el usuario
                    $message = "No se encontró el empleado para eliminar o no se pudo eliminar.";
                    $message_type = "warning";
                }
            } else {
                $conn->rollback(); // Revierte si hubo un error en la eliminación de Usuario
                throw new Exception("Error al ejecutar la eliminación del usuario: " . $stmt_usuario->error);
            }
            $stmt_usuario->close();

        } catch (Exception | mysqli_sql_exception $e) {
            $conn->rollback(); // Asegura un rollback en caso de cualquier excepción
            error_log("Error al eliminar empleado: " . $e->getMessage());
            $message = "Error al eliminar empleado: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
} else {
    $message = "ID de empleado no especificado.";
    $message_type = "danger";
}

// Cierra la conexión a la base de datos
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Redirige de vuelta a la página de gestión de empleados con el mensaje
header('Location: ' . $redirect_url . '?message=' . urlencode($message) . '&type=' . urlencode($message_type));
exit;
?>
