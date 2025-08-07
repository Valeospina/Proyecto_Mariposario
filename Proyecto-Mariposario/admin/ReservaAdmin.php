<?php
session_start();
include '../DB.php'; // Asegúrate de que esta ruta sea correcta para tu conexión a la base de datos

// Define el título de la página actual al inicio para evitar warnings
$page_title = "Gestión de Reservas";

// Protección de la página de administración
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

// Inicializar variables de filtro
$filtro_fecha = $_GET['fecha'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';

// ******************************************************************
// PAGINACIÓN Y FILTROS
// ******************************************************************
$registrosPorPagina = 10; // Número de reservas por página
$paginaActual = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Consulta base para contar el total de registros con filtros
$sqlCount = "SELECT COUNT(*) AS total
             FROM Reserva r
             LEFT JOIN Evento e ON r.ID_Evento = e.ID_Evento
             LEFT JOIN Usuario u ON r.ID_Usuario = u.ID_Usuario
             WHERE 1=1";

$params = [];
$types = '';

if ($filtro_fecha) {
    $sqlCount .= " AND DATE(r.Fecha_Reserva) = ?";
    $types .= 's';
    $params[] = $filtro_fecha;
}
if ($filtro_usuario) {
    $sqlCount .= " AND u.Nombre LIKE ?";
    $types .= 's';
    $params[] = '%' . $filtro_usuario . '%';
}

// Preparar y ejecutar la consulta para contar el total de registros
$stmtCount = $conn->prepare($sqlCount);
if ($types) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$totalRegistros = $resultCount->fetch_assoc()['total'] ?? 0;
$stmtCount->close();

// Calcular el total de páginas
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// ******************************************************************
// CONSULTA PRINCIPAL CON FILTROS, LÍMITE Y OFFSET
// ******************************************************************
$sql = "SELECT r.*, e.Nombre AS Nombre_Evento, u.Nombre AS Nombre_Usuario
        FROM Reserva r
        LEFT JOIN Evento e ON r.ID_Evento = e.ID_Evento
        LEFT JOIN Usuario u ON r.ID_Usuario = u.ID_Usuario
        WHERE 1=1";

// Reiniciar parámetros y tipos para la consulta principal
$params = [];
$types = '';

if ($filtro_fecha) {
    $sql .= " AND DATE(r.Fecha_Reserva) = ?";
    $types .= 's';
    $params[] = $filtro_fecha;
}
if ($filtro_usuario) {
    $sql .= " AND u.Nombre LIKE ?";
    $types .= 's';
    $params[] = '%' . $filtro_usuario . '%';
}

$sql .= " ORDER BY r.Fecha_Reserva DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Añadir parámetros de límite y offset
$params[] = $registrosPorPagina;
$params[] = $offset;
$types .= 'ii'; // Dos enteros para LIMIT y OFFSET

// Vincular parámetros dinámicamente para la consulta principal
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$reservas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
        /* General Body and Layout */
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            margin: 0;
            color: #34495e;
            min-height: 100vh; /* Asegura que el cuerpo ocupe al menos toda la altura de la ventana */
            display: flex;
            flex-direction: column;
        }

        .main-panel {
            flex-grow: 1; /* Permite que el panel principal crezca y empuje el pie de página hacia abajo */
        }

        /* Área de Contenido del Administrador - Mayor Padding y Ancho Máximo */
        .admin-content {
            max-width: 1400px; /* Ancho máximo aumentado */
            margin: 40px auto;
            background: #fff;
            padding: 45px; /* Padding aumentado */
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .admin-content h2 {
            font-size: 2.2rem;
            margin-bottom: 15px;
            color: #2c3e50;
            text-align: left; /* Alinea el título a la izquierda */
        }

        .admin-content p {
            text-align: left;
            margin-bottom: 30px;
            color: #7f8c8d;
            font-size: 1rem;
        }

        /* Formulario de Filtro */
        .filter-form {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.06);
            align-items: center;
            justify-content: flex-start; /* Alinea los elementos a la izquierda */
            flex-wrap: wrap; /* Permite que los elementos se envuelvan en pantallas más pequeñas */
        }

        .filter-form input[type="date"],
        .filter-form input[type="text"] {
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
            flex-grow: 1; /* Permite que los inputs crezcan */
            min-width: 200px; /* Ancho mínimo aumentado para los inputs */
        }

        .filter-form input:focus {
            border-color: #8BC34A;
            box-shadow: 0 0 0 0.2rem rgba(139, 195, 74, 0.25);
            outline: none;
        }

        .filter-form .btn-filter { /* Clase específica para el botón de filtro */
            background-color: #007bff; /* Azul para el botón de filtro */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .filter-form .btn-filter:hover {
            background-color: #0056b3;
        }

        /* Tabla de Administración */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            overflow: hidden; /* Asegura las esquinas redondeadas en la tabla */
        }

        .admin-table th,
        .admin-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .admin-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .admin-table tbody tr:hover {
            background-color: #f2f7fc;
        }

        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Columna de Acciones */
        .admin-table .actions {
            white-space: nowrap; /* Mantiene los botones en una sola línea */
            text-align: center;
            display: flex; /* Usar flexbox para alinear los botones horizontalmente */
            gap: 5px; /* Espacio entre los botones */
            justify-content: center; /* Centrar los botones dentro de la celda */
        }

        .admin-table .actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
            min-width: 40px; /* Asegura un tamaño de botón consistente */
        }

        /* Estilos específicos para los botones de Aprobar y Cancelar */
        .admin-table .actions .btn-approve {
            background-color: #28a745; /* Verde para Aprobar */
            color: white;
        }

        .admin-table .actions .btn-approve:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .admin-table .actions .btn-cancel {
            background-color: #dc3545; /* Rojo para Cancelar */
            color: white;
        }

        .admin-table .actions .btn-cancel:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        /* Paginación */
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap; /* Permite que los enlaces de paginación se envuelvan */
        }

        .pagination a {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            text-decoration: none;
            color: #007bff;
            border-radius: 5px;
            transition: all 0.3s ease;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .pagination a.active {
            background-color: #28a745; /* Verde para la página activa */
            color: #fff;
            font-weight: bold;
            border-color: #28a745;
            box-shadow: 0 2px 5px rgba(139, 195, 74, 0.3);
        }

        .pagination a:hover:not(.active) {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #0056b3;
        }

        /* Mensaje de No Resultados */
        .admin-table tbody tr td[colspan="8"] { /* Ajustado colspan para 8 columnas ahora */
            text-align: center;
            color: #7f8c8d;
            padding: 20px;
        }

        /* Clases para el estado de la reserva */
        .text-warning { color: #ffc107; font-weight: 500; } /* Amarillo para Pendiente */
        .text-success { color: #28a745; font-weight: 500; } /* Verde para Confirmada */
        .text-danger { color: #dc3545; font-weight: 500; } /* Rojo para Cancelada */

        /* Ajustes Responsivos */
        @media (max-width: 768px) {
            .admin-content {
                margin: 20px;
                padding: 25px;
            }

            .admin-content h2 {
                text-align: left; /* Asegura la alineación a la izquierda también en pantallas pequeñas */
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-form input[type="date"],
            .filter-form input[type="text"],
            .filter-form .btn-filter {
                width: 100%;
                box-sizing: border-box; /* Incluye padding y borde en el ancho total del elemento */
            }

            .admin-table, .admin-table thead, .admin-table tbody, .admin-table th, .admin-table td, .admin-table tr {
                display: block; /* Hace que los elementos de la tabla se comporten como bloques */
            }

            .admin-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            .admin-table tr {
                margin-bottom: 15px;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }

            .admin-table td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }

            .admin-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: #555;
            }
            .admin-table .actions {
                text-align: center;
                padding-top: 15px;
                padding-bottom: 15px;
                border-top: 1px solid #e0e0e0;
                justify-content: center; /* Asegura que los botones se centren en móvil */
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
                    <h2>Gestionar Reservas</h2>
                    <p>Aquí puedes visualizar y gestionar las reservas de eventos.</p>

                    <form action="" method="GET" class="filter-form">
                        <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>" placeholder="Filtrar por Fecha">
                        <input type="text" name="usuario" value="<?php echo htmlspecialchars($filtro_usuario); ?>" placeholder="Filtrar por Usuario">
                        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="ReservaAdmin.php" class="btn-filter" style="background-color: #6c757d;"><i class="fas fa-redo"></i> Limpiar Filtros</a>
                    </form>

                    <?php if (!empty($reservas)): ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                           
                                    <th>Evento</th>
                                    <th>Usuario</th>
                                    <th>Fecha Reserva</th>
                                    <th>Cantidad Entradas</th>
                                    <th>Precio Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservas as $reserva): ?>
                                    <tr>
                                  
                                        <td data-label="Evento:"><?php echo htmlspecialchars($reserva['Nombre_Evento'] ?: 'N/A'); ?></td>
                                        <td data-label="Usuario:"><?php echo htmlspecialchars($reserva['Nombre_Usuario'] ?: 'N/A'); ?></td>
                                        <td data-label="Fecha Reserva:"><?php echo htmlspecialchars($reserva['Fecha_Reserva']); ?></td>
                                        <td data-label="Cantidad Entradas:"><?php echo htmlspecialchars($reserva['Cantidad_Entradas'] ?? 0); ?></td>
                                        <td data-label="Precio Total:">₡<?php echo number_format($reserva['Precio_Total'] ?? 0, 2); ?></td>
                                        <td data-label="Estado:">
                                            <?php
                                                $estado = htmlspecialchars($reserva['Estado']);
                                                $class = '';
                                                switch ($estado) {
                                                    case 'Pendiente': $class = 'text-warning'; break;
                                                    case 'Confirmada': $class = 'text-success'; break;
                                                    case 'Cancelada': $class = 'text-danger'; break;
                                                    default: $class = ''; break;
                                                }
                                                echo '<span class="' . $class . '">' . $estado . '</span>';
                                            ?>
                                        </td>
                                   <td class="actions">
                                        <a href="confirm_reserva.php?id=<?= $reserva['ID_Reserva']; ?>" class="btn btn-action-edit" title="Confirmar">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="cancel_reserva.php?id=<?= $reserva['ID_Reserva']; ?>" class="btn btn-action-delete" title="Cancelar" onclick="return confirm('¿Cancelar esta reserva?');">
                                            <i class="fas fa-times"></i>
                                        </a>

                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                     <?php if (empty($reservas)) echo "<tr><td colspan='9'>No hay resultados.</td></tr>"; ?>
                            </tbody>
                        </table>

                        <div class="pagination">
                            <?php if ($paginaActual > 1): ?>
                                <a href="?page=<?php echo $paginaActual - 1; ?>&fecha=<?php echo htmlspecialchars($filtro_fecha); ?>&usuario=<?php echo htmlspecialchars($filtro_usuario); ?>">Anterior</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&fecha=<?php echo htmlspecialchars($filtro_fecha); ?>&usuario=<?php echo htmlspecialchars($filtro_usuario); ?>" class="<?php echo ($i == $paginaActual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            <?php if ($paginaActual < $totalPaginas): ?>
                                <a href="?page=<?php echo $paginaActual + 1; ?>&fecha=<?php echo htmlspecialchars($filtro_fecha); ?>&usuario=<?php echo htmlspecialchars($filtro_usuario); ?>">Siguiente</a>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <p>No hay reservas registradas con los filtros actuales.</p>
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