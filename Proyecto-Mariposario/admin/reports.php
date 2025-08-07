<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Inicializar variables de mensaje
$message = '';
$message_type = '';

// **Protección de la página de administración:**
// 1. Verifica si el usuario ha iniciado sesión.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../logind.html');
    exit;
}

// 2. Verifica si el rol del usuario es administrador (ID_Rol = 1).
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

$page_title = 'Ver Reportes';

// --- Lógica para obtener datos de los reportes ---

$total_users = 0;
$total_products = 0;
$total_events = 0;
$users_by_role = [];
$low_stock_products = [];
$upcoming_events = [];
$products_by_category = [];

try {
    if (isset($conn) && $conn instanceof mysqli) {
        // Total de Usuarios
        $result = $conn->query("SELECT COUNT(*) AS count FROM Usuario");
        if ($result) {
            $total_users = $result->fetch_assoc()['count'];
        }

        // Total de Productos (activos en catálogo)
        $result = $conn->query("SELECT COUNT(*) AS count FROM Producto WHERE Activo_Catalogo = TRUE");
        if ($result) {
            $total_products = $result->fetch_assoc()['count'];
        }

        // Total de Eventos
        $result = $conn->query("SELECT COUNT(*) AS count FROM Evento");
        if ($result) {
            $total_events = $result->fetch_assoc()['count'];
        }

        // Usuarios por Rol
        $result = $conn->query("SELECT r.Nombre AS role_name, COUNT(u.ID_Usuario) AS user_count 
                                     FROM Usuario u 
                                     JOIN Rol r ON u.ID_Rol = r.ID_Rol 
                                     GROUP BY r.Nombre");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users_by_role[] = $row;
            }
        }

        // Productos con Bajo Stock (ej. menos de 10 unidades)
        // *** CORRECCIÓN CRÍTICA AQUÍ: Usa la tabla Inventario y SUMA el Stock_Actual ***
        $stmt_low_stock = $conn->prepare("
            SELECT 
                P.Nombre, 
                SUM(I.Stock_Actual) AS Total_Stock 
            FROM 
                Producto P
            JOIN 
                Inventario I ON P.ID_Producto = I.ID_Producto
            WHERE 
                I.Activo = TRUE -- Considera solo ítems de inventario activos
            GROUP BY 
                P.ID_Producto, P.Nombre
            HAVING 
                Total_Stock <= 10
            ORDER BY 
                Total_Stock ASC
        ");
        if ($stmt_low_stock) {
            $stmt_low_stock->execute();
            $result_low_stock = $stmt_low_stock->get_result();
            while ($row = $result_low_stock->fetch_assoc()) {
                $low_stock_products[] = $row;
            }
            $stmt_low_stock->close();
        } else {
            throw new Exception("Error al preparar la consulta de bajo stock: " . $conn->error);
        }

        // Productos por Categoría
        // Incluyendo solo productos que están activos en el catálogo y tienen inventario
        $stmt_products_by_category = $conn->prepare("
            SELECT 
                P.Categoria, 
                COUNT(DISTINCT P.ID_Producto) AS product_count 
            FROM 
                Producto P
            JOIN 
                Inventario I ON P.ID_Producto = I.ID_Producto
            WHERE 
                P.Activo_Catalogo = TRUE AND I.Activo = TRUE
            GROUP BY 
                P.Categoria
            ORDER BY
                P.Categoria ASC
        ");
        if ($stmt_products_by_category) {
            $stmt_products_by_category->execute();
            $result_products_by_category = $stmt_products_by_category->get_result();
            while ($row = $result_products_by_category->fetch_assoc()) {
                $products_by_category[] = $row;
            }
            $stmt_products_by_category->close();
        } else {
            throw new Exception("Error al preparar la consulta de productos por categoría: " . $conn->error);
        }

        // Próximos Eventos (top 5)
        // Asumiendo que la columna de fecha de evento se llama 'Fecha'
        $result = $conn->query("SELECT Nombre, Fecha, Hora, Ubicacion FROM Evento WHERE Fecha >= CURDATE() ORDER BY Fecha ASC LIMIT 5");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $upcoming_events[] = $row;
            }
        }

    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception $e) {
    error_log("Error al cargar reportes: " . $e->getMessage());
    $message = "Error al cargar los reportes: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// Lógica para mostrar mensajes si vienen de una redirección (poco probable en reports.php)
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
        /* Estilos específicos para reports.php */
        .report-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .report-card {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            min-height: 120px;
        }

        .report-card .icon {
            font-size: 2.5em;
            color: #00BCD4;
            margin-bottom: 10px;
        }
        .report-card h3 {
            margin: 0;
            font-size: 1.2em;
            color: #555;
            font-weight: 500;
        }
        .report-card .value {
            font-size: 2.5em;
            font-weight: 700;
            color: #333;
            margin-top: 5px;
        }

        /* Estilos para tablas de reportes (similares a data-table) */
        .report-section {
            margin-bottom: 40px;
        }
        .report-section h3 {
            font-size: 1.6em;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .report-section .table-container {
            /* Ya definidos en admin.css, pero lo dejo aquí para referencia si necesitas ajustes específicos */
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

                <h2>Resumen de Reportes</h2>
                <p>Una vista general de las estadísticas clave del sistema.</p>

                <div class="report-cards">
                    <div class="report-card">
                        <i class="fas fa-users icon"></i>
                        <h3>Total Usuarios</h3>
                        <span class="value"><?php echo htmlspecialchars($total_users); ?></span>
                    </div>
                    <div class="report-card">
                        <i class="fas fa-box icon"></i>
                        <h3>Total Productos</h3>
                        <span class="value"><?php echo htmlspecialchars($total_products); ?></span>
                    </div>
                    <div class="report-card">
                        <i class="fas fa-calendar-alt icon"></i>
                        <h3>Total Eventos</h3>
                        <span class="value"><?php echo htmlspecialchars($total_events); ?></span>
                    </div>
                </div> 

                <div class="report-section">
                    <h3>Usuarios por Rol</h3>
                    <?php if (!empty($users_by_role)): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Rol</th>
                                        <th>Cantidad de Usuarios</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users_by_role as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['role_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['user_count']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No se encontraron datos de roles de usuario.</p>
                    <?php endif; ?>
                </div>

                <div class="report-section">
                    <h3>Productos con Bajo Stock (<= 10 unidades)</h3>
                    <?php if (!empty($low_stock_products)): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nombre del Producto</th>
                                        <th>Stock Actual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['Nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($product['Total_Stock']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No hay productos con bajo stock actualmente.</p>
                    <?php endif; ?>
                </div>

                <div class="report-section">
                    <h3>Productos por Categoría</h3>
                    <?php if (!empty($products_by_category)): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Categoría</th>
                                        <th>Cantidad de Productos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products_by_category as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['Categoria']); ?></td>
                                            <td><?php echo htmlspecialchars($category['product_count']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No se encontraron datos de categorías de productos.</p>
                    <?php endif; ?>
                </div>
                
                <div class="report-section">
                    <h3>Próximos Eventos</h3>
                    <?php if (!empty($upcoming_events)): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nombre del Evento</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Ubicación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming_events as $event): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($event['Nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($event['Fecha']); ?></td>
                                            <td><?php echo htmlspecialchars($event['Hora']); ?></td>
                                            <td><?php echo htmlspecialchars($event['Ubicacion']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No hay próximos eventos programados.</p>
                    <?php endif; ?>
                </div>

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