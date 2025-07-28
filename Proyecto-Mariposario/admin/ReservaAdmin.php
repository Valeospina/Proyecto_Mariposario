<?php
$page_title = "Gestión de Reservas";
include '../DB.php';

// Obtener filtros
$filtro_fecha = $_GET['fecha'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';

// Query base
$sql = "SELECT r.*, e.Nombre AS Nombre_Evento, u.Nombre AS Nombre_Usuario 
        FROM Reserva r
        LEFT JOIN Evento e ON r.ID_Evento = e.ID_Evento
        LEFT JOIN Usuario u ON r.ID_Usuario = u.ID_Usuario
        WHERE 1=1";

// Aplicar filtros
if ($filtro_fecha) {
    $sql .= " AND DATE(r.Fecha_Reserva) = ?";
}
if ($filtro_usuario) {
    $sql .= " AND u.Nombre LIKE ?";
}

$stmt = $conn->prepare($sql);
$params = [];
$types = '';
if ($filtro_fecha) {
    $types .= 's';
    $params[] = $filtro_fecha;
}
if ($filtro_usuario) {
    $types .= 's';
    $params[] = '%' . $filtro_usuario . '%';
}
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$reservas = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
   <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Panel de Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css"> </head>
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
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="inventarioAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'inventarioAdmin.php' || basename($_SERVER['PHP_SELF']) == 'add_inventario.php' || basename($_SERVER['PHP_SELF']) == 'edit_inventario.php') ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>                
                    <li><a href="ReservaAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ReservaAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Reservas</a></li>
                    <li><a href="InsEventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'InsEventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Asistencia</a></li>
                    <li><a href="pedidos.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pedidos.php' || basename($_SERVER['PHP_SELF']) == 'edit_pedido.php') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Gestionar Pedidos</a></li>
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

                    <h2>Gestionar Reservas</h2>
                    <p>Reservas por usuario o fecha.</p>

        <form method="GET" class="filter-form" style="margin-bottom: 20px;">
            <input type="date" name="fecha" value="<?= htmlspecialchars($filtro_fecha) ?>" />
            <input type="text" name="usuario" placeholder="Nombre de usuario" value="<?= htmlspecialchars($filtro_usuario) ?>" />
            <button type="submit" class="btn btn-add-product">Filtrar</button>
        </form>

        <table class="admin-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Evento</th>
                    <th>Usuario</th>
                    <th>Personas</th>
                    <th>Fecha Reserva</th>
                    <th>Estado</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $res): ?>
                    <tr>
                        <td></td>
                        <td><?= $res['Nombre_Evento']; ?></td>
                        <td><?= $res['Nombre_Usuario'] ?? 'No registrado'; ?></td>
                        <td><?= $res['Cantidad_Personas']; ?></td>
                        <td><?= $res['Fecha_Reserva']; ?></td>
                        <td><?= $res['Estado']; ?></td>
                        <td><?= $res['Telefono']; ?></td>
                        <td><?= $res['Correo']; ?></td>
                        <td>
                            <a href="confirm_reserva.php?id=<?= $res['ID_Reserva']; ?>" class="btn btn-action-edit">Confirmar</a>
                            <a href="cancel_reserva.php?id=<?= $res['ID_Reserva']; ?>" class="btn btn-action-delete" onclick="return confirm('¿Cancelar esta reserva?')">Cancelar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reservas)) echo "<tr><td colspan='9'>No hay resultados.</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</main>

        </div>
    </div>
</body>
</html>

