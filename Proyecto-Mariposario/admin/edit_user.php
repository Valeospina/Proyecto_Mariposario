<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección de la página de administración
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html'); // Redirige si no está logueado o no es admin
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
    // ATENCIÓN: Nombres de columna adaptados a tu DB: 'Nombre' y 'Correo'
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
    // ATENCIÓN: Los nombres de los campos del formulario ahora son 'nombre' y 'correo'
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
        // ATENCIÓN: Nombres de columna adaptados a tu DB: 'Nombre', 'Correo'
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
                        $user_data['Correo'] = $email_input;       // Actualiza el array user_data
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
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'add_user.php' || basename($_SERVER['PHP_SELF']) == 'edit_user.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php' || basename($_SERVER['PHP_SELF']) == 'add_product.php' || basename($_SERVER['PHP_SELF']) == 'edit_product.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php' || basename($_SERVER['PHP_SELF']) == 'add_evento.php' || basename($_SERVER['PHP_SELF']) == 'edit_evento.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
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
                    <h2>Editar Usuario</h2>
                    <p>Modifica los detalles del usuario.</p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($user_data): ?>
                        <div class="form-container">
                            <h3>Información del Usuario</h3>
                            <form action="edit_user.php?id=<?php echo htmlspecialchars($user_data['ID_Usuario']); ?>" method="POST">
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
                                <div class="button-group">
                                    <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Guardar Cambios</button>
                                    <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <p>No se pudo cargar el usuario para editar. Por favor, asegúrese de que el ID es válido.</p>
                        <p><a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista de usuarios</a></p>
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