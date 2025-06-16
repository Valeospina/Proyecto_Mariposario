<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos
 
// Protección de la página de administración
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html'); // Redirige si no está logueado o no es admin
    exit;
}
 
$message = '';
$message_type = '';
 
// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recopilar y sanear los datos del formulario
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $categoria = htmlspecialchars(trim($_POST['categoria']));
    $descripcion = htmlspecialchars(trim($_POST['descripcion']));
    $precio = floatval($_POST['precio']); // Convertir a número flotante
    $stock = intval($_POST['stock']); // Convertir a entero
    $imagen_url = htmlspecialchars(trim($_POST['imagen_url']));
    $fecha_reposicion = !empty($_POST['fecha_reposicion']) ? $_POST['fecha_reposicion'] : null;
    $notificar_disponibilidad = isset($_POST['notificar_disponibilidad']) ? 1 : 0; // 1 si marcado, 0 si no
 
    // Validación básica de datos
    if (empty($nombre) || empty($categoria) || empty($descripcion) || $precio <= 0 || $stock < 0) {
        $message = "Todos los campos obligatorios deben ser completados correctamente.";
        $message_type = "danger";
    } else {
        // Prepara la consulta para insertar el producto
        $insert_query = "INSERT INTO Producto (Nombre, Categoria, Descripcion, Precio, Stock, Imagen_URL, Fecha_Reposicion, Notificar_Disponibilidad) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
 
        try {
            if (isset($conn) && $conn instanceof mysqli) {
                $stmt = $conn->prepare($insert_query);
                // "ssdidsis" - string, string, double, integer, string, date/string, integer
                // Ajusta los tipos según tus campos: s=string, i=integer, d=double/float, b=blob
                $stmt->bind_param("sssdissi", $nombre, $categoria, $descripcion, $precio, $stock, $imagen_url, $fecha_reposicion, $notificar_disponibilidad);
                $stmt->execute();
 
                if ($stmt->affected_rows > 0) {
                    $message = "Producto añadido exitosamente.";
                    $message_type = "success";
                    // Limpiar el formulario después de un éxito
                    $_POST = array();
                } else {
                    $message = "No se pudo añadir el producto.";
                    $message_type = "warning";
                }
                $stmt->close();
            } else {
                throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
            }
        } catch (Exception $e) {
            error_log("Error al añadir producto: " . $e->getMessage());
            $message = "Error al añadir el producto: " . htmlspecialchars($e->getMessage());
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
<title>Añadir Producto - Panel de Administración</title>
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
<h2>Añadir Nuevo Producto</h2>
<p>Completa el formulario para agregar un nuevo producto al inventario.</p>
 
        <?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?>">
<?php echo $message; ?>
</div>
<?php endif; ?>
 
        <form action="add_product.php" method="POST">
<div class="form-group">
<label for="nombre">Nombre del Producto:</label>
<input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
</div>
<div class="form-group">
<label for="categoria">Categoría:</label>
<select id="categoria" name="categoria" required>
<option value="">Selecciona una categoría</option>
<option value="Mariposas" <?php echo (($_POST['categoria'] ?? '') == 'Mariposas') ? 'selected' : ''; ?>>Mariposas</option>
<option value="Orquídeas" <?php echo (($_POST['categoria'] ?? '') == 'Orquídeas') ? 'selected' : ''; ?>>Orquídeas</option>
</select>
</div>
<div class="form-group">
<label for="descripcion">Descripción:</label>
<textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
</div>
<div class="form-group">
<label for="precio">Precio:</label>
<input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>" required>
</div>
<div class="form-group">
<label for="stock">Stock:</label>
<input type="number" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($_POST['stock'] ?? ''); ?>" required>
</div>
<div class="form-group">
<label for="imagen_url">URL de la Imagen (opcional):</label>
<input type="text" id="imagen_url" name="imagen_url" value="<?php echo htmlspecialchars($_POST['imagen_url'] ?? ''); ?>">
</div>
<div class="form-group">
<label for="fecha_reposicion">Fecha de Reposición (opcional):</label>
<input type="date" id="fecha_reposicion" name="fecha_reposicion" value="<?php echo htmlspecialchars($_POST['fecha_reposicion'] ?? ''); ?>">
</div>
<div class="form-group">
<input type="checkbox" id="notificar_disponibilidad" name="notificar_disponibilidad" <?php echo (isset($_POST['notificar_disponibilidad']) && $_POST['notificar_disponibilidad']) ? 'checked' : ''; ?>>
<label for="notificar_disponibilidad">Notificar Disponibilidad</label>
</div>
<div class="button-group">
  <button type="submit" class="btn-submit">Añadir Producto</button>
  <p><a href="products.php" class="btn-returnProducto">Volver a la lista de productos</a></p>
</div>
</form>
 
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