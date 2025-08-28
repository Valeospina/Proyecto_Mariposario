<?php
session_start();
include '../DB.php'; // Archivo de conexión

// Protección: solo admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

$message = '';
$message_type = '';

// Manejo de mensajes de éxito/error pasados por URL (ej. desde add_inventario.php o edit_inventario.php)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Lógica para eliminación
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $item_id = intval($_GET['id']);
    try {
        if ($conn instanceof mysqli) {
            $stmt = $conn->prepare("DELETE FROM Inventario WHERE ID_Inventario = ?");
            if (!$stmt) {
                throw new Exception("Error al preparar la eliminación: " . $conn->error);
            }
            $stmt->bind_param("i", $item_id);
            if ($stmt->execute()) {
                $message = "Ítem de inventario eliminado exitosamente.";
                $message_type = "success";
            } else {
                throw new Exception("Error al eliminar el ítem: " . $stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception("Conexión a la base de datos no válida.");
        }
    } catch (Exception $e) {
        error_log("Error al eliminar ítem de inventario: " . $e->getMessage());
        $message = "Error al eliminar: " . htmlspecialchars($e->getMessage());
        $message_type = "danger";
    }
    // Redirigir para limpiar los parámetros GET de la URL
    header('Location: inventarioAdmin.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
    exit;
}

// Consulta para obtener todos los ítems del inventario
$inventario_items = [];
try {
    if ($conn instanceof mysqli) {
        // Unimos la tabla Inventario con la tabla Producto para obtener Nombre, Categoria y Precio
        $query = "SELECT 
                    i.ID_Inventario, 
                    i.SKU, 
                    i.Stock_Actual, 
                    i.Stock_Minimo, 
                    i.Ubicacion, 
                    i.Activo,
                    p.Nombre AS NombreProducto,  -- Alias para el nombre del producto del catálogo
                    p.Categoria AS TipoProducto, -- Alias para la categoría del producto (que actúa como tipo)
                    p.Precio AS PrecioProducto   -- Alias para el precio del producto
                  FROM 
                    Inventario i
                  JOIN 
                    Producto p ON i.ID_Producto = p.ID_Producto
                  ORDER BY 
                    TipoProducto, NombreProducto"; // Ordenar por la categoría/tipo y luego por el nombre del producto
        
        $result = $conn->query($query);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $inventario_items[] = $row;
            }
            $result->free();
        } else {
            throw new Exception("Error al obtener ítems de inventario: " . $conn->error);
        }
    } else {
        throw new Exception("Conexión a la base de datos no válida o no disponible.");
    }
} catch (Exception $e) {
    error_log("Error al cargar listado de inventario: " . $e->getMessage());
    $message = "Error al cargar el inventario: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

$page_title = 'Gestionar Inventario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $page_title; ?> - Panel de Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
   <style>
        .admin-content {
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .header-actions {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }

        .btn-main {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #28a745;
            color: #fff;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .btn-main:hover { background: #218838; }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        table th {
            background: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
        }

        table tbody tr:hover {
            background: #f4f4f4;
        }

        .description-cell {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        table img {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
        }

        .actions {
            
            gap: 8px;
            justify-content: center;
        }

        .btn-edit, .btn-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            color: #fff;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .btn-edit { background: #007bff; }
        .btn-edit:hover { background: #0056b3; }
        .btn-delete { background: #dc3545; }
        .btn-delete:hover { background: #c82333; }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 8px;
        }

        .pagination a {
            padding: 8px 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            color: #333;
            text-decoration: none;
        }

        .pagination a.active {
            background: #28a745;
            color: #fff;
            border-color: #28a745;
        }

        .pagination a:hover {
            background: #f1f1f1;
        }
    </style>

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
                    <h2>Listado de Inventario</h2>
                    <p>Aquí puedes ver y gestionar todos los ítems de tu inventario.</p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="button-group" style="margin-bottom: 20px;">
                        <a href="add_inventario.php" class="btn btn-add-product"><i class="fas fa-plus-circle"></i> Añadir Nuevo Ítem</a>
                    </div>

                    <?php if (empty($inventario_items)): ?>
                        <p>No hay ítems en el inventario todavía. ¡Añade uno para empezar!</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                
                                    <th>Nombre del Producto</th> <th>Tipo/Categoría</th> <th>SKU</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Precio de Venta</th> <th>Ubicación</th>
                                    <th>Activo (Inventario)</th> <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventario_items as $item): ?>
                                    <tr>
                                       
                                        <td data-label="Nombre del Producto"><?php echo htmlspecialchars($item['NombreProducto']); ?></td>
                                        <td data-label="Tipo/Categoría"><?php echo htmlspecialchars($item['TipoProducto']); ?></td>
                                        <td data-label="SKU"><?php echo htmlspecialchars($item['SKU']); ?></td>
                                        <td data-label="Stock Actual">
                                            <?php
                                            echo htmlspecialchars($item['Stock_Actual']);
                                            if ($item['Stock_Actual'] <= $item['Stock_Minimo']) {
                                                echo ' <span style="color: #E74C3C; font-weight: bold;">(Bajo)</span>';
                                            }
                                            ?>
                                        </td>
                                        <td data-label="Stock Mínimo"><?php echo htmlspecialchars($item['Stock_Minimo']); ?></td>
                                        <td data-label="Precio de Venta">$<?php echo htmlspecialchars(number_format($item['PrecioProducto'], 2)); ?></td>
                                        <td data-label="Ubicación"><?php echo htmlspecialchars($item['Ubicacion']); ?></td>
                                        <td data-label="Activo (Inventario)">
                                            <?php echo $item['Activo'] ? '<span style="color: var(--sidebar-active-bg);"><i class="fas fa-check-circle"></i> Sí</span>' : '<span style="color: #E74C3C;"><i class="fas fa-times-circle"></i> No</span>'; ?>
                                        </td>
                                            <td class="actions">
                                                <a href="edit_inventario.php?id=<?php echo htmlspecialchars($item['ID_Inventario']); ?>" 
                                                class="btn-edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="inventarioAdmin.php?action=delete&id=<?php echo htmlspecialchars($item['ID_Inventario']); ?>" 
                                                class="btn-delete" title="Eliminar" 
                                                onclick="return confirm('¿Estás seguro de que quieres eliminar este ítem del inventario?');">
                                                <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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