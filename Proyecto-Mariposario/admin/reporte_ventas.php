<?php
session_start();
include '../DB.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

$desde = $_GET['desde'] ?? '2000-01-01';
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$producto = $_GET['producto'] ?? '';
$usuario = $_GET['usuario'] ?? '';

$registrosPorPagina = 10;
$paginaActual = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

// Consulta para contar registros
$sqlCount = "
SELECT COUNT(*) AS total
FROM (
    SELECT pp.ID_Producto
    FROM Pedido_Producto pp
    JOIN Pedido p ON p.ID_Pedido = pp.ID_Pedido
    WHERE p.Fecha_Pedido BETWEEN ? AND ?
";

$params = [$desde, $hasta];
$types = 'ss';

if (!empty($producto)) {
    $sqlCount .= " AND pp.ID_Producto = ?";
    $params[] = $producto;
    $types .= 'i';
}
if (!empty($usuario)) {
    $sqlCount .= " AND p.ID_Usuario = ?";
    $params[] = $usuario;
    $types .= 'i';
}

$sqlCount .= " GROUP BY pp.ID_Producto, p.ID_Usuario, DATE(p.Fecha_Pedido)
) AS sub";

$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$totalRegistros = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// Consulta principal con paginación
$sql = "
SELECT 
    pp.ID_Producto,
    pr.Nombre AS Nombre_Producto,
    u.Nombre AS Nombre_Usuario,
    SUM(pp.Cantidad) AS Total_Cantidad,
    SUM(pp.Cantidad * pp.Precio_Unitario) AS Total_Vendido,
    AVG(pp.Precio_Unitario) AS Precio_Promedio,
    DATE(p.Fecha_Pedido) AS Fecha_Pedido
FROM Pedido_Producto pp
JOIN Producto pr ON pr.ID_Producto = pp.ID_Producto
JOIN Pedido p ON p.ID_Pedido = pp.ID_Pedido
JOIN Usuario u ON p.ID_Usuario = u.ID_Usuario
WHERE p.Fecha_Pedido BETWEEN ? AND ?";

if (!empty($producto)) {
    $sql .= " AND pp.ID_Producto = ?";
}
if (!empty($usuario)) {
    $sql .= " AND p.ID_Usuario = ?";
}

$sql .= " GROUP BY pp.ID_Producto, pr.Nombre, u.Nombre, Fecha_Pedido ORDER BY Fecha_Pedido DESC LIMIT ? OFFSET ?";

$params[] = $registrosPorPagina;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Para filtros
$productos = $conn->query("SELECT ID_Producto, Nombre FROM Producto ORDER BY Nombre");
$usuarios = $conn->query("SELECT ID_Usuario, Nombre FROM Usuario ORDER BY Nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .pagination {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 5px;
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
                <h2>Reporte de Ventas</h2>
            </div>
        </header>

        <main class="content-area">
            <div class="admin-content">
                <h3>Filtro de Ventas</h3>
                <form method="GET" class="form-container">
                    <div class="form-group">
                        <label>Desde:</label>
                        <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
                    </div>
                    <div class="form-group">
                        <label>Hasta:</label>
                        <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
                    </div>
                    <div class="form-group">
                        <label>Producto:</label>
                        <select name="producto">
                            <option value="">Todos</option>
                            <?php while ($row = $productos->fetch_assoc()): ?>
                                <option value="<?= $row['ID_Producto'] ?>" <?= $producto == $row['ID_Producto'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['Nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Usuario:</label>
                        <select name="usuario">
                            <option value="">Todos</option>
                            <?php while ($row = $usuarios->fetch_assoc()): ?>
                                <option value="<?= $row['ID_Usuario'] ?>" <?= $usuario == $row['ID_Usuario'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['Nombre']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-submit"><i class="fas fa-filter"></i> Filtrar</button>
                </form>

                <h3>Tabla de Ventas</h3>

                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Usuario</th>
                            <th>Cantidad</th>
                            <th>Total Vendido (₡)</th>
                            <th>Precio Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Fecha_Pedido']) ?></td>
                            <td><?= htmlspecialchars($row['Nombre_Producto']) ?></td>
                            <td><?= htmlspecialchars($row['Nombre_Usuario']) ?></td>
                            <td><?= (int)$row['Total_Cantidad'] ?></td>
                            <td>₡<?= number_format($row['Total_Vendido'], 2) ?></td>
                            <td>₡<?= number_format($row['Precio_Promedio'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <div class="pagination">
                    <?php if ($paginaActual > 1): ?>
                        <a href="?page=<?= $paginaActual - 1 ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>&producto=<?= $producto ?>&usuario=<?= $usuario ?>">Anterior</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <a href="?page=<?= $i ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>&producto=<?= $producto ?>&usuario=<?= $usuario ?>" class="<?= $i == $paginaActual ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($paginaActual < $totalPaginas): ?>
                        <a href="?page=<?= $paginaActual + 1 ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>&producto=<?= $producto ?>&usuario=<?= $usuario ?>">Siguiente</a>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>

