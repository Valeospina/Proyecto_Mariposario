<?php
session_start();
include '../DB.php';

// --- Protección: solo admin ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

// --- Inicialización ---
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$evento_item = null;
$message = '';
$message_type = '';

// Validar ID
if ($event_id <= 0) {
    header('Location: eventoAdmin.php?message=' . urlencode('ID de evento no válido.') . '&type=danger');
    exit;
}

// Obtener datos del evento
try {
    // Removed 'Activo' from SELECT query
    $stmt = $conn->prepare("SELECT ID_Evento, Nombre, Fecha, Hora, Ubicacion, Descripcion, Precio FROM Evento WHERE ID_Evento = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $evento_item = $result->fetch_assoc();
    $stmt->close();

    if (!$evento_item) {
        header('Location: eventoAdmin.php?message=' . urlencode('Evento no encontrado.') . '&type=danger');
        exit;
    }
} catch (Exception $e) {
    $message = "Error al cargar el evento: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// --- Procesar actualización ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $evento_item) {
    $nombre = trim($_POST['nombre'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');
    $hora = trim($_POST['hora'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT); // Use FLOAT for decimal
    // Removed $activo variable

    // Validations (no change needed here related to 'Activo')
    if (empty($nombre) || empty($fecha) || empty($hora) || empty($ubicacion) || $precio === false || $precio < 0) {
        $message = "Todos los campos obligatorios deben ser completados correctamente.";
        $message_type = "danger";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $fecha)) {
        $message = "El formato de la fecha debe ser AAAA-MM-DD.";
        $message_type = "danger";
    } elseif (!preg_match("/^\d{2}:\d{2}$/", $hora)) {
        $message = "El formato de la hora debe ser HH:MM.";
        $message_type = "danger";
    } else {
        try {
            // Removed 'Activo' from UPDATE query and bind_param
            $stmt = $conn->prepare("UPDATE Evento SET Nombre=?, Fecha=?, Hora=?, Ubicacion=?, Descripcion=?, Precio=? WHERE ID_Evento=?");
            $stmt->bind_param("sssssdi", $nombre, $fecha, $hora, $ubicacion, $descripcion, $precio, $event_id);

            if ($stmt->execute()) {
                $message = "Evento actualizado correctamente.";
                $message_type = "success";
                // Actualizar los datos del ítem en la memoria para que el formulario muestre los cambios
                $evento_item['Nombre'] = $nombre;
                $evento_item['Fecha'] = $fecha;
                $evento_item['Hora'] = $hora;
                $evento_item['Ubicacion'] = $ubicacion;
                $evento_item['Descripcion'] = $descripcion;
                $evento_item['Precio'] = $precio;
                // Removed $evento_item['Activo'] update
            } else {
                if ($conn->errno == 1062) {
                    throw new Exception("Ya existe un evento con el mismo nombre y fecha.");
                } else {
                    throw new Exception("Error al actualizar el evento: " . $stmt->error);
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

$page_title = 'Editar Evento';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?> - Panel Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../css/admin.css">
<style>
    body {font-family: 'Poppins', sans-serif;background:#f5f6fa;margin:0;}
    .admin-content {max-width: 900px;margin:40px auto;background:#fff;padding:35px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.08);}
    .admin-content h3 {font-size:1.8rem;margin-bottom:20px;text-align:center;color:#2c3e50;border-bottom:1px solid #e0e0e0;padding-bottom:10px;}
    .admin-form {display:flex;flex-direction:column;gap:18px;}
    .form-group label {font-weight:500;margin-bottom:8px;color:#34495e;}
    .form-group input, .form-group textarea {padding:12px;font-size:1rem;border:1px solid #dcdcdc;border-radius:8px;background:#f9f9f9; width: -webkit-fill-available;}
    .form-group input:focus, .form-group textarea:focus {border-color:#3498db;box-shadow:0 0 8px rgba(52,152,219,0.2);background:#fff;outline:none;}
    .checkbox-group {display:flex;align-items:center;gap:10px;}
    .form-actions {display:flex;justify-content:flex-end;gap:12px;margin-top:20px;}
    .btn {display:inline-flex;align-items:center;gap:8px;padding:12px 20px;font-weight:600;border-radius:8px;text-decoration:none;transition:0.3s;cursor:pointer;}
    .btn-primary {background:#28a745;color:#fff;}
    .btn-primary:hover {background:#218838;}
    .btn-secondary {background:#6c757d;color:#fff;}
    .btn-secondary:hover {background:#5a6268;}
    .alert {padding:12px 18px;border-radius:6px;margin-bottom:20px;font-size:0.95rem;}
    .alert-success {background:#d4edda;color:#155724;border-left:5px solid #28a745;}
    .alert-danger {background:#f8d7da;color:#721c24;border-left:5px solid #dc3545;}
    @media(max-width:768px){.admin-content{margin:20px;padding:20px;}.form-actions{flex-direction:column;gap:10px;}.btn{width:100%;justify-content:center;}}
</style>
</head>
<body>
<div class="admin-dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>Admin Panel</h3></div>
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
        <div class="sidebar-footer"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></div>
    </aside>

    <div class="main-panel">
        <header class="main-panel-header">
            <h2><?php echo $page_title; ?></h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span><img src="../images/user-avatar.png" alt="User Avatar"></div>
        </header>

        <main class="content-area">
            <div class="admin-content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if ($evento_item): ?>
                    <h3>Editar Evento: <?php echo htmlspecialchars($evento_item['Nombre']); ?></h3>
                    <form method="POST" class="admin-form">
                        <div class="form-group">
                            <label for="nombre">Nombre del Evento:</label>
                            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($evento_item['Nombre']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="fecha">Fecha:</label>
                            <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($evento_item['Fecha']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="hora">Hora:</label>
                            <input type="time" id="hora" name="hora" value="<?php echo htmlspecialchars(substr($evento_item['Hora'], 0, 5)); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="ubicacion">Ubicación:</label>
                            <input type="text" id="ubicacion" name="ubicacion" value="<?php echo htmlspecialchars($evento_item['Ubicacion']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción:</label>
                            <textarea id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($evento_item['Descripcion']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="precio">Precio:</label>
                            <input type="number" step="0.01" id="precio" name="precio" value="<?php echo htmlspecialchars($evento_item['Precio']); ?>" min="0" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                            <a href="eventoAdmin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
</body>
</html>
<?php if (isset($conn)) $conn->close(); ?>