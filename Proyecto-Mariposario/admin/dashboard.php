<?php
session_start();
include '../DB.php';

$message = '';
$message_type = '';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../logind.php');
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

try {
    $sqlProductos = "SELECT COUNT(*) AS total FROM Producto";
    $resultProductos = $conn->query($sqlProductos);
    $totalProductos = $resultProductos->fetch_assoc()['total'];

    $sqlUsuarios = "SELECT COUNT(*) AS total FROM Usuario WHERE ID_Rol = 2";
    $resultUsuarios = $conn->query($sqlUsuarios);
    $totalUsuarios = $resultUsuarios->fetch_assoc()['total'];

    $sqlPedidos = "SELECT COUNT(*) AS total FROM Pedido WHERE Estado_Pedido = 'Pendiente de Pago'";
    $resultPedidos = $conn->query($sqlPedidos);
    $totalPedidosPendientes = $resultPedidos->fetch_assoc()['total'];

    $conn->close();

} catch (Exception $e) {
    $totalProductos = 0;
    $totalUsuarios = 0;
    $totalPedidosPendientes = 0;
    $message = 'Error al obtener datos del dashboard: ' . $e->getMessage();
    $message_type = 'danger';
}

$page_title = 'Panel de Administración';
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
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-top: 30px;
        }
        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            padding: 25px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }
        .card h3 {
            font-size: 1.25rem;
            color: #2c3e50;
            margin-bottom: 12px;
        }
        .card-value {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .card-text {
            font-size: 0.95rem;
            color: #888;
        }
        .card-blue .card-value { color: #3498db; }
        .card-green .card-value { color: #27ae60; }
        .card-red .card-value { color: #e74c3c; }

        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
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
                        <li><a href="reportAsis.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reportAsis.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reportes Asistencia</a></li>
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
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Buscar...">
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

                    <h2>Bienvenido al Panel de Administración</h2>
                    <p>Aquí puedes ver un resumen de tu actividad y acceder a las diferentes secciones.</p>

                    <div class="dashboard-cards">
                        <div class="card card-blue">
                            <h3>Total Productos</h3>
                            <p class="card-value blue"><?php echo $totalProductos; ?></p>
                            <p class="card-text">Cantidad total de productos registrados.</p>
                        </div>
                        <div class="card card-green">
                            <h3>Usuarios Registrados</h3>
                            <p class="card-value green"><?php echo $totalUsuarios; ?></p>
                            <p class="card-text">Clientes que han creado una cuenta.</p>
                        </div>
                        <div class="card card-red">
                            <h3>Pedidos Pendientes</h3>
                            <p class="card-value red"><?php echo $totalPedidosPendientes; ?></p>
                            <p class="card-text">Pedidos en espera de procesamiento.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
