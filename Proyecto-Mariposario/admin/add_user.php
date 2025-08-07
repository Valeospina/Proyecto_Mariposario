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
    <style>
        /* ======= ESTILOS GENERALES ======= */
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            margin: 0;
            color: #333;
        }

        .admin-content {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .admin-content h2 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-align: center;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }

        /* ======= FORMULARIO ======= */
        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #34495e;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            padding: 12px 14px;
            font-size: 1rem;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            transition: 0.3s;
            background: #f9f9f9;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.2);
            outline: none;
            background: #fff;
        }

        .form-group small {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 5px;
        }

        /* ======= BOTONES ======= */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: #28a745;
            color: #fff;
        }

        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.2);
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108,117,125,0.2);
        }

        .btn i {
            font-size: 1rem;
        }

        /* ======= ALERTAS ======= */
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 0.95rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 5px solid #ffc107;
        }

        /* ======= RESPONSIVE ======= */
        @media (max-width: 768px) {
            .admin-content {
                margin: 20px;
                padding: 20px;
            }
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
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
                    <h3>Añadir Nuevo Usuario</h3>
                    <p>Completa el formulario para registrar un nuevo usuario.</p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form action="add_user.php" method="POST" class="admin-form">
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
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Añadir Usuario</button>
                            <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                        </div>
                    </form>
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