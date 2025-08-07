<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección de la página de administración
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php'); // Redirige si no está logueado o no es admin
    exit;
}

$user_data = null;
$message = '';
$message_type = '';
$roles = []; // Para almacenar los roles disponibles

// Cargar roles desde la base de datos (usando la columna 'Nombre' de la tabla Rol)
try {
    if (isset($conn) && $conn instanceof mysqli) {
        $roles_query = "SELECT ID_Rol, Nombre FROM Rol ORDER BY Nombre";
        $stmt_roles = $conn->prepare($roles_query);
        $stmt_roles->execute();
        $result_roles = $stmt_roles->get_result();
        while ($row_role = $result_roles->fetch_assoc()) {
            $roles[] = $row_role;
        }
        $stmt_roles->close();
    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception $e) {
    error_log("Error al cargar roles: " . $e->getMessage());
    $message = "Error al cargar roles: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}


// Obtener el ID del usuario de la URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id = intval($_GET['id']); // Sanitiza el ID

    // Cargar los datos del usuario existente
    $select_query = "SELECT ID_Usuario, Nombre, Correo, ID_Rol FROM Usuario WHERE ID_Usuario = ?";

    try {
        if (isset($conn) && $conn instanceof mysqli) {
            // Abre una nueva conexión si la anterior fue cerrada por la carga de roles
            if (!$conn->ping()) {
                include '../DB.php'; // Re-incluye para obtener una nueva conexión
            }

            $stmt = $conn->prepare($select_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user_data = $result->fetch_assoc();
            } else {
                $message = "Usuario no encontrado.";
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
        }
    } catch (Exception $e) {
        error_log("Error al cargar datos del usuario (ID: $user_id): " . $e->getMessage());
        $message = "Error al cargar datos del usuario: " . htmlspecialchars($e->getMessage());
        $message_type = "danger";
    }
} else {
    $message = "ID de usuario no proporcionado para editar.";
    $message_type = "danger";
}

// Procesar el formulario cuando se envía (actualizar)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_data) {
    // Recopilar y sanear los datos del formulario
    $id_usuario_edit = intval($_POST['id_usuario']); // Asegúrate de que el ID viene del formulario
    $nombre_usuario_input = htmlspecialchars(trim($_POST['nombre']));
    $email_input = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
    $new_password = $_POST['password'] ?? ''; // Nueva contraseña (opcional)
    $id_rol = intval($_POST['id_rol']);

    // Validación básica
    if (empty($nombre_usuario_input) || empty($email_input) || $id_rol <= 0) {
        $message = "Todos los campos obligatorios (excepto la contraseña si no la cambias) deben ser completados correctamente.";
        $message_type = "danger";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $message = "El formato del email no es válido.";
        $message_type = "danger";
    } elseif ($id_usuario_edit != $user_data['ID_Usuario']) {
        // Esto es una capa de seguridad para asegurar que no se manipula el ID
        $message = "Error de seguridad: ID de usuario no coincide.";
        $message_type = "danger";
    } else {
        $update_query = "UPDATE Usuario SET Nombre = ?, Correo = ?, ID_Rol = ? ";
        $bind_params_types = "ssi";
        $bind_params_values = [&$nombre_usuario_input, &$email_input, &$id_rol];

        if (!empty($new_password)) {
            if (strlen($new_password) < 6) { // Validación de longitud de contraseña
                $message = "La nueva contraseña debe tener al menos 6 caracteres.";
                $message_type = "danger";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query .= ", Contrasena = ? ";
                $bind_params_types .= "s";
                $bind_params_values[] = &$hashed_password;
            }
        }
        $update_query .= " WHERE ID_Usuario = ?";
        $bind_params_types .= "i";
        $bind_params_values[] = &$id_usuario_edit;

        // Si ya hay un mensaje de error por la contraseña, no intentar la actualización
        if (empty($message)) {
            try {
                if (isset($conn) && $conn instanceof mysqli) {
                    // Abre una nueva conexión si la anterior fue cerrada
                    if (!$conn->ping()) {
                        include '../DB.php'; // Re-incluye para obtener una nueva conexión
                    }

                    $stmt = $conn->prepare($update_query);
                    // Usar call_user_func_array para bind_param con un array de referencias
                    call_user_func_array([$stmt, 'bind_param'], array_merge([$bind_params_types], $bind_params_values));
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        $message = "Usuario actualizado exitosamente.";
                        $message_type = "success";
                        // Recargar los datos del usuario después de la actualización para mostrar los cambios
                        $user_data['Nombre'] = $nombre_usuario_input; // Actualiza el array user_data
                        $user_data['Correo'] = $email_input;     // Actualiza el array user_data
                        $user_data['ID_Rol'] = $id_rol;

                        // Redirigir a users.php con un mensaje de éxito
                        header('Location: users.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                        exit;

                    } else {
                        $message = "No se realizaron cambios en el usuario o no se pudo actualizar.";
                        $message_type = "info";
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
                }
            } catch (Exception $e) {
                error_log("Error al actualizar usuario (ID: $id_usuario_edit): " . $e->getMessage());
                $message = "Error al actualizar el usuario: " . htmlspecialchars($e->getMessage());
                $message_type = "danger";
            }
        }
    }
}

// Define el título de la página actual
$page_title = 'Editar Usuario';
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
    <style>
        body {font-family: 'Poppins', sans-serif;background:#f5f6fa;margin:0;}
        .admin-content {max-width: 900px;margin:40px auto;background:#fff;padding:35px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.08);}
        .admin-content h3 {font-size:1.8rem;margin-bottom:20px;text-align:center;color:#2c3e50;border-bottom:1px solid #e0e0e0;padding-bottom:10px;}
        .admin-form {display:flex;flex-direction:column;gap:18px;}
        .form-group label {font-weight:500;margin-bottom:8px;color:#34495e; display: block;}
        .form-group input, .form-group select {padding:12px;width:100%;font-size:1rem;border:1px solid #dcdcdc;border-radius:8px;background:#f9f9f9;box-sizing:border-box;}
        .form-group input:focus, .form-group select:focus {border-color:#3498db;box-shadow:0 0 8px rgba(52,152,219,0.2);background:#fff;outline:none;}
        .form-actions {display:flex;justify-content:flex-end;gap:12px;margin-top:20px;}
        .btn {display:inline-flex;align-items:center;gap:8px;padding:12px 20px;font-weight:600;border-radius:8px;text-decoration:none;transition:0.3s;cursor:pointer;border:none;}
        .btn-primary {background:#28a745;color:#fff;}
        .btn-primary:hover {background:#218838;transform: translateY(-2px);}
        .btn-secondary {background:#6c757d;color:#fff;}
        .btn-secondary:hover {background:#5a6268;transform: translateY(-2px);}
        .alert {padding:12px 18px;border-radius:6px;margin-bottom:20px;font-size:0.95rem;display: flex; align-items: center; gap: 10px;}
        .alert-success {background:#d4edda;color:#155724;border-left:5px solid #28a745;}
        .alert-danger {background:#f8d7da;color:#721c24;border-left:5px solid #dc3545;}
        
        /* Responsive adjustments */
        @media(max-width:768px){
            .admin-content{margin:20px;padding:20px;}
            .form-actions{flex-direction:column;gap:10px;}
            .btn{width:100%;justify-content:center;}
        }
    </style>

    <div class="admin-dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <nav class="sidebar-nav">
                <div class="menu-scroll">
                    <ul>
                        <li><a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="gestion_empleados.php" class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['gestion_empleados.php', 'add_empleado.php', 'edit_empleado.php'])) ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Gestionar Empleados</a></li>
                        <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                        <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                        <li><a href="inventarioAdmin.php" class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['inventarioAdmin.php', 'add_inventario.php', 'edit_inventario.php'])) ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                        <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
                        <li><a href="ReservaAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ReservaAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Gestionar Reservas</a></li>
                        <li><a href="InsEventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'InsEventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Gestionar Asistencia</a></li>
                        <li><a href="pedidos.php" class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['pedidos.php', 'edit_pedido.php'])) ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Gestionar Pedidos</a></li>
                        <li><a href="reporte_ventas.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reporte_ventas.php') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> Reporte de Ventas</a></li>
                        <li><a href="reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
                        <li><a href="reportAsis.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reportes Asistencia</a></li>
                        <li><a href="admin-chats.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-chats.php') ? 'active' : ''; ?>"><i class="fas fa-headset"></i> Soporte</a></li>  
                    </ul>
                </div>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </aside>

        <div class="main-panel">
            <header class="main-panel-header">
                <h2><?php echo $page_title; ?></h2>
                <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span><img src="../images/user-avatar.png" alt="User Avatar"></div>
            </header>

            <main class="content-area">
                <div class="admin-content">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($user_data): ?>
                        <h3>Información del Usuario</h3>
                        <form action="edit_user.php?id=<?php echo htmlspecialchars($user_data['ID_Usuario']); ?>" method="POST" class="admin-form">
                            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($user_data['ID_Usuario']); ?>">

                            <div class="form-group">
                                <label for="nombre">Nombre de Usuario:</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user_data['Nombre'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="correo">Email:</label>
                                <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($user_data['Correo'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                                <input type="password" id="password" name="password">
                                <small>Mínimo 6 caracteres si la cambias.</small>
                            </div>
                            <div class="form-group">
                                <label for="id_rol">Rol:</label>
                                <select id="id_rol" name="id_rol" required>
                                    <option value="">Selecciona un rol</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role['ID_Rol']); ?>"
                                            <?php echo (($user_data['ID_Rol'] ?? '') == $role['ID_Rol']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role['Nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                                <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <p>No se pudo cargar el usuario para editar. Por favor, asegúrese de que el ID es válido.</p>
                        <div class="form-actions" style="justify-content: center;">
                            <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista de usuarios</a>
                        </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

</body>
</html>
<?php
// Cierra la conexión a la base de datos al final del script
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>