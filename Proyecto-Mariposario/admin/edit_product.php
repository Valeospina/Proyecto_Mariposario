<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección de la página de administración
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html'); // Redirige si no está logueado o no es admin
    exit;
}

$product = null;
$message = '';
$message_type = '';

// Obtener el ID del producto de la URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = $_GET['id'];

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
                // "ssdisisi" - string, string, double, integer, string, integer
                // Aquí usamos "ssdsisii" porque Fecha_Reposicion es un string (date), y Notificar_Disponibilidad e ID_Producto son enteros
                $stmt->bind_param("sssdissii", $nombre, $categoria, $descripcion, $precio, $stock, $imagen_url, $fecha_reposicion, $notificar_disponibilidad, $id_producto_edit);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Producto actualizado exitosamente.";
                    $message_type = "success";
                    // Recargar los datos del producto después de la actualización para mostrar los cambios
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin_styles.css">

</head>
<body>
    <header class="admin-header">
        <h1>Panel de Administración</h1>
        <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Rol: <?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Desconocido'); ?>)</p>
    </header>

    <nav class="admin-nav">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Gestionar Usuarios</a></li>
            <li><a href="products.php">Gestionar Productos</a></li>
            <li><a href="reports.php">Ver Reportes</a></li>
            <li><a href="../logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>

    <main class="admin-content">
        <h2>Editar Producto</h2>
        <p>Modifica los detalles del producto.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
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
            <div class="form-group">
                <input type="checkbox" id="notificar_disponibilidad" name="notificar_disponibilidad" <?php echo (($product['Notificar_Disponibilidad'] ?? 0) == 1) ? 'checked' : ''; ?>>
                <label for="notificar_disponibilidad">Notificar Disponibilidad</label>
            </div>
            <div class="button-group">
                <button type="submit" class="btn-submit">Guardar Cambios</button>
                <p><a href="products.php" class="btn-returnProducto">Volver a la lista de productos</a></p>
            </div>
        </form>
        <?php endif; ?>

    </main>

    <footer>
        <p style="text-align: center; margin-top: 30px; color: #ffffff;">&copy; <?php echo date("Y"); ?> Panel de Administración</p>
    </footer>
</body>
</html>
<?php
// Cierra la conexión a la base de datos si está abierta y es MySQLi
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>