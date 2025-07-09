<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Inicializar variables de mensaje
$message = '';
$message_type = '';

// Protección de la página de administración:
// 1. Verifica si el usuario ha iniciado sesión.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../logind.php');
    exit;
}

// 2. Verifica si el rol del usuario es administrador (ID_Rol = 1).
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php'); // Redirige si no es administrador
    exit;
}

$page_title = 'Gestionar Empleados';

// --- Lógica para eliminar empleado ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $empleado_usuario_id = $_GET['id']; // Este es el ID_Usuario del empleado

    if ($empleado_usuario_id == $_SESSION['user_id']) {
        $message = "No puedes eliminar tu propia cuenta de administrador.";
        $message_type = "danger";
    } else {
        try {
            // Iniciar transacción para asegurar que ambos registros (Usuario y Empleado) se eliminen o ninguno
            $conn->begin_transaction();

            // 1. Eliminar de la tabla Empleado primero (si existe un registro allí)
            $stmt_empleado = $conn->prepare("DELETE FROM Empleado WHERE ID_Usuario = ?");
            $stmt_empleado->bind_param("i", $empleado_usuario_id);
            $stmt_empleado->execute();
            $stmt_empleado->close();
            // No verificamos affected_rows aquí porque un usuario podría no tener un registro en 'Empleado' aún.

            // 2. Eliminar de la tabla Usuario
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

        } catch (Exception $e) {
            $conn->rollback(); // Asegura un rollback en caso de cualquier excepción
            error_log("Error al eliminar empleado: " . $e->getMessage());
            $message = "Error al eliminar empleado: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

// --- Lógica para obtener datos de los empleados ---
$empleados = [];
try {
    if (isset($conn) && $conn instanceof mysqli) {
        // Unir Usuario con Empleado para obtener todos los datos necesarios
        // Filtramos para incluir roles que no sean 'Cliente'
        $sql = "SELECT 
                    u.ID_Usuario, 
                    u.Nombre, 
                    u.Correo, 
                    r.Nombre AS Rol_Nombre, 
                    e.Salario, 
                    e.Horario, 
                    e.Fecha_Contratacion 
                FROM 
                    Usuario u 
                JOIN 
                    Rol r ON u.ID_Rol = r.ID_Rol 
                LEFT JOIN 
                    Empleado e ON u.ID_Usuario = e.ID_Usuario 
                WHERE 
                    r.Nombre != 'Cliente' -- Excluir a los clientes del listado de empleados
                ORDER BY 
                    u.Nombre ASC";
        
        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $empleados[] = $row;
            }
        } else {
            throw new Exception("Error al ejecutar la consulta de empleados: " . $conn->error);
        }
    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception | mysqli_sql_exception $e) { // Capturar también excepciones de MySQLi
    error_log("Error al cargar empleados: " . $e->getMessage());
    $message = "Error al cargar los empleados: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// Lógica para mostrar mensajes si vienen de una redirección
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Panel de Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">

</head>
<body>

    <div class="admin-dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="gestion_empleados.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'gestion_empleados.php' || basename($_SERVER['PHP_SELF']) == 'add_empleado.php' || basename($_SERVER['PHP_SELF']) == 'edit_empleado.php') ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Gestionar Empleados</a></li>
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="inventarioAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'inventarioAdmin.php' || basename($_SERVER['PHP_SELF']) == 'add_inventario.php' || basename($_SERVER['PHP_SELF']) == 'edit_inventario.php') ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>                
                    <li><a href="ReservaAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ReservaAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Reservas</a></li>
                    <li><a href="InsEventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'InsEventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Asistencia</a></li>
                    <li><a href="pedidos.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pedidos.php' || basename($_SERVER['PHP_SELF']) == 'edit_pedido.php') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Gestionar Pedidos</a></li>
                    <li><a href="reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </aside>

        <div class="main-panel">
            <header class="main-panel-header">
                <div class="header-left">
                    <h2><?php echo $page_title; ?></h2>
                </div>
                <div class="header-right">
                    <div class="search-bar">
                        <input type="text" placeholder="Buscar...">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="user-profile">
                        <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                        <img src="../images/user-avatar.png" alt="User Avatar">
                    </div>
                </div>
            </header>

            <main class="content-area">
                <div class="admin-content">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="actions-bar">
                        <a href="add_empleado.php" class="btn btn-add-product"><i class="fas fa-user-plus"></i> Añadir Nuevo Empleado</a>
                    </div>

                    <?php if (!empty($empleados)): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID Usuario</th>
                                        <th>Nombre</th>
                                        <th>Correo</th>
                                        <th>Rol</th>
                                        <th>Salario</th>
                                        <th>Horario</th>
                                        <th>Fecha Contratación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($empleados as $empleado): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($empleado['ID_Usuario']); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['Nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['Correo']); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['Rol_Nombre']); ?></td>
                                            <td>₡<?php echo number_format($empleado['Salario'] ?? 0, 2, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['Horario'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['Fecha_Contratacion'] ?? 'N/A'); ?></td>
                                            <td class="action-buttons">
                                                 <a href="edit_empleado.php?id=<?php echo htmlspecialchars($empleado['ID_Usuario']); ?>" class="btn btn-action-edit" title="Editar"><i class="fas fa-edit"></i> Editar</a>
                                                 <a href="delete_empleado.php?id=<?php echo htmlspecialchars($empleado['ID_Usuario']); ?>" class="btn btn-action-delete" title="Eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar a este empleado? Esta acción es irreversible.');"><i class="fas fa-trash-alt"></i> Eliminar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No hay empleados registrados en el sistema.</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>