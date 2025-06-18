<?php
session_start();
include '../DB.php'; // Archivo de conexión

// Protección: solo admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html');
    exit;
}

$message = '';
$message_type = '';
$event_id = $_GET['id'] ?? null; // Obtener el ID del evento de la URL

$event_data = [ // Inicializar con valores por defecto para evitar errores si no se carga el evento
    'ID_Evento' => $event_id,
    'Nombre' => '',
    'Descripcion' => '',
    'Precio' => '0.00',
    'Imagen_URL' => '',
    'Fecha' => '',         // Nuevo campo
    'Hora' => '',          // Nuevo campo
    'Ubicacion' => ''      // Nuevo campo
];

// --- Lógica para obtener datos del evento (cuando se carga la página) ---
if ($event_id && is_numeric($event_id)) {
    try {
        if (isset($conn) && $conn instanceof mysqli) {
            // Seleccionar *todos* los campos del evento, incluyendo los nuevos
            $stmt = $conn->prepare("SELECT ID_Evento, Nombre, Descripcion, Precio, Imagen_URL, Fecha, Hora, Ubicacion FROM Evento WHERE ID_Evento = ?");
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta SELECT: " . $conn->error);
            }
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $event_data = $result->fetch_assoc();
            } else {
                // Si el evento no se encuentra, redirigir con un mensaje de error
                header('Location: eventoAdmin.php?message=' . urlencode('Evento no encontrado.') . '&type=' . urlencode('danger'));
                exit;
            }
            $stmt->close();
        } else {
            throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
        }
    } catch (Exception $e) {
        error_log("Error al cargar datos del evento en edit_evento.php (GET): " . $e->getMessage());
        $message = 'Error al cargar los datos del evento: ' . htmlspecialchars($e->getMessage());
        $message_type = 'danger';
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Si no hay ID válido en GET, redirigir (solo si es una carga inicial GET)
    header('Location: eventoAdmin.php?message=' . urlencode('ID de evento no válido.') . '&type=' . urlencode('danger'));
    exit;
}


// --- Lógica para actualizar datos del evento (cuando se envía el formulario) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recopilar datos del formulario
    $event_id_post = $_POST['id'] ?? null; // El ID oculto del formulario
    $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $descripcion = htmlspecialchars(trim($_POST['descripcion'] ?? ''));
    $precio = floatval($_POST['precio'] ?? 0);
    $imagen_url = htmlspecialchars(trim($_POST['imagen_url'] ?? ''));
    $fecha = htmlspecialchars(trim($_POST['fecha'] ?? ''));        // Nuevo campo
    $hora = htmlspecialchars(trim($_POST['hora'] ?? ''));         // Nuevo campo
    $ubicacion = htmlspecialchars(trim($_POST['ubicacion'] ?? '')); // Nuevo campo

    // Validar el ID y los campos obligatorios
    if (!$event_id_post || !is_numeric($event_id_post)) {
        $message = 'ID de evento no válido para la actualización.';
        $message_type = 'danger';
    } elseif (empty($nombre) || empty($descripcion) || $precio <= 0 || empty($fecha) || empty($ubicacion)) {
        $message = 'Todos los campos obligatorios (Nombre, Descripción, Precio, Fecha, Ubicación) deben ser rellenados.';
        $message_type = 'danger';
    } else {
        // Si hora es vacío, lo convertimos a NULL para la base de datos
        $hora_for_db = empty($hora) ? NULL : $hora;

        try {
            // Preparar la consulta SQL para actualizar el evento
            // Incluimos los nuevos campos en el UPDATE
            $stmt = $conn->prepare("UPDATE Evento SET Nombre = ?, Descripcion = ?, Precio = ?, Imagen_URL = ?, Fecha = ?, Hora = ?, Ubicacion = ? WHERE ID_Evento = ?");
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta UPDATE: " . $conn->error);
            }

            // Vincular parámetros: s, s, d, s, s, s, s, i
            // nombre, descripcion, precio, imagen_url, fecha, hora, ubicacion, ID_Evento
            $stmt->bind_param("ssdssssi", $nombre, $descripcion, $precio, $imagen_url, $fecha, $hora_for_db, $ubicacion, $event_id_post);

            if ($stmt->execute()) {
                $message = 'Evento actualizado exitosamente.';
                $message_type = 'success';
                // Redirigir a la página de gestión de eventos con el mensaje
                header('Location: eventoAdmin.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                exit;
            } else {
                throw new Exception("Error al actualizar el evento: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error al actualizar evento en edit_evento.php (POST): " . $e->getMessage());
            $message = 'Error al actualizar el evento: ' . htmlspecialchars($e->getMessage());
            $message_type = 'danger';
            // Volver a cargar los datos del evento para que el formulario no se borre si hay un error
            // Esto es crucial para que el usuario no pierda lo que escribió al haber un error de DB
            $event_data = [
                'ID_Evento' => $event_id_post,
                'Nombre' => $nombre,
                'Descripcion' => $descripcion,
                'Precio' => $precio,
                'Imagen_URL' => $imagen_url,
                'Fecha' => $fecha,
                'Hora' => $hora,
                'Ubicacion' => $ubicacion
            ];
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
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
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
                    <h2><?php echo $page_title; ?></h2>
                    <p>Modifica los detalles del evento.</p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-container">
                        <form action="edit_evento.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($event_data['ID_Evento']); ?>">

                            <div class="form-group">
                                <label for="nombre">Nombre del Evento:</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($event_data['Nombre']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="descripcion">Descripción:</label>
                                <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($event_data['Descripcion']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="precio">Precio (USD):</label>
                                <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo htmlspecialchars($event_data['Precio']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="imagen_url">URL de la Imagen:</label>
                                <input type="url" id="imagen_url" name="imagen_url" value="<?php echo htmlspecialchars($event_data['Imagen_URL']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="fecha">Fecha del Evento:</label>
                                <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($event_data['Fecha'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="hora">Hora del Evento (Opcional):</label>
                                <input type="time" id="hora" name="hora" value="<?php echo htmlspecialchars($event_data['Hora'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="ubicacion">Ubicación del Evento:</label>
                                <input type="text" id="ubicacion" name="ubicacion" value="<?php echo htmlspecialchars($event_data['Ubicacion'] ?? ''); ?>" required>
                            </div>

                            <div class="button-group">
                                <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Guardar Cambios</button>
                                <a href="eventoAdmin.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
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
