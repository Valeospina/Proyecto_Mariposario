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

$page_title = 'Añadir Nuevo Empleado';
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

        // --- Lógica para procesar el formulario de añadir empleado ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $contrasena = $_POST['contrasena'] ?? '';
            $id_rol = $_POST['id_rol'] ?? '';
            $salario = $_POST['salario'] ?? 0.00;
            $horario = trim($_POST['horario'] ?? 'No especificado');
            $fecha_contratacion = trim($_POST['fecha_contratacion'] ?? '');

            // Validaciones básicas
            if (empty($nombre) || empty($correo) || empty($contrasena) || empty($id_rol)) {
                $message = "Todos los campos obligatorios deben ser completados (Nombre, Correo, Contraseña, Rol).";
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
                        // Hashear la contraseña
                        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

                        // 1. Insertar en la tabla Usuario
                        $stmt_user = $conn->prepare("INSERT INTO Usuario (Nombre, Correo, Contrasena, ID_Rol) VALUES (?, ?, ?, ?)");
                        $stmt_user->bind_param("sssi", $nombre, $correo, $contrasena_hash, $id_rol);
                        if (!$stmt_user->execute()) {
                            throw new Exception("Error al insertar usuario: " . $stmt_user->error);
                        }
                        $new_user_id = $stmt_user->insert_id; // Obtener el ID del nuevo usuario
                        $stmt_user->close();

                        // 2. Insertar en la tabla Empleado (si se ha agregado el rol de empleado o similar)
                        // Asegurarse de que la fecha de contratación es válida
                        $fecha_contratacion_valid = null;
                        if (!empty($fecha_contratacion)) {
                            $date_obj = DateTime::createFromFormat('Y-m-d', $fecha_contratacion);
                            if ($date_obj && $date_obj->format('Y-m-d') === $fecha_contratacion) {
                                $fecha_contratacion_valid = $fecha_contratacion;
                            } else {
                                $message = "Formato de fecha de contratación no válido. Use AAAA-MM-DD.";
                                $message_type = "danger";
                                $conn->rollback(); // Revertir si la fecha es inválida
                                goto end_of_post_logic; // Salir del bloque POST
                            }
                        }
                        
                        // Insertar en la tabla Empleado
                        $stmt_empleado = $conn->prepare("INSERT INTO Empleado (ID_Usuario, Salario, Horario, Fecha_Contratacion) VALUES (?, ?, ?, ?)");
                        $stmt_empleado->bind_param("idss", $new_user_id, $salario, $horario, $fecha_contratacion_valid);
                        
                        if (!$stmt_empleado->execute()) {
                            throw new Exception("Error al insertar en Empleado: " . $stmt_empleado->error);
                        }
                        $stmt_empleado->close();

                        $conn->commit(); // Confirmar la transacción
                        $message = "Empleado '" . htmlspecialchars($nombre) . "' añadido exitosamente.";
                        $message_type = "success";
                        header('Location: gestion_empleados.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                        exit;
                    }
                } catch (Exception $e) {
                    $conn->rollback(); // Revertir cualquier cambio si ocurre un error
                    error_log("Error en transacción al añadir empleado: " . $e->getMessage());
                    $message = "Error en transacción al añadir empleado: " . htmlspecialchars($e->getMessage());
                    $message_type = "danger";
                } finally {
                    $check_stmt->close();
                }
            }
        }
    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception $e) {
    error_log("Error general al añadir empleado: " . $e->getMessage());
    $message = "Error general al añadir empleado: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}
end_of_post_logic: // Etiqueta para el goto
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
        /* Estilos específicos para formularios */
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
            box-sizing: border-box; /* Incluye padding y borde en el ancho total */
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

        .form-actions {
            text-align: right;
            margin-top: 30px;
        }

        .form-actions button, .form-actions a {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
            transition: background-color 0.3s ease;
        }

        .form-actions .btn-submit {
            background-color: #28a745;
            color: white;
        }

        .form-actions .btn-submit:hover {
            background-color: #218838;
        }

        .form-actions .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .form-actions .btn-cancel:hover {
            background-color: #5a6268;
        }

        /* Estilos de alerta (si no están en admin.css) */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
            font-size: 1em;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
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
                <ul>
                    <li><a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="gestion_empleados.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'gestion_empleados.php' || basename($_SERVER['PHP_SELF']) == 'add_empleado.php' || basename($_SERVER['PHP_SELF']) == 'edit_empleado.php') ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Gestionar Empleados</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php' || basename($_SERVER['PHP_SELF']) == 'add_product.php' || basename($_SERVER['PHP_SELF']) == 'edit_product.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="inventarioAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'inventarioAdmin.php' || basename($_SERVER['PHP_SELF']) == 'add_inventario.php' || basename($_SERVER['PHP_SELF']) == 'edit_inventario.php') ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php' || basename($_SERVER['PHP_SELF']) == 'add_evento.php' || basename($_SERVER['PHP_SELF']) == 'edit_evento.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
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

                    <div class="form-container">
                        <h3>Añadir Nuevo Empleado</h3>
                        <form action="add_empleado.php" method="POST">
                            <div class="form-group">
                                <label for="nombre">Nombre Completo:</label>
                                <input type="text" id="nombre" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="correo">Correo Electrónico:</label>
                                <input type="email" id="correo" name="correo" required>
                            </div>
                            <div class="form-group">
                                <label for="contrasena">Contraseña:</label>
                                <input type="password" id="contrasena" name="contrasena" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label for="id_rol">Rol:</label>
                                <select id="id_rol" name="id_rol" required>
                                    <option value="">Seleccione un Rol</option>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?php echo htmlspecialchars($rol['ID_Rol']); ?>">
                                            <?php echo htmlspecialchars($rol['Nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="salario">Salario (₡):</label>
                                <input type="number" id="salario" name="salario" step="0.01" min="0" value="0.00">
                            </div>
                            <div class="form-group">
                                <label for="horario">Horario:</label>
                                <input type="text" id="horario" name="horario" placeholder="Ej: L-V 8:00 AM - 5:00 PM">
                            </div>
                            <div class="form-group">
                                <label for="fecha_contratacion">Fecha de Contratación:</label>
                                <input type="date" id="fecha_contratacion" name="fecha_contratacion">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Guardar Empleado</button>
                                <a href="gestion_empleados.php" class="btn-cancel"><i class="fas fa-times"></i> Cancelar</a>
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
