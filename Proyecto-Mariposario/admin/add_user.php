<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección de la página de administración
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.php'); // Redirige si no está logueado o no es admin
    exit;
}

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


// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recopilar y sanear los datos del formulario
    // ATENCIÓN: Los nombres de los campos del formulario ahora son 'nombre' y 'correo'
    $nombre_usuario_input = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $email_input = filter_var(trim($_POST['correo'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? ''; // La contraseña no se sanea con htmlspecialchars antes de hashear
    $id_rol = intval($_POST['id_rol'] ?? 0);

    // Validación básica de datos
    if (empty($nombre_usuario_input) || empty($email_input) || empty($password) || $id_rol <= 0) {
        $message = "Todos los campos obligatorios deben ser completados correctamente.";
        $message_type = "danger";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $message = "El formato del email no es válido.";
        $message_type = "danger";
    } elseif (strlen($password) < 6) { // Ejemplo de validación de longitud de contraseña
        $message = "La contraseña debe tener al menos 6 caracteres.";
        $message_type = "danger";
    } else {
        // Hashear la contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ATENCIÓN: Nombres de columna adaptados a tu DB: 'Nombre', 'Correo', 'Contrasena'
        $insert_query = "INSERT INTO Usuario (Nombre, Correo, Contrasena, ID_Rol) VALUES (?, ?, ?, ?)";

        try {
            if (isset($conn) && $conn instanceof mysqli) {
                // Abre una nueva conexión si la anterior fue cerrada por la carga de roles
                if (!$conn->ping()) {
                    include '../DB.php'; // Re-incluye para obtener una nueva conexión
                }

                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("sssi", $nombre_usuario_input, $email_input, $hashed_password, $id_rol);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Usuario añadido exitosamente.";
                    $message_type = "success";
                    // Limpiar el formulario después de un éxito (opcional, pero útil para añadir múltiples)
                    // $_POST = array(); // Descomenta si quieres limpiar los campos después de añadir
                    
                    // Redirigir a users.php con un mensaje de éxito
                    header('Location: users.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                    exit;
                } else {
                    $message = "No se pudo añadir el usuario.";
                    $message_type = "warning";
                }
                $stmt->close();
            } else {
                throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
            }
        } catch (Exception $e) {
            error_log("Error al añadir usuario: " . $e->getMessage());
            $message = "Error al añadir el usuario: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

// Define el título de la página actual
$page_title = 'Añadir Nuevo Usuario';
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
                    <h2>Añadir Nuevo Usuario</h2>
                    <p>Completa el formulario para registrar un nuevo usuario.</p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-container">
                        <h3>Datos del Usuario</h3>
                        <form action="add_user.php" method="POST">
                            <div class="form-group">
                                <label for="nombre">Nombre de Usuario:</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="correo">Email:</label>
                                <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Contraseña:</label>
                                <input type="password" id="password" name="password" required>
                                <small>Mínimo 6 caracteres.</small>
                            </div>
                            <div class="form-group">
                                <label for="id_rol">Rol:</label>
                                <select id="id_rol" name="id_rol" required>
                                    <option value="">Selecciona un rol</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role['ID_Rol']); ?>"
                                            <?php echo (($_POST['id_rol'] ?? '') == $role['ID_Rol']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role['Nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="button-group">
                                <button type="submit" class="btn btn-submit"><i class="fas fa-user-plus"></i> Añadir Usuario</button>
                                <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                            </div>
                        </form>
                    </div>

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