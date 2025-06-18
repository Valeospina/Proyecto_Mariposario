<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección de la página de administración
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html'); // Redirige si no está logueado o no es admin
    exit;
}

$product = null;
$message = '';
$message_type = '';

// Obtener el ID del producto de la URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = intval($_GET['id']); // Asegúrate de sanitizar el ID

    // Cargar los datos del producto existente
    $select_query = "SELECT ID_Producto, Nombre, Categoria, Descripcion, Precio, Stock, Imagen_URL, Fecha_Reposicion, Notificar_Disponibilidad FROM Producto WHERE ID_Producto = ?";

    try {
        if (isset($conn) && $conn instanceof mysqli) {
            $stmt = $conn->prepare($select_query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
            } else {
                $message = "Producto no encontrado.";
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
        }
    } catch (Exception $e) {
        error_log("Error al cargar datos del producto (ID: $product_id): " . $e->getMessage());
        $message = "Error al cargar datos del producto: " . htmlspecialchars($e->getMessage());
        $message_type = "danger";
    }
} else {
    $message = "ID de producto no proporcionado para editar.";
    $message_type = "danger";
}

// Procesar el formulario cuando se envía (actualizar)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $product) {
    // Recopilar y sanear los datos del formulario
    $id_producto_edit = intval($_POST['id_producto']); // Asegúrate de que el ID viene del formulario
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $categoria = htmlspecialchars(trim($_POST['categoria']));
    $descripcion = htmlspecialchars(trim($_POST['descripcion']));
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $imagen_url = htmlspecialchars(trim($_POST['imagen_url']));
    $fecha_reposicion = !empty($_POST['fecha_reposicion']) ? $_POST['fecha_reposicion'] : null;
    $notificar_disponibilidad = isset($_POST['notificar_disponibilidad']) ? 1 : 0;

    // Validación básica
    if (empty($nombre) || empty($categoria) || empty($descripcion) || $precio <= 0 || $stock < 0) {
        $message = "Todos los campos obligatorios deben ser completados correctamente.";
        $message_type = "danger";
    } else if ($id_producto_edit != $product['ID_Producto']) {
        // Esto es una capa de seguridad para asegurar que no se manipula el ID
        $message = "Error de seguridad: ID de producto no coincide.";
        $message_type = "danger";
    }
    else {
        // Prepara la consulta para actualizar el producto
        $update_query = "UPDATE Producto SET Nombre = ?, Categoria = ?, Descripcion = ?, Precio = ?, Stock = ?, Imagen_URL = ?, Fecha_Reposicion = ?, Notificar_Disponibilidad = ? WHERE ID_Producto = ?";

        try {
            if (isset($conn) && $conn instanceof mysqli) {
                $stmt = $conn->prepare($update_query);
                // "ssdsisii" - string, string, double, integer, string, integer, integer
                // NOTA: Fecha_Reposicion es un string (date), y Notificar_Disponibilidad e ID_Producto son enteros
                $stmt->bind_param("sssdissii", $nombre, $categoria, $descripcion, $precio, $stock, $imagen_url, $fecha_reposicion, $notificar_disponibilidad, $id_producto_edit);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Producto actualizado exitosamente.";
                    $message_type = "success";
                    // Recargar los datos del producto después de la actualización para mostrar los cambios
                    // (Esto es redundante si se redirecciona, pero útil si se quedan en la misma página)
                    $product['Nombre'] = $nombre;
                    $product['Categoria'] = $categoria;
                    $product['Descripcion'] = $descripcion;
                    $product['Precio'] = $precio;
                    $product['Stock'] = $stock;
                    $product['Imagen_URL'] = $imagen_url;
                    $product['Fecha_Reposicion'] = $fecha_reposicion;
                    $product['Notificar_Disponibilidad'] = $notificar_disponibilidad;

                } else {
                    $message = "No se realizaron cambios en el producto o no se pudo actualizar.";
                    $message_type = "info"; // Cambio a info si no hay cambios
                }
                $stmt->close();
            } else {
                throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
            }
        } catch (Exception $e) {
            error_log("Error al actualizar producto (ID: $id_producto_edit): " . $e->getMessage());
            $message = "Error al actualizar el producto: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

// Define el título de la página actual
$page_title = 'Editar Producto';
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
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
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
                    <h2>Editar Producto</h2>
                    <p>Modifica los detalles del producto.</p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($product): ?>
                        <div class="form-container"> <h3>Información del Producto</h3>
                            <form action="edit_product.php?id=<?php echo htmlspecialchars($product['ID_Producto']); ?>" method="POST">
                                <input type="hidden" name="id_producto" value="<?php echo htmlspecialchars($product['ID_Producto']); ?>">

                                <div class="form-group">
                                    <label for="nombre">Nombre del Producto:</label>
                                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($product['Nombre'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="categoria">Categoría:</label>
                                    <select id="categoria" name="categoria" required>
                                        <option value="">Selecciona una categoría</option>
                                        <option value="Mariposas" <?php echo (($product['Categoria'] ?? '') == 'Mariposas') ? 'selected' : ''; ?>>Mariposas</option>
                                        <option value="Orquídeas" <?php echo (($product['Categoria'] ?? '') == 'Orquídeas') ? 'selected' : ''; ?>>Orquídeas</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="descripcion">Descripción:</label>
                                    <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($product['Descripcion'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="precio">Precio:</label>
                                    <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo htmlspecialchars($product['Precio'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="stock">Stock:</label>
                                    <input type="number" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($product['Stock'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="imagen_url">URL de la Imagen (opcional):</label>
                                    <input type="text" id="imagen_url" name="imagen_url" value="<?php echo htmlspecialchars($product['Imagen_URL'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="fecha_reposicion">Fecha de Reposición (opcional):</label>
                                    <input type="date" id="fecha_reposicion" name="fecha_reposicion" value="<?php echo htmlspecialchars($product['Fecha_Reposicion'] ?? ''); ?>">
                                </div>
                                <div class="form-group checkbox-group"> <input type="checkbox" id="notificar_disponibilidad" name="notificar_disponibilidad" value="1" <?php echo (($product['Notificar_Disponibilidad'] ?? 0) == 1) ? 'checked' : ''; ?>>
                                    <label for="notificar_disponibilidad">Notificar Disponibilidad</label>
                                </div>
                                <div class="button-group">
                                    <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Guardar Cambios</button>
                                    <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <p>No se pudo cargar el producto para editar. Por favor, asegúrese de que el ID es válido.</p>
                        <p><a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista de productos</a></p>
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