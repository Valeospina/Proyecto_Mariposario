<?php
session_start();
include '../DB.php'; 

// Inicializar variables de mensaje para evitar warnings
$message = '';
$message_type = '';

// **Protección de la página de administración:**
// 1. Verifica si el usuario ha iniciado sesión.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../logind.php');
    exit;
}

// 2. Verifica si el rol del usuario es administrador (ID_Rol = 1).
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

// ******************************************************************
// LÓGICA PARA ELIMINAR PRODUCTOS Y SUS ÍTEMS DE INVENTARIO ASOCIADOS
// ******************************************************************
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $product_id_to_delete = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

    if ($product_id_to_delete) {
        try {
            // Iniciar una transacción para asegurar la integridad de los datos
            $conn->begin_transaction();

            // 1. Eliminar ítems de inventario asociados al producto
            // Esto es crucial para evitar errores de clave foránea si no tienes ON DELETE CASCADE
            $stmt_delete_inventory = $conn->prepare("DELETE FROM Inventario WHERE ID_Producto = ?");
            if ($stmt_delete_inventory === false) {
                throw new Exception("Error al preparar la eliminación de inventario: " . $conn->error);
            }
            $stmt_delete_inventory->bind_param("i", $product_id_to_delete);
            $stmt_delete_inventory->execute();
            $stmt_delete_inventory->close();

            // 2. Eliminar el producto
            $stmt_delete_product = $conn->prepare("DELETE FROM Producto WHERE ID_Producto = ?");
            if ($stmt_delete_product === false) {
                throw new Exception("Error al preparar la eliminación de producto: " . $conn->error);
            }
            $stmt_delete_product->bind_param("i", $product_id_to_delete);
            $stmt_delete_product->execute();

            if ($stmt_delete_product->affected_rows > 0) {
                $conn->commit(); // Confirmar los cambios si todo fue bien
                $message = "Producto y sus ítems de inventario asociados eliminados correctamente.";
                $message_type = "success";
            } else {
                $conn->rollback(); // Revertir si no se encontró el producto o no se pudo eliminar
                $message = "No se encontró el producto o no se pudo eliminar.";
                $message_type = "warning";
            }
            $stmt_delete_product->close();

        } catch (Exception $e) {
            $conn->rollback(); // Revertir en caso de cualquier error
            $message = "Error al eliminar el producto: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
            error_log("Error deleting product ID {$product_id_to_delete}: " . $e->getMessage());
        }
    } else {
        $message = "ID de producto inválido para eliminar.";
        $message_type = "danger";
    }
    // Redirigir para evitar reenvío del formulario/URL
    header("Location: products.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit;
}


// Lógica para obtener y mostrar productos
// Consulta la tabla 'Producto' con los nombres de columnas actualizados.
// Ya no seleccionamos 'Stock', sino 'Activo_Catalogo'.
$products_query = "SELECT ID_Producto, Nombre, Categoria, Descripcion, Precio, Imagen_URL, Activo_Catalogo
                   FROM Producto"; 

$products_result = null;
$products = []; 

try {
    if (isset($conn) && $conn instanceof mysqli) {
        $products_result = $conn->query($products_query);
        if ($products_result) {
            while ($row = $products_result->fetch_assoc()) {
                $products[] = $row;
            }
            $products_result->free(); 
        } else {
            throw new Exception("Error en la consulta SQL: " . $conn->error);
        }
    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception $e) {
    error_log("Error al obtener productos: " . $e->getMessage());
    // Muestra un mensaje de error legible al usuario
    $message = "Error al cargar los productos: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// Lógica para mostrar mensajes si vienen de una redirección (ej. después de un ADD, EDIT, DELETE)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Define el título de la página actual
$page_title = 'Gestionar Productos';

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
        :root {
        --vibrant-pink: #e74c3c;
        --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-medium: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        /* 0) Aseguramos que las celdas centren su contenido */
        table td, table th {
        vertical-align: middle;
        }

        /* 1) Contenedor flex para alinear en fila y espaciar */
        .action-links {
        display: flex;
        align-items: center;
        gap: 10px;
        }

        /* 2) Estilos base de todos los botones de acción */
        .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;                    /* espacio icono–texto */
        padding: 8px 12px;           /* tamaño que pedías */
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;           /* tamaño que pedías */
        cursor: pointer;
        transition: background-color .2s, transform .2s, box-shadow .2s;
        border: none;
        text-decoration: none;
        color: #fff;
        flex: none;
        line-height: 1;
        box-sizing: border-box;
        }

        /* 3) Colores y estados */
        .btn-action-edit {
        background-color: #00BCD4;
        }
        .btn-action-edit:hover {
        background-color: #00acc1;
        transform: translateY(-1px);
        box-shadow: var(--shadow-medium);
        }

        .btn-action-delete {
        background-color: var(--vibrant-pink);
        }
        .btn-action-delete:hover {
        background-color: #d96974;
        transform: translateY(-1px);
        box-shadow: var(--shadow-medium);
        }
        </style>

   
</head>
<body>

    <!-- El preloader ha sido eliminado de aquí -->

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
                    <li><a href="InsEventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'InsEventoAdmin') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Asistencia</a></li>
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

                    <h2>Gestionar Productos</h2>
                    <p>Aquí puedes ver, editar o eliminar productos (mariposas y orquídeas) del sistema.</p>

                    <p style="margin-bottom: 25px;"><a href="add_product.php" class="btn btn-add-product"><i class="fas fa-plus"></i> Añadir Nuevo Producto</a></p>

                    <?php if (!empty($products)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Descripción</th>
                                    <th>Precio</th>
                                    <th>Imagen</th> 
                                    <th>Activo en Catálogo</th> 
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td data-label="ID:"><?php echo htmlspecialchars($product['ID_Producto']); ?></td>
                                        <td data-label="Nombre:"><?php echo htmlspecialchars($product['Nombre']); ?></td>
                                        <td data-label="Categoría:"><?php echo htmlspecialchars($product['Categoria']); ?></td>
                                        <td data-label="Descripción:"><?php echo htmlspecialchars($product['Descripcion']); ?></td>
                                        <td data-label="Precio:">$<?php echo htmlspecialchars(number_format($product['Precio'], 2)); ?></td>
                                        <td data-label="Imagen:">
                                            <?php if (!empty($product['Imagen_URL'])): ?>
                                                <?php
                                                $image_src = htmlspecialchars($product['Imagen_URL']);
                                                // Comprobar si la URL de la imagen es una URL externa (http/https)
                                                // O si es una ruta local que empieza con 'uploads/productos/' (nuestra convención)
                                                if (!filter_var($image_src, FILTER_VALIDATE_URL) && strpos($image_src, 'uploads/productos/') === 0) {
                                                    // Si es una ruta local y estamos en admin/, añadir '../' para acceder correctamente
                                                    $image_src = '../' . $image_src;
                                                }
                                                ?>
                                                <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($product['Nombre']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                            <?php else: ?>
                                                Sin imagen
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Activo en Catálogo:">
                                            <?php echo $product['Activo_Catalogo'] ? '<span style="color: var(--sidebar-active-bg);"><i class="fas fa-check-circle"></i> Sí</span>' : '<span style="color: var(--danger-red);"><i class="fas fa-times-circle"></i> No</span>'; ?>
                                        </td>
                                        <td data-label="Acciones:" class="action-links">
                                            <a href="edit_product.php?id=<?php echo htmlspecialchars($product['ID_Producto']); ?>" class="btn btn-action-edit" title="Editar"><i class="fas fa-edit"></i> Editar</a>
                                            <a href="products.php?action=delete&id=<?php echo htmlspecialchars($product['ID_Producto']); ?>" class="btn btn-action-delete" title="Eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar este producto? Esto también eliminará todos los ítems de inventario asociados.');"><i class="fas fa-trash-alt"></i> Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
// Cierra la conexión a la base de datos si está abierta y es MySQLi
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
