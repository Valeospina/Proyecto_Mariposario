<?php
$page_title = "Inscripciones a Eventos";
include '../DB.php';

$sql_eventos = "SELECT ID_Evento, Nombre FROM Evento";
$eventos = $conn->query($sql_eventos)->fetch_all(MYSQLI_ASSOC);

$evento_id = $_GET['evento_id'] ?? '';
$inscritos = [];

if ($evento_id) {
    $sql = "SELECT r.*, u.Nombre AS Nombre_Usuario 
            FROM Reserva r 
            LEFT JOIN Usuario u ON r.ID_Usuario = u.ID_Usuario
            WHERE r.ID_Evento = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $evento_id);
    $stmt->execute();
    $inscritos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Panel de Administración</title>
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
                    <li><a href="gestion_empleados.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['gestion_empleados.php','add_empleado.php','edit_empleado.php']) ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Gestionar Empleados</a></li>
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="inventarioAdmin.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['inventarioAdmin.php','add_inventario.php','edit_inventario.php']) ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
                    <li><a href="ReservaAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ReservaAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Reservas</a></li>
                    <li><a href="InsEventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'InsEventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Asistencia</a></li>
                    <li><a href="pedidos.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['pedidos.php','edit_pedido.php']) ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Gestionar Pedidos</a></li>
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
                    <h2><?php echo htmlspecialchars($page_title); ?></h2>
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
                    <?php if (!empty($_GET['msg'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_GET['msg']) ?>
                        </div>
                    <?php endif; ?>

                    <h2>Gestionar Asistencia</h2>

                    <form method="GET" class="filter-form">
                        <select name="evento_id">
                            <option value="">-- Selecciona un evento --</option>
                            <?php foreach ($eventos as $evento): ?>
                                <option value="<?= $evento['ID_Evento'] ?>" <?= ($evento_id == $evento['ID_Evento']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($evento['Nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-add-product">Ver Inscritos</button>
                    </form>

                    <?php if (!empty($inscritos)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Teléfono</th>
                                    <th>Correo</th>
                                    <th>Cantidad</th>
                                    <th>Estado</th>
                                    <th>Asistió</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscritos as $ins): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ins['Nombre_Usuario'] ?? 'No registrado') ?></td>
                                        <td><?= htmlspecialchars($ins['Telefono']) ?></td>
                                        <td><?= htmlspecialchars($ins['Correo']) ?></td>
                                        <td><?= intval($ins['Cantidad_Personas']) ?></td>
                                        <td><?= htmlspecialchars($ins['Estado']) ?></td>
                                        <td>
                                            <?= !empty($ins['Asistio']) ? 'Sí' : 'No' ?>
                                        </td>
                                        <td>
                                            <a href="edit_inscripcion.php?id=<?= $ins['ID_Reserva'] ?>" class="btn btn-action-edit">Editar</a>
                                            <a href="cancel_reserva.php?id=<?= $ins['ID_Reserva'] ?>" class="btn btn-action-delete" onclick="return confirm('¿Cancelar participación?')">Cancelar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($evento_id): ?>
                        <p>No hay inscripciones para este evento.</p>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>
</body>
</html>