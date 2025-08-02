<?php
session_start();
include '../DB.php'; // Archivo de conexión

// Protección: solo admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
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
    $fecha = htmlspecialchars(trim($_POST['fecha'] ?? ''));         // Nuevo campo
    $hora = htmlspecialchars(trim($_POST['hora'] ?? ''));          // Nuevo campo
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

        /* Estilos generales para el formulario y sus elementos */
        .admin-form {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-medium);
            max-width: 700px; /* Ajustado para un poco más de espacio */
            margin: 20px auto; /* Centrar el formulario con margen superior */
        }

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

        /* Estilos para todos los inputs de texto, número, email, password, textarea y select */
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select,
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="url"], /* Estilo para input de URL */
        .form-group input[type="date"], /* Estilo para input de fecha */
        .form-group input[type="time"] /* Estilo para input de hora */
        {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            color: var(--text-dark);
            background-color: var(--main-bg);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        /* Estilos de foco para todos los inputs y selects */
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group textarea:focus,
        .form-group select:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group input[type="url"]:focus, /* Estilo de foco para input de URL */
        .form-group input[type="date"]:focus, /* Estilo de foco para input de fecha */
        .form-group input[type="time"]:focus /* Estilo de foco para input de hora */
        {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        /* Estilos para el grupo de botones de acción del formulario */
        .button-group {
            display: flex;
            justify-content: flex-end; /* Alinea los botones a la derecha */
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

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

        /* Estilo para el botón Cancelar */
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

    <div class="admin-dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="gestion_empleados.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'gestion_empleados.php' || basename($_SERVER['PHP_SELF']) == 'add_empleado.php' || basename($_SERVER['PHP_SELF']) == 'edit_empleado.php') ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Gestionar Empleados</a></li>
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'add_user.php' || basename($_SERVER['PHP_SELF']) == 'edit_user.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="inventarioAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'inventarioAdmin.php' || basename($_SERVER['PHP_SELF']) == 'add_inventario.php' || basename($_SERVER['PHP_SELF']) == 'edit_inventario.php') ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php' || basename($_SERVER['PHP_SELF']) == 'add_evento.php' || basename($_SERVER['PHP_SELF']) == 'edit_evento.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
                    <li><a href="ReservaAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ReservaAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Reservas</a></li>
                    <li><a href="InsEventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'InsEventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Asistencia</a></li>
                    <li><a href="pedidos.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pedidos.php' || basename($_SERVER['PHP_SELF']) == 'edit_pedido.php') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Gestionar Pedidos</a></li>
                    <li><a href="reporte_ventas.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reporte_ventas.php') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> Reporte de Ventas</a></li>
                    <li><a href="reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
                    <li><a href="reportAsis.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reportes Asistencia</a></li>                    
                    <li><a href="admin-chats.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-chats.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Soporte</a></li>  
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
