<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección de la página de administración
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php'); // Redirige si no está logueado o no es admin
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
    <style>
        /* Variables de color de admin.css para consistencia */
        :root {
            --sidebar-bg: #2C3E50; 
            --sidebar-link-color: #ECF0F1;
            --sidebar-hover-bg: #34495E;
            --sidebar-active-bg: #1ABC9C; 
            --sidebar-active-color: #FFFFFF;

            --main-bg: #F0F2F5; 
            --card-bg: #FFFFFF;
            --header-top-bg: #FFFFFF;
            --border-color: #E0E0E0;

            --text-dark: #333333;
            --text-secondary: #7F8C8D;
            --accent-blue: #3498DB; 
            --danger-red: #E74C3C; 

            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        /* Estilos para el contenedor del formulario */
        .form-container {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-medium);
            max-width: 600px; /* Ancho máximo para formularios */
            margin: 0 auto; /* Centrar el formulario */
        }

        /* Estilos para grupos de formulario */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        /* Estilos específicos para input de texto, email y password */
        .form-group input[type="text"],
        .form-group input[type="email"], /* Nuevo estilo para email */
        .form-group input[type="password"], /* Nuevo estilo para password */
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box; /* Incluye padding y border en el ancho */
            font-size: 1rem;
            color: var(--text-dark);
            background-color: var(--main-bg); /* Fondo de input ligeramente gris */
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        /* Estilos de foco para todos los inputs y selects */
        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus, /* Estilo de foco para email */
        .form-group input[type="password"]:focus, /* Estilo de foco para password */
        .form-group input[type="number"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        /* Estilos para el grupo de botones al final del formulario */
        .button-group {
            display: flex;
            justify-content: space-between;
            gap: 15px; /* Espacio entre los botones */
            margin-top: 30px;
        }

        /* Estilo general para los botones */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            border: none;
            text-decoration: none;
        }

        /* Estilo para el botón de Guardar Cambios */
        .btn-submit {
            background-color: var(--sidebar-active-bg); /* Verde Turquesa */
            color: var(--sidebar-active-color);
            flex-grow: 1; /* Permite que el botón ocupe el espacio disponible */
            box-shadow: 0 4px 10px rgba(26, 188, 156, 0.2);
        }

        .btn-submit:hover {
            background-color: #16A085; /* Tono más oscuro */
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(26, 188, 156, 0.3);
        }

        /* Estilo para el botón Volver a la lista */
        .btn-secondary {
            background-color: var(--text-secondary); /* Gris */
            color: white;
            flex-grow: 1; /* Permite que el botón ocupe el espacio disponible */
            box-shadow: 0 4px 10px rgba(127, 140, 141, 0.2);
        }

        .btn-secondary:hover {
            background-color: #6C7A89; /* Gris más oscuro */
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(127, 140, 141, 0.3);
        }

        /* Iconos dentro de los botones */
        .btn .fas {
            margin-right: 8px;
        }

        /* Estilos de alerta (copiados de admin.css para consistencia) */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-light);
        }

        .alert-success { background-color: #D4EDDA; color: #155724; border: 1px solid #C3E6CB; }
        .alert-danger { background-color: #F8D7DA; color: #721C24; border: 1px solid #F5C6CB; }
        .alert-warning { background-color: #FFF3CD; color: #856404; border: 1px solid #FFEBAe; }
        .alert-info { background-color: #D1ECF1; color: #0C5460; border: 1px solid #BEE5EB; }

        /* Responsive adjustments for form */
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }
            .button-group {
                flex-direction: column; /* Apila los botones en pantallas pequeñas */
            }
            .btn-submit, .btn-secondary {
                width: 100%; /* Ocupa todo el ancho cuando están apilados */
                margin-bottom: 10px; /* Espacio entre botones apilados */
            }
            .btn-secondary {
                margin-bottom: 0; /* Elimina el margen inferior del último botón apilado */
            }
        }
    </style>
<body>

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
                        <li><a href="admin-chats.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-chats.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Soporte</a></li>  
                    </ul>
                </div>
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