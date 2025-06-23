<?php
session_start();
include '../DB.php'; // Archivo de conexión a la base de datos

// Protección: solo admin puede acceder
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$message_type = '';

// Directorio donde se guardarán las imágenes subidas (asegúrate de que exista y tenga permisos de escritura)
$upload_directory = '../uploads/productos/';

// Crear el directorio si no existe
if (!is_dir($upload_directory)) {
    mkdir($upload_directory, 0755, true); // Crea directorios anidados y con permisos 0755
}


// Si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $imagen_url_db = null; // Lo que finalmente se guardará en la base de datos
    $activo_catalogo = isset($_POST['activo_catalogo']) ? 1 : 0; // Checkbox

    // Validaciones básicas de campos de texto
    if (empty($nombre) || empty($categoria) || empty($descripcion) || $precio === false || $precio < 0) {
        $message = "Todos los campos obligatorios (Nombre, Categoría, Descripción, Precio) deben ser completados correctamente.";
        $message_type = "danger";
    } else {
        // --- Lógica para manejar la imagen (subida o URL) ---
        $file_uploaded = false;
        if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] == UPLOAD_ERR_OK) {
            $file_name = $_FILES['imagen_file']['name'];
            $file_tmp_name = $_FILES['imagen_file']['tmp_name'];
            $file_size = $_FILES['imagen_file']['size'];
            $file_type = $_FILES['imagen_file']['type'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file_ext, $allowed_ext)) {
                $message = "Tipo de archivo no permitido para la imagen. Solo JPG, JPEG, PNG, GIF.";
                $message_type = "danger";
            } elseif ($file_size > $max_file_size) {
                $message = "La imagen es demasiado grande. Máximo 5MB.";
                $message_type = "danger";
            } else {
                // Generar un nombre único para el archivo
                $new_file_name = uniqid('prod_', true) . '.' . $file_ext;
                $destination_path = $upload_directory . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $destination_path)) {
                    // Guardar la ruta relativa para la base de datos
                    $imagen_url_db = str_replace('../', '', $destination_path); // Eliminar '../' para guardar una ruta relativa limpia
                    $file_uploaded = true;
                } else {
                    $message = "Error al subir el archivo de imagen.";
                    $message_type = "danger";
                }
            }
        }

        // Si no se subió un archivo exitosamente, revisar si hay una URL
        if (!$file_uploaded && !empty(trim($_POST['imagen_url'] ?? ''))) {
            $imagen_url_input = trim($_POST['imagen_url']);
            // Validar si es una URL válida (ej. que empiece con http/https)
            if (filter_var($imagen_url_input, FILTER_VALIDATE_URL)) {
                $imagen_url_db = $imagen_url_input;
            } else {
                $message = "La URL de la imagen no es válida.";
                $message_type = "danger";
            }
        }
        // Si el mensaje de error ya está seteado por validación de archivo/URL, no intentar insertar
        if (empty($message)) {
            try {
                if ($conn instanceof mysqli) {
                    // Prepara la consulta SQL para insertar un nuevo producto
                    $stmt = $conn->prepare("INSERT INTO Producto (Nombre, Categoria, Descripcion, Precio, Imagen_URL, Activo_Catalogo) VALUES (?, ?, ?, ?, ?, ?)");

                    if (!$stmt) {
                        throw new Exception("Error al preparar la consulta: " . $conn->error);
                    }

                    $stmt->bind_param("sssdsi", $nombre, $categoria, $descripcion, $precio, $imagen_url_db, $activo_catalogo);

                    if ($stmt->execute()) {
                        $message = "Producto '<strong>" . htmlspecialchars($nombre) . "</strong>' añadido exitosamente.";
                        $message_type = "success";
                        // Redirigir para limpiar el POST y mostrar el mensaje
                        header('Location: products.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                        exit;
                    } else {
                        throw new Exception("Error al añadir el producto: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Conexión a la base de datos no válida o no disponible.");
                }
            } catch (Exception $e) {
                error_log("Error al añadir producto: " . $e->getMessage());
                $message = "Error al añadir el producto: " . htmlspecialchars($e->getMessage());
                $message_type = "danger";
            }
        }
    }
}

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
                    <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="active"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="inventarioAdmin.php"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                    <li><a href="eventoAdmin.php"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
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

                    <h3>Formulario para Añadir Producto</h3>
                    <form action="add_product.php" method="POST" class="admin-form" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="nombre">Nombre del Producto:</label>
                            <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="categoria">Categoría:</label>
                            <select id="categoria" name="categoria" required>
                                <option value="">Seleccione una categoría</option>
                                <option value="Mariposa" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'Mariposa') ? 'selected' : ''; ?>>Mariposa</option>
                                <option value="Orquidea" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == 'Orquidea') ? 'selected' : ''; ?>>Orquídea</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción:</label>
                            <textarea id="descripcion" name="descripcion" rows="5" required><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="precio">Precio:</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="imagen_file">Subir Imagen:</label>
                            <input type="file" id="imagen_file" name="imagen_file" accept="image/*">
                            <small>Sube un archivo de imagen (JPG, PNG, GIF). Máx. 5MB.</small>
                        </div>

                        <div class="form-group">
                            <label for="imagen_url">O usar URL de la Imagen:</label>
                            <input type="url" id="imagen_url" name="imagen_url" value="<?php echo htmlspecialchars($_POST['imagen_url'] ?? ''); ?>">
                            <small>Ej: https://ejemplo.com/imagen.jpg (Dejar vacío si subiste una imagen)</small>
                        </div>

                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="activo_catalogo" name="activo_catalogo" value="1" <?php echo (isset($_POST['activo_catalogo']) && $_POST['activo_catalogo'] == '1') ? 'checked' : 'checked'; ?>>
                            <label for="activo_catalogo">Activo en Catálogo (Visible para usuarios)</label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Añadir Producto</button>
                            <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a Productos</a>
                        </div>
                    </form>
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