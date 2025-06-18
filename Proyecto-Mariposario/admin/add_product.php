<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección de la página de administración
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html'); // Redirige si no está logueado o no es admin
    exit;
}

$message = '';
$message_type = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recopilar y sanear los datos del formulario
    $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $categoria = htmlspecialchars(trim($_POST['categoria'] ?? ''));
    $descripcion = htmlspecialchars(trim($_POST['descripcion'] ?? ''));
    $precio = floatval($_POST['precio'] ?? 0); // Convertir a número flotante
    $stock = intval($_POST['stock'] ?? 0);     // Convertir a entero
    $imagen_url = htmlspecialchars(trim($_POST['imagen_url'] ?? ''));
    $fecha_reposicion = !empty($_POST['fecha_reposicion'] ?? '') ? $_POST['fecha_reposicion'] : null;
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
                // "ssdsissi" - string, string, double, integer, string, date/string, integer
                // Ajusta los tipos según tus campos: s=string, i=integer, d=double/float, b=blob
                $stmt->bind_param("ssdsissi", $nombre, $categoria, $descripcion, $precio, $stock, $imagen_url, $fecha_reposicion, $notificar_disponibilidad);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Producto añadido exitosamente.";
                    $message_type = "success";
                    // Opcional: Redirigir a products.php con un mensaje de éxito
                    header('Location: products.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                    exit;
                    // Si no redireccionas, puedes limpiar el formulario aquí:
                    // $_POST = array(); // Limpia los valores de los campos después del éxito
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

// Define el título de la página actual
$page_title = 'Añadir Nuevo Producto';
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
    <link rel="stylesheet" href="../css/admin.css"> </head>
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
                    <h2>Añadir Nuevo Producto</h2>
                    <p>Completa el formulario para agregar un nuevo producto al inventario.</p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-container"> <h3>Detalles del Nuevo Producto</h3>
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
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="notificar_disponibilidad" name="notificar_disponibilidad" value="1" <?php echo (isset($_POST['notificar_disponibilidad']) && $_POST['notificar_disponibilidad']) ? 'checked' : ''; ?>>
                                <label for="notificar_disponibilidad">Notificar Disponibilidad</label>
                            </div>
                            <div class="button-group">
                                <button type="submit" class="btn btn-submit"><i class="fas fa-plus-circle"></i> Añadir Producto</button>
                                <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista</a>
                            </div>
                        </form>
                    </div>

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