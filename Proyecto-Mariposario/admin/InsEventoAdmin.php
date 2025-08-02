<?php
$page_title = "Inscripciones a Eventos";
include '../DB.php';

$sql_eventos = "SELECT ID_Evento, Nombre FROM Evento";
$eventos = $conn->query($sql_eventos)->fetch_all(MYSQLI_ASSOC);

$evento_id = $_GET['evento_id'] ?? '';
$inscritos = [];

// Parámetros de paginación
$registrosPorPagina = 10;
$paginaActual = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;
$totalPaginas = 0;

if ($evento_id) {
    try {
        // Contar total de registros
        $sqlCount = "SELECT COUNT(*) as total FROM Reserva WHERE ID_Evento = ?";
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->bind_param('i', $evento_id);
        $stmtCount->execute();
        $resultadoCount = $stmtCount->get_result();
        $totalRegistros = $resultadoCount->fetch_assoc()['total'] ?? 0;
        $stmtCount->close();

        $totalPaginas = ceil($totalRegistros / $registrosPorPagina);

        // Consulta principal con LIMIT y OFFSET
        $sql = "SELECT r.*, u.Nombre AS Nombre_Usuario 
                FROM Reserva r 
                LEFT JOIN Usuario u ON r.ID_Usuario = u.ID_Usuario
                WHERE r.ID_Evento = ?
                ORDER BY r.Fecha_Reserva DESC
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iii', $evento_id, $registrosPorPagina, $offset);
        $stmt->execute();
        $inscritos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error al obtener inscripciones: " . $e->getMessage());
    }
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
    <style>
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
            background-color: #8BC34A;
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
                    <li><a href="admin-chats.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-chats.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Soporte</a></li>  
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

                        <!-- PAGINACIÓN -->
                        <div class="pagination">
                            <?php if ($paginaActual > 1): ?>
                                <a href="?evento_id=<?= $evento_id ?>&page=<?= $paginaActual - 1 ?>">Anterior</a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <a href="?evento_id=<?= $evento_id ?>&page=<?= $i ?>" class="<?= ($i == $paginaActual) ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                            <?php if ($paginaActual < $totalPaginas): ?>
                                <a href="?evento_id=<?= $evento_id ?>&page=<?= $paginaActual + 1 ?>">Siguiente</a>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($evento_id): ?>
                        <p>No hay inscripciones para este evento.</p>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>
</body>
</html>
