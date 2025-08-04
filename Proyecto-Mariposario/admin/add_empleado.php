<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Inicializar variables de mensaje
$message = '';
$message_type = '';

// Protección de la página de administración:
if (!isset($_SESSION['user_id'])) {
    header('Location: ../logind.php');
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

$page_title = 'Agregar Empleado';
$roles = [];

try {
    if (isset($conn) && $conn instanceof mysqli) {
        // Obtener roles disponibles para el select (excluyendo el rol 'Cliente')
        $result_roles = $conn->query("SELECT ID_Rol, Nombre FROM Rol WHERE Nombre != 'Cliente' ORDER BY Nombre ASC");
        if ($result_roles) {
            while ($row = $result_roles->fetch_assoc()) {
                $roles[] = $row;
            }
        } else {
            throw new Exception("Error al cargar roles: " . $conn->error);
        }

        // --- Lógica para procesar el formulario de añadir nuevo empleado ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $contrasena = $_POST['contrasena'] ?? '';
            $id_rol = $_POST['id_rol'] ?? '';
            $salario = $_POST['salario'] ?? 0.00;
            $horario = trim($_POST['horario'] ?? '');
            $fecha_contratacion = trim($_POST['fecha_contratacion'] ?? '');

            // Validaciones básicas
            if (empty($nombre) || empty($correo) || empty($contrasena) || empty($id_rol)) {
                $message = "Los campos Nombre, Correo, Contraseña y Rol son obligatorios.";
                $message_type = "danger";
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $message = "El formato del correo electrónico no es válido.";
                $message_type = "danger";
            } elseif (strlen($contrasena) < 6) {
                $message = "La contraseña debe tener al menos 6 caracteres.";
                $message_type = "danger";
            } else {
                // Iniciar transacción
                $conn->begin_transaction();
                try {
                    // Verificar si el correo ya existe
                    $check_stmt = $conn->prepare("SELECT ID_Usuario FROM Usuario WHERE Correo = ?");
                    $check_stmt->bind_param("s", $correo);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    if ($check_result->num_rows > 0) {
                        $message = "El correo electrónico ya está registrado.";
                        $message_type = "danger";
                        $conn->rollback(); // Revertir la transacción
                    } else {
                        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

                        // 1. Insertar en la tabla Usuario
                        $stmt_user = $conn->prepare("INSERT INTO Usuario (Nombre, Correo, Contrasena, ID_Rol) VALUES (?, ?, ?, ?)");
                        $stmt_user->bind_param("sssi", $nombre, $correo, $contrasena_hash, $id_rol);
                        if (!$stmt_user->execute()) {
                            throw new Exception("Error al insertar usuario: " . $stmt_user->error);
                        }
                        $nuevo_usuario_id = $stmt_user->insert_id; // Obtener el ID del usuario recién insertado
                        $stmt_user->close();

                        // 2. Insertar en la tabla Empleado
                        $fecha_contratacion_valid = null;
                        if (!empty($fecha_contratacion)) {
                            $date_obj = DateTime::createFromFormat('Y-m-d', $fecha_contratacion);
                            if ($date_obj && $date_obj->format('Y-m-d') === $fecha_contratacion) {
                                $fecha_contratacion_valid = $fecha_contratacion;
                            } else {
                                $message = "Formato de fecha de contratación no válido. Use AAAA-MM-DD.";
                                $message_type = "danger";
                                $conn->rollback();
                                goto end_of_post_logic_add; // Saltar al final de la lógica POST
                            }
                        }

                        $stmt_empleado = $conn->prepare("INSERT INTO Empleado (ID_Usuario, Salario, Horario, Fecha_Contratacion) VALUES (?, ?, ?, ?)");
                        $stmt_empleado->bind_param("idss", $nuevo_usuario_id, $salario, $horario, $fecha_contratacion_valid);
                        if (!$stmt_empleado->execute()) {
                            throw new Exception("Error al insertar en Empleado: " . $stmt_empleado->error);
                        }
                        $stmt_empleado->close();

                        $conn->commit(); // Confirmar la transacción
                        $message = "Empleado '" . htmlspecialchars($nombre) . "' agregado exitosamente.";
                        $message_type = "success";

                        // Limpiar campos del formulario después de una inserción exitosa si lo deseas
                        $_POST = array(); // Esto vaciaría el formulario
                    }
                } catch (Exception | mysqli_sql_exception $e) {
                    $conn->rollback();
                    error_log("Error en transacción al agregar empleado: " . $e->getMessage());
                    $message = "Error en transacción al agregar empleado: " . htmlspecialchars($e->getMessage());
                    $message_type = "danger";
                } finally {
                    $check_stmt->close();
                }
            }
        }
        end_of_post_logic_add: // Etiqueta para el goto
    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception | mysqli_sql_exception $e) {
    error_log("Error general al cargar/agregar empleado: " . $e->getMessage());
    $message = "Error general al cargar/agregar empleado: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
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
    <style>
        /* Estilos generales para formularios */
        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            max-width: 700px;
            margin: 30px auto;
        }

        .form-container h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group input[type="number"]:focus,
        .form-group input[type="date"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                            <div class="form-container">
                                <h3>Agregar Nuevo Empleado</h3>
                                <form action="add_empleado.php" method="POST">
                                    <div class="form-group">
                                        <label for="nombre">Nombre Completo:</label>
                                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="correo">Correo Electrónico:</label>
                                        <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="contrasena">Contraseña:</label>
                                        <input type="password" id="contrasena" name="contrasena" minlength="6" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="id_rol">Rol:</label>
                                        <select id="id_rol" name="id_rol" required>
                                            <option value="">Seleccione un Rol</option>
                                            <?php foreach ($roles as $rol): ?>
                                                <option value="<?php echo htmlspecialchars($rol['ID_Rol']); ?>"
                                                    <?php echo (isset($_POST['id_rol']) && $_POST['id_rol'] == $rol['ID_Rol']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($rol['Nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="salario">Salario (₡):</label>
                                        <input type="number" id="salario" name="salario" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['salario'] ?? '0.00'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="horario">Horario:</label>
                                        <input type="text" id="horario" name="horario" value="<?php echo htmlspecialchars($_POST['horario'] ?? ''); ?>" placeholder="Ej: L-V 8:00 AM - 5:00 PM">
                                    </div>
                                    <div class="form-group">
                                        <label for="fecha_contratacion">Fecha de Contratación:</label>
                                        <input type="date" id="fecha_contratacion" name="fecha_contratacion" value="<?php echo htmlspecialchars($_POST['fecha_contratacion'] ?? ''); ?>">
                                    </div>
                                    <div class="button-group">
                                        <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Guardar Empleado</button>
                                        <a href="gestion_empleados.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                                    </div>
                                </form>
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
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
