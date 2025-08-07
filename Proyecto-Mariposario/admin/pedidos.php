<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Inicializar variables de mensaje
$message = '';
$message_type = '';
$pedidos = []; // Initialize the $pedidos array

// Protección de la página de administración:
// 1. Verifica si el usuario ha iniciado sesión.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../logind.php');
    exit;
}

// 2. Verifica si el rol del usuario es administrador (ID_Rol = 1).
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php'); // Redirige si no es administrador
    exit;
}

$page_title = 'Gestionar Pedidos';

// Parámetros de paginación
$registrosPorPagina = 10;
$paginaActual = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;
$totalPaginas = 0;

// --- Lógica para obtener datos de los pedidos ---
try {
    if ($conn instanceof mysqli) {
        $filtro_pago = $_GET['filtro_pago'] ?? '';

        // Contar total de registros
        $sqlCount = "
            SELECT COUNT(*) as total
            FROM Pedido p
            JOIN Usuario u ON p.ID_Usuario = u.ID_Usuario
            LEFT JOIN (
                SELECT ID_Pedido, Estado
                FROM Estado_Pedido
                WHERE (ID_Pedido, Fecha) IN (
                    SELECT ID_Pedido, MAX(Fecha)
                    FROM Estado_Pedido
                    GROUP BY ID_Pedido
                )
            ) ep ON ep.ID_Pedido = p.ID_Pedido
        ";

        if ($filtro_pago) {
            $sqlCount .= " WHERE p.Metodo_Pago = ?";
        }

        $stmtCount = $conn->prepare($sqlCount);
        if ($filtro_pago) {
            $stmtCount->bind_param("s", $filtro_pago);
        }
        $stmtCount->execute();
        $resultadoCount = $stmtCount->get_result();
        $totalRegistros = $resultadoCount->fetch_assoc()['total'] ?? 0;
        $stmtCount->close();

        $totalPaginas = ceil($totalRegistros / $registrosPorPagina);

        // Consulta principal con LIMIT y OFFSET
        $sql = "
            SELECT 
                p.ID_Pedido, 
                u.Nombre AS Nombre_Usuario, 
                p.Fecha_Pedido, 
                p.Metodo_Pago, 
                ep.Estado AS Estado_Actual,
                f.Ruta_PDF_Factura AS Ruta_Comprobante_Factura
            FROM Pedido p
            JOIN Usuario u ON p.ID_Usuario = u.ID_Usuario
            LEFT JOIN (
                SELECT ID_Pedido, Estado
                FROM Estado_Pedido
                WHERE (ID_Pedido, Fecha) IN (
                    SELECT ID_Pedido, MAX(Fecha)
                    FROM Estado_Pedido
                    GROUP BY ID_Pedido
                )
            ) ep ON ep.ID_Pedido = p.ID_Pedido
            LEFT JOIN Factura f ON f.ID_Pedido = p.ID_Pedido
        ";

        if ($filtro_pago) {
            $sql .= " WHERE p.Metodo_Pago = ?";
        }

        $sql .= " ORDER BY p.Fecha_Pedido DESC LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);

        if ($filtro_pago) {
            $stmt->bind_param("sii", $filtro_pago, $registrosPorPagina, $offset);
        } else {
            $stmt->bind_param("ii", $registrosPorPagina, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $pedidos[] = $row;
        }
        $stmt->close();
    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception $e) {
    error_log("Error al cargar pedidos: " . $e->getMessage());
    $message = "Error al cargar los pedidos: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// Lógica para mostrar mensajes si vienen de una redirección
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
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
        /* Estilos específicos para pedidos.php */
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: #f2f2f2;
            font-weight: 600;
            color: #333;
        }

        .data-table tr:hover {
            background-color: #f9f9f9;
        }

        .action-buttons .btn {
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-size: 0.9em;
            display: inline-block;
            margin-right: 5px;
        }

        .action-buttons .btn-edit {
            background-color: #00BCD4;
        }

        .action-buttons .btn-edit:hover {
            background-color: #00BCD4;
        }

        .table-container {
            overflow-x: auto; /* Permite desplazamiento horizontal en tablas grandes */
        }

        .mb-3 {
            margin-bottom: 1rem; /* Added this as it was in your form tag */
        }

        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 8px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ccc;
            text-decoration: none;
            color: #333;
            border-radius: 5px;
        }
        .pagination a.active {
            background-color: #28a745;
            color: #fff;
            font-weight: bold;
        }
        .pagination a:hover {
            background-color: #f0f0f0;
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

                    <h3>Listado de Pedidos</h3>

                    <form method="GET" class="mb-3">
                        <label for="filtro_pago">Filtrar por método de pago:</label>
                        <select name="filtro_pago" id="filtro_pago" onchange="this.form.submit()">
                            <option value="" <?= ($_GET['filtro_pago'] ?? '') == '' ? 'selected' : '' ?>>Todos</option>
                            <option value="Efectivo Tienda" <?= ($_GET['filtro_pago'] ?? '') == 'Efectivo Tienda' ? 'selected' : '' ?>>Efectivo Tienda</option>
                            <option value="SINPE Movil" <?= ($_GET['filtro_pago'] ?? '') == 'SINPE Movil' ? 'selected' : '' ?>>SINPE Movil</option>
                            <option value="PayPal" <?= ($_GET['filtro_pago'] ?? '') == 'PayPal' ? 'selected' : '' ?>>PayPal</option>
                        </select>
                    </form>

                    <?php if (!empty($pedidos)): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Usuario</th>
                                        <th>Fecha Pedido</th>
                                        <th>Método de Pago</th>
                                        <th>Estado Actual</th>
                                        <th>Comprobante</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $pedido): ?>
                                        <tr>
                                            <td></td>
                                            <td><?php echo htmlspecialchars($pedido['Nombre_Usuario']); ?></td>
                                            <td><?php echo htmlspecialchars($pedido['Fecha_Pedido']); ?></td>
                                            <td><?php echo htmlspecialchars($pedido['Metodo_Pago']); ?></td>
                                            <td><?php echo htmlspecialchars($pedido['Estado_Actual'] ?? 'Pendiente'); ?></td>
                                            <td>
                                                <?php
                                                if ($pedido['Metodo_Pago'] === 'SINPE Movil' && !empty($pedido['Ruta_Comprobante_Factura'])) {
                                                    echo "<a href='../" . htmlspecialchars($pedido['Ruta_Comprobante_Factura']) . "' target='_blank'>Ver Comprobante</a>";
                                                }
                                                else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td class="action-buttons">
                                                <a href="edit_pedido.php?id=<?php echo htmlspecialchars($pedido['ID_Pedido']); ?>" class="btn btn-edit">Editar Estado</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PAGINACIÓN -->
                        <div class="pagination">
                            <?php if ($paginaActual > 1): ?>
                                <a href="?filtro_pago=<?= urlencode($filtro_pago) ?>&page=<?= $paginaActual - 1 ?>">Anterior</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <a href="?filtro_pago=<?= urlencode($filtro_pago) ?>&page=<?= $i ?>" class="<?= ($i == $paginaActual) ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                            <?php if ($paginaActual < $totalPaginas): ?>
                                <a href="?filtro_pago=<?= urlencode($filtro_pago) ?>&page=<?= $paginaActual + 1 ?>">Siguiente</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p>No hay pedidos registrados en el sistema.</p>
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
