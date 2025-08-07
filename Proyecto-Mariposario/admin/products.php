<?php
session_start();
include '../DB.php';

$message = '';
$message_type = '';

// Verificación de sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: ../logind.php');
    exit;
}
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

// ELIMINAR PRODUCTO
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $product_id_to_delete = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    if ($product_id_to_delete) {
        try {
            $conn->begin_transaction();

            $stmt_delete_inventory = $conn->prepare("DELETE FROM Inventario WHERE ID_Producto = ?");
            $stmt_delete_inventory->bind_param("i", $product_id_to_delete);
            $stmt_delete_inventory->execute();
            $stmt_delete_inventory->close();

            $stmt_delete_product = $conn->prepare("DELETE FROM Producto WHERE ID_Producto = ?");
            $stmt_delete_product->bind_param("i", $product_id_to_delete);
            $stmt_delete_product->execute();

            if ($stmt_delete_product->affected_rows > 0) {
                $conn->commit();
                $message = "Producto y sus ítems de inventario asociados eliminados correctamente.";
                $message_type = "success";
            } else {
                $conn->rollback();
                $message = "No se encontró el producto o no se pudo eliminar.";
                $message_type = "warning";
            }
            $stmt_delete_product->close();
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error al eliminar el producto: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    } else {
        $message = "ID de producto inválido para eliminar.";
        $message_type = "danger";
    }
    header("Location: products.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit;
}

// PAGINACIÓN
$registrosPorPagina = 7;
$paginaActual = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($paginaActual - 1) * $registrosPorPagina;

$totalQuery = "SELECT COUNT(*) AS total FROM Producto";
$totalResult = $conn->query($totalQuery);
$totalRegistros = $totalResult->fetch_assoc()['total'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

$products_query = "SELECT ID_Producto, Nombre, Categoria, Descripcion, Precio, Imagen_URL, Activo_Catalogo
                   FROM Producto
                   ORDER BY ID_Producto DESC
                   LIMIT ? OFFSET ?";

$products = [];
try {
    $stmt = $conn->prepare($products_query);
    $stmt->bind_param("ii", $registrosPorPagina, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $message = "Error al cargar los productos: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

$page_title = 'Gestionar Productos';
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
        </header>

        <main class="content-area">
            <div class="admin-content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="header-actions">
                    <a href="add_product.php" class="btn-main"><i class="fas fa-plus"></i> Añadir Nuevo Producto</a>
                </div>

                <?php if (!empty($products)): ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Descripción</th>
                                    <th>Precio</th>
                                    <th>Imagen</th>
                                    <th>Activo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['Nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($product['Categoria']); ?></td>
                                        <td class="description-cell" title="<?php echo htmlspecialchars($product['Descripcion']); ?>">
                                            <?php echo htmlspecialchars($product['Descripcion']); ?>
                                        </td>
                                        <td>₡<?php echo htmlspecialchars(number_format($product['Precio'], 2)); ?></td>
                                        <td>
                                            <?php if (!empty($product['Imagen_URL'])): ?>
                                                <?php
                                                $image_src = $product['Imagen_URL'];
                                                if (!filter_var($image_src, FILTER_VALIDATE_URL)) {
                                                    $image_src = '../' . ltrim($image_src, '/');
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($image_src); ?>" alt="<?php echo htmlspecialchars($product['Nombre']); ?>">
                                            <?php else: ?>
                                                Sin imagen
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $product['Activo_Catalogo']
                                                ? '<i class="fas fa-check-circle" style="color:#28a745;"></i>'
                                                : '<i class="fas fa-times-circle" style="color:#dc3545;"></i>'; ?>
                                        </td>
                                        <td class="actions">
                                            <a href="edit_product.php?id=<?= $product['ID_Producto'] ?>" class="btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                            <a href="products.php?action=delete&id=<?= $product['ID_Producto'] ?>" class="btn-delete" title="Eliminar" onclick="return confirm('¿Seguro que deseas eliminar este producto?');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <?php if ($paginaActual > 1): ?>
                            <a href="?page=<?php echo $paginaActual - 1; ?>">Anterior</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $paginaActual) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($paginaActual < $totalPaginas): ?>
                            <a href="?page=<?php echo $paginaActual + 1; ?>">Siguiente</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p>No hay productos registrados.</p>
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
