<?php
session_start();
include '../DB.php';

// Inicializar variables de mensaje para evitar warnings (buena práctica)
$message = '';
$message_type = '';

// Protección de la página de administración
if (!isset($_SESSION['user_id'])) {
    header('Location: ../logind.php');
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) { // Añadido isset para user_role
    header('Location: ../index.php'); // Redirige a una página de usuario si no es admin
    exit;
}

// ******************************************************************
// PAGINACIÓN PARA LISTADO DE EVENTOS
// ******************************************************************
$registrosPorPagina = 10; // Número de eventos por página
$paginaActual = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Contar total de eventos
$totalQuery = "SELECT COUNT(*) AS total FROM Evento";
$totalResult = $conn->query($totalQuery);
$totalRegistros = $totalResult->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Consulta para obtener los eventos con paginación
$eventos_query = "SELECT ID_Evento, Nombre, Descripcion, Precio, Imagen_URL, Activo FROM Evento ORDER BY ID_Evento DESC LIMIT ? OFFSET ?";
$eventos_result = null;
$eventos = [];

try {
    if (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare($eventos_query);
        $stmt->bind_param("ii", $registrosPorPagina, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $eventos[] = $row;
        }
        $stmt->close();
    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception $e) {
    error_log("Error al obtener eventos: " . $e->getMessage());
    // Muestra un mensaje de error legible al usuario
    $message = "Error al cargar los eventos: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// Lógica para mostrar mensajes si vienen de una redirección (ej. después de un ADD, EDIT, DELETE)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Define el título de la página actual
$page_title = 'Gestionar Eventos';

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
        /* Agrega o asegúrate de que estas reglas CSS estén en tu admin.css */
        .actions {
          
            gap: 8px; /* Espacio entre los botones */
            justify-content: flex-start; /* Alinea los botones al inicio del td */
            align-items: center;
        }

        .description-cell {
        max-width: 250px; /* Puedes ajustar este valor: 100px, 120px, etc. */
        white-space: nowrap; /* Evita que el texto salte de línea */
        overflow: hidden;    /* Oculta cualquier texto que se desborde */
        text-overflow: ellipsis; /* Añade "..." al final del texto desbordado */
        display: block; /* Necesario para que max-width funcione correctamente */
    }

    /* En la sección de Media Queries para móvil, también podrías ajustarlo si es necesario */
    @media (max-width: 768px) {
        td[data-label="Descripción:"] .description-cell {
            max-width: 100%; /* Asegura que en móvil tome el 100% del espacio disponible */
            white-space: normal; /* Permite que el texto salte de línea en móvil */
            overflow: visible;
            text-overflow: clip;
        }
    }

        .btn-edit, .btn-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px; /* Tamaño fijo para hacerlos cuadrados */
            height: 36px; /* Tamaño fijo para hacerlos cuadrados */
            border-radius: 6px;
            color: #fff;
            font-size: 1rem;
            transition: background 0.3s ease, transform 0.2s ease;
            text-decoration: none; /* Asegúrate de que no tengan subrayado */
        }

        .btn-edit {
            background: #007bff; /* Color azul para editar */
        }
        .btn-edit:hover {
            background: #0056b3; /* Tono más oscuro de azul al pasar el ratón */
            transform: translateY(-1px); /* Efecto de "levantar" */
        }

        .btn-delete {
            background: #dc3545; /* Color rojo para eliminar */
        }
        .btn-delete:hover {
            background: #c82333; /* Tono más oscuro de rojo al pasar el ratón */
            transform: translateY(-1px); /* Efecto de "levantar" */
        }

        /* Styles for pagination - from your original inventarioAdmin.php, ensure these are consistent */
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 8px;
        }
        .pagination a {
            padding: 8px 14px; /* Adjusted padding to match inventarioAdmin */
            border: 1px solid #ccc;
            text-decoration: none;
            color: #333;
            border-radius: 6px; /* Adjusted border-radius to match inventarioAdmin */
            transition: background 0.3s ease; /* Added transition */
        }
        .pagination a.active {
            background-color: #28a745; /* Changed to green from inventarioAdmin */
            color: #fff;
            font-weight: bold;
            border-color: #28a745; /* Changed border color */
        }
        .pagination a:hover {
            background-color: #f1f1f1; /* Adjusted hover color */
        }

        /* Additional styles you already had or might want to confirm are in admin.css */
        .admin-content {
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .btn-add-product { /* Assuming this is for your "Añadir Nuevo Evento" button */
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #28a745; /* Green color */
            color: #fff;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .btn-add-product:hover {
            background: #218838;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        table th {
            background: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
        }

        table tbody tr:hover {
            background: #f4f4f4;
        }

        /* Alert Messages (confirm these are in admin.css or global style) */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInFromTop 0.4s ease-out;
            font-size: 0.95rem;
        }

        @keyframes slideInFromTop {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert i {
            font-size: 1.2rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        /* For responsive tables on smaller screens */
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            tr {
                border: 1px solid #ccc;
                margin-bottom: 10px;
                border-radius: 8px;
                overflow: hidden;
            }

            td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }

            td:before {
                position: absolute;
                top: 0;
                left: 6px;
                width: 45%;
                padding-left: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                content: attr(data-label);
            }

            .actions {
                justify-content: flex-end; /* Align actions to the right on mobile */
                padding-top: 10px;
                padding-bottom: 10px;
                padding-right: 15px;
            }

            .btn-edit, .btn-delete {
                width: 40px;
                height: 40px;
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
                   <li><a href="gestionM.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'gestionM.php') ? 'active' : ''; ?>"><i class="fas fa-headset"></i> Gestion Mariposas</a></li>
                    </ul>
                </div>
                            <div class="sidebar-footer">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
            </nav>

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

                    <h2>Gestionar Eventos</h2>
                    <p>Aquí puedes ver, editar o eliminar eventos del sistema.</p>

                    <p style="margin-bottom: 25px;"><a href="add_evento.php" class="btn btn-add-product"><i class="fas fa-plus"></i> Añadir Nuevo Evento</a></p>

                    <?php if (!empty($eventos)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Imagen</th> <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Precio</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventos as $evento): ?>
                                    <tr>
                                        <td data-label="Imagen:">
                                            <?php
                                                $img_src = !empty($evento['Imagen_URL']) ? $evento['Imagen_URL'] : '../images/placeholder.png'; // Placeholder if no image
                                                if (!filter_var($img_src, FILTER_VALIDATE_URL) && $img_src !== '../images/placeholder.png') {
                                                    $img_src = '../' . ltrim($img_src, '/'); // Prepend ../ for local paths
                                                }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Imagen de evento" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                        </td>
                                        <td data-label="Nombre:"><?php echo htmlspecialchars($evento['Nombre']); ?></td>
                                        <td data-label="Descripción:">
                                            <span class="description-cell" title="<?php echo htmlspecialchars($evento['Descripcion']); ?>">
                                                <?php echo htmlspecialchars($evento['Descripcion']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Precio:">₡<?php echo number_format($evento['Precio'], 2); ?></td>
                                        <td class="actions">
                                            <a href="edit_evento.php?id=<?php echo htmlspecialchars($evento['ID_Evento']); ?>" class="btn-edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                     <?php if ($evento['Activo'] == 1): ?>
                                         <!-- ACTIVAR -->
                                        <a href="toggle_event.php?id=<?php echo $evento['ID_Evento']; ?>&state=1" 
                                        class="btn btn-success" title="Activar">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                    <?php else: ?>
                                          <!-- DESACTIVAR -->
                                        <a href="toggle_event.php?id=<?php echo $evento['ID_Evento']; ?>&state=0" 
                                        class="btn btn-danger" title="Desactivar">
                                            <i class="fas fa-eye-slash"></i>
                                        </a>
                                    <?php endif; ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="pagination">
                            <?php if ($paginaActual > 1): ?>
                                <a href="?page=<?php echo $paginaActual - 1; ?>">Anterior</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $paginaActual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            <?php if ($paginaActual < $totalPaginas): ?>
                                <a href="?page=<?php echo $paginaActual + 1; ?>">Siguiente</a>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <p>No hay eventos registrados.</p>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

</body>
</html>
<?php
// Cierra la conexión a la base de datos si está abierta y es MySQLi
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>