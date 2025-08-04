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

// Variables para mantener los valores del formulario en caso de error o después de limpiar
$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$precio = $_POST['precio'] ?? '';
$imagen_url = $_POST['imagen_url'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';
$ubicacion = $_POST['ubicacion'] ?? '';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recopilar y limpiar los datos del formulario, incluyendo los nuevos campos
    $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $descripcion = htmlspecialchars(trim($_POST['descripcion'] ?? ''));
    $precio = floatval($_POST['precio'] ?? 0);
    $imagen_url = htmlspecialchars(trim($_POST['imagen_url'] ?? ''));
    $fecha = htmlspecialchars(trim($_POST['fecha'] ?? ''));
    $hora = htmlspecialchars(trim($_POST['hora'] ?? ''));
    $ubicacion = htmlspecialchars(trim($_POST['ubicacion'] ?? ''));

    // Validar datos (ahora incluimos fecha y ubicacion como obligatorios)
    if (empty($nombre) || empty($descripcion) || $precio <= 0 || empty($fecha) || empty($ubicacion)) {
        $message = "Completa correctamente todos los campos obligatorios (Nombre, Descripción, Precio, Fecha, Ubicación).";
        $message_type = "danger";
    } else {
        $hora_for_db = empty($hora) ? NULL : $hora;

        $query = "INSERT INTO Evento (Nombre, Descripcion, Precio, Imagen_URL, Fecha, Hora, Ubicacion) VALUES (?, ?, ?, ?, ?, ?, ?)";
        try {
            if ($conn instanceof mysqli) {
                $stmt = $conn->prepare($query);
                if (!$stmt) {
                    throw new Exception("Error al preparar la consulta: " . $conn->error);
                }

                $stmt->bind_param("ssdssss", $nombre, $descripcion, $precio, $imagen_url, $fecha, $hora_for_db, $ubicacion);

                if ($stmt->execute()) {
                    $message = "Evento agregado exitosamente.";
                    $message_type = "success";
                    // Limpiar los valores de las variables para que el formulario aparezca vacío
                    $nombre = $descripcion = $precio = $imagen_url = $fecha = $hora = $ubicacion = '';
                } else {
                    throw new Exception("No se pudo agregar el evento: " . $stmt->error);
                }
                $stmt->close();
            } else {
                throw new Exception("Conexión a la base de datos no válida o no disponible.");
            }
        } catch (Exception $e) {
            error_log("Error al añadir evento: " . $e->getMessage());
            $message = "Error: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

// Define el título de la página actual
$page_title = 'Añadir Nuevo Evento';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $page_title; ?> - Panel de Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css" />

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

        .admin-content h3 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-align: center;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }

        /* ======= FORMULARIO ======= */
        .admin-form { /* Esta clase debe ir en el tag <form> */
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
        .form-group select,
        .form-group textarea { /* Añadido textarea aquí */
            padding: 12px 14px;
            font-size: 1rem;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            transition: 0.3s;
            background: #f9f9f9;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { /* Añadido textarea aquí */
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

        /* Checkbox (si lo necesitaras en el futuro, ya está aquí) */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }

        .checkbox-group input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* ======= BOTONES ======= */
        .form-actions { /* Esta clase debe ir en el div que contiene los botones */
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
        }

        .btn-primary {
            background: #28a745;
            color: #fff;
            border: none; /* Asegúrate de que los botones tengan border: none; */
        }

        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.2);
        }

        /* Cambiado btn-submit a btn-primary para consistencia */
        .btn-submit {
            background: #28a745;
            color: #fff;
            border: none;
        }

        .btn-submit:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.2);
        }


        .btn-secondary {
            background: #6c757d;
            color: #fff;
            border: none; /* Asegúrate de que los botones tengan border: none; */
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
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <h3>Añadir Nuevo Evento</h3>
                    <form action="add_evento.php" method="POST" class="admin-form">
                        <div class="form-group">
                            <label for="nombre">Nombre del Evento:</label>
                            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required />
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción:</label>
                            <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($descripcion); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="precio">Precio:</label>
                            <input type="number" id="precio" name="precio" min="0" step="0.01" value="<?php echo htmlspecialchars($precio); ?>" required />
                        </div>

                        <div class="form-group">
                            <label for="imagen_url">URL de la Imagen (opcional):</label>
                            <input type="text" id="imagen_url" name="imagen_url" value="<?php echo htmlspecialchars($imagen_url); ?>" />
                        </div>

                        <div class="form-group">
                            <label for="fecha">Fecha del Evento:</label>
                            <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>" required />
                        </div>

                        <div class="form-group">
                            <label for="hora">Hora del Evento (opcional):</label>
                            <input type="time" id="hora" name="hora" value="<?php echo htmlspecialchars($hora); ?>" />
                        </div>

                        <div class="form-group">
                            <label for="ubicacion">Ubicación del Evento:</label>
                            <input type="text" id="ubicacion" name="ubicacion" value="<?php echo htmlspecialchars($ubicacion); ?>" required />
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Añadir Evento</button>
                            <a href="eventoAdmin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                        </div>
                    </form>
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