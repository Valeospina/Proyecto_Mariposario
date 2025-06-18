<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Inicializar variables de mensaje
$message = '';
$message_type = '';

// Protección de la página de administración:
// 1. Verifica si el usuario ha iniciado sesión.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

// 2. Verifica si el rol del usuario es administrador (ID_Rol = 1).
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php'); // Redirige si no es administrador
    exit;
}

$page_title = 'Gestionar Pedidos';

// --- Lógica para obtener datos de los pedidos ---
$pedidos = [];
try {
    if (isset($conn) && $conn instanceof mysqli) {
        // Consulta para obtener todos los pedidos con su último estado y nombre de usuario
        // Selecciona el último estado para cada pedido basado en la fecha más reciente
        $sql = "
            SELECT 
                p.ID_Pedido,
                u.Nombre AS Nombre_Usuario,
                p.Fecha_Pedido,
                ep.Estado AS Estado_Actual,
                MAX(ep.Fecha) AS Fecha_Ultimo_Estado
            FROM 
                Pedido p
            JOIN 
                Usuario u ON p.ID_Usuario = u.ID_Usuario
            LEFT JOIN 
                Estado_Pedido ep ON p.ID_Pedido = ep.ID_Pedido
            GROUP BY 
                p.ID_Pedido, u.Nombre, p.Fecha_Pedido, ep.Estado 
            ORDER BY 
                p.Fecha_Pedido DESC, Fecha_Ultimo_Estado DESC;
        ";


        $result = $conn->query($sql);

        if ($result) {

            while ($row = $result->fetch_assoc()) {
                $pedidos[] = $row;
            }
        } else {
            throw new Exception("Error al ejecutar la consulta de pedidos: " . $conn->error);
        }
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
            background-color: #007bff;
        }

        .action-buttons .btn-edit:hover {
            background-color: #0056b3;
        }

        .table-container {
            overflow-x: auto; /* Permite desplazamiento horizontal en tablas grandes */
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
                    <li><a href="gestion_empleados.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'gestion_empleados.php' || basename($_SERVER['PHP_SELF']) == 'add_empleado.php' || basename($_SERVER['PHP_SELF']) == 'edit_empleado.php') ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Gestionar Empleados</a></li>
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="inventarioAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'inventarioAdmin.php' || basename($_SERVER['PHP_SELF']) == 'add_inventario.php' || basename($_SERVER['PHP_SELF']) == 'edit_inventario.php') ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>                
                    <li><a href="pedidos.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pedidos.php' || basename($_SERVER['PHP_SELF']) == 'edit_pedido.php') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Gestionar Pedidos</a></li>
                    <li><a href="reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
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

                    <h3>Listado de Pedidos</h3>
                    <?php if (!empty($pedidos)): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID Pedido</th>
                                        <th>Usuario</th>
                                        <th>Fecha Pedido</th>
                                        <th>Estado Actual</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $pedido): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pedido['ID_Pedido']); ?></td>
                                            <td><?php echo htmlspecialchars($pedido['Nombre_Usuario']); ?></td>
                                            <td><?php echo htmlspecialchars($pedido['Fecha_Pedido']); ?></td>
                                            <td><?php echo htmlspecialchars($pedido['Estado_Actual'] ?? 'Pendiente'); ?></td>
                                            <td class="action-buttons">
                                                <a href="edit_pedido.php?id=<?php echo htmlspecialchars($pedido['ID_Pedido']); ?>" class="btn btn-edit">Editar Estado</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
