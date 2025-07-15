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

// Consulta principal
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

$params = [$desde, $hasta];
$types = 'ss';

if (!empty($producto)) {
    $sql .= " AND pp.ID_Producto = ?";
    $params[] = $producto;
    $types .= 'i';
}
if (!empty($usuario)) {
    $sql .= " AND p.ID_Usuario = ?";
    $params[] = $usuario;
    $types .= 'i';
}

$sql .= " GROUP BY pp.ID_Producto, pr.Nombre, u.Nombre, Fecha_Pedido ORDER BY Fecha_Pedido DESC";

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
                
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
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
            </div>
        </main>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
