<?php
session_start();
$page_title = "Reporte de Asistencia";
include '../DB.php';

// Traer todos los eventos para el select
$sql_eventos = "SELECT ID_Evento, Nombre FROM Evento";
$eventos     = $conn->query($sql_eventos)->fetch_all(MYSQLI_ASSOC);

$evento_id  = $_GET['evento_id'] ?? '';
$reservas   = [];
$total_res  = 0;
$total_ast  = 0;
$tasa_asist = 0;

// Parámetros de paginación
$registrosPorPagina = 10;
$paginaActual = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;
$totalPaginas = 0;

if ($evento_id) {
    // Obtener conteo total de reservas para este evento
    $sql_count = "SELECT COUNT(*) as total FROM Reserva WHERE ID_Evento = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param('i', $evento_id);
    $stmt_count->execute();
    $resultado_count = $stmt_count->get_result()->fetch_assoc();
    $total_res = intval($resultado_count['total']);
    $stmt_count->close();

    // Calcular total de páginas
    $totalPaginas = $total_res > 0 ? ceil($total_res / $registrosPorPagina) : 0;

    // Obtener reservas paginadas
    $sql  = "SELECT r.*, u.Nombre AS Nombre_Usuario 
             FROM Reserva r 
             LEFT JOIN Usuario u ON r.ID_Usuario = u.ID_Usuario
             WHERE r.ID_Evento = ?
             LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $evento_id, $registrosPorPagina, $offset);
    $stmt->execute();
    $reservas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Calcular asistentes confirmados
    if ($total_res > 0) {
        foreach ($reservas as $r) {
            if ($r['Asistio']) {
                $total_ast++;
            }
        }
        $tasa_asist = round($total_ast / $total_res * 100, 2);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($page_title); ?> - Panel de Administración</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../css/admin.css">
  <style>
    .report-summary, .report-compare {
      background: #fff;
      padding: 1rem;
      margin-bottom: 1.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .report-summary ul,
    .report-compare ul {
      list-style: none;
      padding: 0;
      display: flex;
      gap: 2rem;
    }
    .report-summary li, .report-compare li {
      flex: 1;
    }
    .report-summary h3,
    .report-compare h3 {
      margin-bottom: 0.75rem;
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
                        <li><a href="reportAsis.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reportAsis.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reportes Asistencia</a></li>
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
        <h2><?php echo htmlspecialchars($page_title); ?></h2>

        <form method="GET" class="filter-form">
          <select name="evento_id">
            <option value="">-- Selecciona un evento --</option>
            <?php foreach ($eventos as $e): ?>
              <option value="<?= $e['ID_Evento'] ?>"
                <?= ($evento_id == $e['ID_Evento']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($e['Nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-add-product">Generar Reporte</button>
        </form>

        <?php if ($evento_id): ?>
          <?php if ($total_res > 0): ?>

            <!-- 1) RESUMEN -->
            <div class="report-summary">
              <h3>1. Resumen de cifras</h3>
              <ul>
                <li><strong>Total de Reservas:</strong><br><?= $total_res; ?></li>
                <li><strong>Asistencias Confirmadas:</strong><br><?= $total_ast; ?></li>
                <li><strong>Tasa de Asistencia:</strong><br><?= $tasa_asist; ?>%</li>
              </ul>
            </div>

            <!-- 2) COMPARACIÓN -->
            <div class="report-compare">
              <h3>2. Comparación Reservas vs. Asistencias</h3>
              <ul>
                <li><strong>Reservas Totales:</strong><br><?= $total_res; ?></li>
                <li><strong>Reservas Asistidas:</strong><br><?= $total_ast; ?></li>
                <li><strong>Reservas No Asistidas:</strong><br><?= $total_res - $total_ast; ?></li>
              </ul>
            </div>

            <!-- 3) DETALLE DE RESERVAS -->
            <h3>3. Detalle de Reservas</h3>
            <table class="report-table">
              <thead>
                <tr>
                  <th>Usuario</th>
                  <th>Teléfono</th>
                  <th>Correo</th>
                  <th>Cant.</th>
                  <th>Estado</th>
                  <th>Asistió</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($reservas as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars($r['Nombre_Usuario'] ?? 'No registrado') ?></td>
                    <td><?= htmlspecialchars($r['Telefono']) ?></td>
                    <td><?= htmlspecialchars($r['Correo']) ?></td>
                    <td class="text-center"><?= intval($r['Cantidad_Personas']) ?></td>
                    <td><?= htmlspecialchars($r['Estado']) ?></td>
                    <td class="text-center">
                      <?= $r['Asistio']
                          ? '<span class="badge badge-success">Sí</span>'
                          : '<span class="badge badge-warning">No</span>' ?>
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

          <?php else: ?>
            <p>No se encontraron reservas para el evento seleccionado.</p>
          <?php endif; ?>
        <?php endif; ?>

      </div>
    </main>
  </div>
</div>
</body>
</html>


