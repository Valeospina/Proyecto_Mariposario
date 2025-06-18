<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Inicializar variables de mensaje
$message = '';
$message_type = '';

// Protección de la página de administración:
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

$page_title = 'Editar Empleado';
$empleado_usuario_id = $_GET['id'] ?? null; // Este es el ID_Usuario del empleado

$empleado_data = null;
$roles = [];

if (!$empleado_usuario_id || !is_numeric($empleado_usuario_id)) {
    $message = "ID de empleado no válido.";
    $message_type = "danger";
    // Redirige a la lista de empleados si el ID no es válido
    header('Location: gestion_empleados.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
    exit;
}

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

        // --- Lógica para procesar el formulario de edición ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $contrasena = $_POST['contrasena'] ?? ''; // La contraseña puede estar vacía si no se cambia
            $id_rol = $_POST['id_rol'] ?? '';
            $salario = $_POST['salario'] ?? 0.00;
            $horario = trim($_POST['horario'] ?? '');
            $fecha_contratacion = trim($_POST['fecha_contratacion'] ?? '');

            // Validaciones básicas
            if (empty($nombre) || empty($correo) || empty($id_rol)) {
                $message = "Los campos Nombre, Correo y Rol son obligatorios.";
                $message_type = "danger";
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $message = "El formato del correo electrónico no es válido.";
                $message_type = "danger";
            } elseif (!empty($contrasena) && strlen($contrasena) < 6) {
                $message = "La contraseña debe tener al menos 6 caracteres si se va a cambiar.";
                $message_type = "danger";
            } else {
                // Iniciar transacción
                $conn->begin_transaction();
                try {
                    // Verificar si el correo ya existe para otro usuario (excepto el actual)
                    $check_stmt = $conn->prepare("SELECT ID_Usuario FROM Usuario WHERE Correo = ? AND ID_Usuario != ?");
                    $check_stmt->bind_param("si", $correo, $empleado_usuario_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    if ($check_result->num_rows > 0) {
                        $message = "El correo electrónico ya está registrado por otro usuario.";
                        $message_type = "danger";
                        $conn->rollback(); // Revertir la transacción
                    } else {
                        // 1. Actualizar la tabla Usuario
                        $sql_update_user = "UPDATE Usuario SET Nombre = ?, Correo = ?, ID_Rol = ?";
                        $params_types_user = "ssi";
                        $params_values_user = [&$nombre, &$correo, &$id_rol];

                        if (!empty($contrasena)) {
                            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
                            $sql_update_user .= ", Contrasena = ?";
                            $params_types_user .= "s";
                            $params_values_user[] = &$contrasena_hash;
                        }
                        $sql_update_user .= " WHERE ID_Usuario = ?";
                        $params_types_user .= "i";
                        $params_values_user[] = &$empleado_usuario_id;

                        $stmt_user = $conn->prepare($sql_update_user);
                        call_user_func_array([$stmt_user, 'bind_param'], array_merge([$params_types_user], $params_values_user));
                        if (!$stmt_user->execute()) {
                            throw new Exception("Error al actualizar usuario: " . $stmt_user->error);
                        }
                        $stmt_user->close();

                        // 2. Actualizar o insertar en la tabla Empleado
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

                        // Primero, intenta actualizar el registro en Empleado
                        // CORRECCIÓN: Cambiado "idssi" a "dssi" para que coincida con las 4 variables (Salario, Horario, Fecha_Contratacion, ID_Usuario en WHERE)
                        $stmt_empleado_update = $conn->prepare("UPDATE Empleado SET Salario = ?, Horario = ?, Fecha_Contratacion = ? WHERE ID_Usuario = ?");
                        $stmt_empleado_update->bind_param("dssi", $salario, $horario, $fecha_contratacion_valid, $empleado_usuario_id);
                        $stmt_empleado_update->execute();

                        if ($stmt_empleado_update->affected_rows === 0) {
                            // Si no se actualizó ninguna fila, significa que no existe, entonces lo insertamos
                            $stmt_empleado_insert = $conn->prepare("INSERT INTO Empleado (ID_Usuario, Salario, Horario, Fecha_Contratacion) VALUES (?, ?, ?, ?)");
                            $stmt_empleado_insert->bind_param("idss", $empleado_usuario_id, $salario, $horario, $fecha_contratacion_valid);
                            if (!$stmt_empleado_insert->execute()) {
                                throw new Exception("Error al insertar en Empleado: " . $stmt_empleado_insert->error);
                            }
                            $stmt_empleado_insert->close();
                        }
                        $stmt_empleado_update->close();

                        $conn->commit(); // Confirmar la transacción
                        $message = "Empleado '" . htmlspecialchars($nombre) . "' actualizado exitosamente.";
                        $message_type = "success";

                        // Si el usuario actual edita su propio rol, actualizar la sesión
                        if ($_SESSION['user_id'] == $empleado_usuario_id && $_SESSION['user_role'] != $id_rol) {
                            $_SESSION['user_role'] = $id_rol;
                            // También se podría actualizar $_SESSION['user_name'] si se cambió el nombre
                        }
                    }
                } catch (Exception | mysqli_sql_exception $e) { // Capturar también excepciones de MySQLi
                    $conn->rollback(); // Revertir cualquier cambio si ocurre un error
                    error_log("Error en transacción al editar empleado: " . $e->getMessage());
                    $message = "Error en transacción al editar empleado: " . htmlspecialchars($e->getMessage());
                    $message_type = "danger";
                } finally {
                    $check_stmt->close();
                }
            }
        }
        end_of_post_logic: // Etiqueta para el goto

        // --- Obtener datos del empleado (después de posible actualización) ---
        // Se unen Usuario y Empleado para obtener todos los campos relevantes
        $stmt_get_empleado = $conn->prepare("
            SELECT 
                u.ID_Usuario, 
                u.Nombre, 
                u.Correo, 
                u.ID_Rol, 
                e.Salario, 
                e.Horario, 
                e.Fecha_Contratacion 
            FROM 
                Usuario u 
            LEFT JOIN 
                Empleado e ON u.ID_Usuario = e.ID_Usuario 
            WHERE 
                u.ID_Usuario = ?
        ");
        $stmt_get_empleado->bind_param("i", $empleado_usuario_id);
        $stmt_get_empleado->execute();
        $result_get_empleado = $stmt_get_empleado->get_result();
        if ($result_get_empleado->num_rows > 0) {
            $empleado_data = $result_get_empleado->fetch_assoc();
        } else {
            $message = "Empleado no encontrado.";
            $message_type = "danger";
            // Redirige si el empleado no existe
            header('Location: gestion_empleados.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
            exit;
        }
        $stmt_get_empleado->close();

    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception | mysqli_sql_exception $e) { // Capturar también excepciones de MySQLi
    error_log("Error general al cargar/editar empleado: " . $e->getMessage());
    $message = "Error general al cargar/editar empleado: " . htmlspecialchars($e->getMessage());
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
            display: inline-flex; /* Usar flex para alinear icono y texto */
            align-items: center;
            justify-content: center;
            gap: 8px; /* Espacio entre icono y texto */
            margin-left: 10px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-actions .btn-submit {
            background-color: #28a745;
            color: white;
        }

        .form-actions .btn-submit:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .form-actions .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .form-actions .btn-cancel:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }

        /* Estilos de alerta */
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

                    <?php if ($empleado_data): ?>
                        <div class="form-container">
                            <h3>Editar Empleado: <?php echo htmlspecialchars($empleado_data['Nombre']); ?></h3>
                            <form action="edit_empleado.php?id=<?php echo htmlspecialchars($empleado_usuario_id); ?>" method="POST">
                                <div class="form-group">
                                    <label for="nombre">Nombre Completo:</label>
                                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($empleado_data['Nombre']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="correo">Correo Electrónico:</label>
                                    <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($empleado_data['Correo']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="contrasena">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                                    <input type="password" id="contrasena" name="contrasena" minlength="6">
                                </div>
                                <div class="form-group">
                                    <label for="id_rol">Rol:</label>
                                    <select id="id_rol" name="id_rol" required>
                                        <option value="">Seleccione un Rol</option>
                                        <?php foreach ($roles as $rol): ?>
                                            <option value="<?php echo htmlspecialchars($rol['ID_Rol']); ?>"
                                                <?php echo ($empleado_data['ID_Rol'] == $rol['ID_Rol']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($rol['Nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="salario">Salario (₡):</label>
                                    <input type="number" id="salario" name="salario" step="0.01" min="0" value="<?php echo htmlspecialchars($empleado_data['Salario'] ?? '0.00'); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="horario">Horario:</label>
                                    <input type="text" id="horario" name="horario" value="<?php echo htmlspecialchars($empleado_data['Horario'] ?? ''); ?>" placeholder="Ej: L-V 8:00 AM - 5:00 PM">
                                </div>
                                <div class="form-group">
                                    <label for="fecha_contratacion">Fecha de Contratación:</label>
                                    <input type="date" id="fecha_contratacion" name="fecha_contratacion" value="<?php echo htmlspecialchars($empleado_data['Fecha_Contratacion'] ?? ''); ?>">
                                </div>
                                <div class="button-group">
                                    <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Guardar Cambios</button>
                                    <a href="gestion_empleados.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <p>No se pudo cargar la información del empleado. Por favor, regrese al listado.</p>
                        <div class="form-actions" style="text-align: center;">
                            <a href="gestion_empleados.php" class="btn-cancel"><i class="fas fa-arrow-left"></i> Volver al Listado</a>
                        </div>
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
