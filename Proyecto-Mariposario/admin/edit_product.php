<?php
session_start();
include '../DB.php'; // Archivo de conexión a la base de datos

// Protección: solo admin puede acceder
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;
$message = '';
$message_type = '';

// Directorio donde se guardan las imágenes subidas
$upload_directory = '../uploads/productos/';

// Crear el directorio si no existe
if (!is_dir($upload_directory)) {
    mkdir($upload_directory, 0755, true); // Crea directorios anidados y con permisos 0755
}


// Si no se proporcionó un ID válido, redirigir
if ($product_id <= 0) {
    header('Location: products.php?message=' . urlencode('ID de producto no válido.') . '&type=danger');
    exit;
}

// Lógica para obtener los datos del producto a editar
try {
    if ($conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT ID_Producto, Nombre, Categoria, Descripcion, Precio, Imagen_URL, Activo_Catalogo FROM Producto WHERE ID_Producto = ?");
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta para obtener producto: " . $conn->error);
        }
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        if (!$product) {
            header('Location: products.php?message=' . urlencode('Producto no encontrado.') . '&type=danger');
            exit;
        }
    } else {
        throw new Exception("Conexión a la base de datos no válida o no disponible.");
    }
} catch (Exception $e) {
    error_log("Error al cargar producto para edición: " . $e->getMessage());
    $message = "Error al cargar el producto: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}


// Si el formulario ha sido enviado (para actualizar el producto)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $product) {
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $imagen_url_db = $product['Imagen_URL']; // Valor actual, se modificará si hay subida o nueva URL
    $activo_catalogo = isset($_POST['activo_catalogo']) ? 1 : 0; // Checkbox
    $clear_image = isset($_POST['clear_image']); // Nuevo checkbox para eliminar imagen

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
                $message = "Tipo de archivo no permitido para la nueva imagen. Solo JPG, JPEG, PNG, GIF.";
                $message_type = "danger";
            } elseif ($file_size > $max_file_size) {
                $message = "La nueva imagen es demasiado grande. Máximo 5MB.";
                $message_type = "danger";
            } else {
                // Generar un nombre único para el archivo
                $new_file_name = uniqid('prod_', true) . '.' . $file_ext;
                $destination_path = $upload_directory . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $destination_path)) {
                    // Eliminar la imagen antigua si era un archivo local
                    if ($product['Imagen_URL'] && !filter_var($product['Imagen_URL'], FILTER_VALIDATE_URL)) {
                        $old_file_path = '../' . $product['Imagen_URL']; // Convertir de vuelta a ruta local
                        if (file_exists($old_file_path) && is_file($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }
                    $imagen_url_db = str_replace('../', '', $destination_path); // Guardar ruta relativa limpia
                    $file_uploaded = true;
                } else {
                    $message = "Error al subir el nuevo archivo de imagen.";
                    $message_type = "danger";
                }
            }
        } elseif ($clear_image) {
            // Si se marcó para limpiar la imagen y no se subió una nueva
            if ($product['Imagen_URL'] && !filter_var($product['Imagen_URL'], FILTER_VALIDATE_URL)) {
                $old_file_path = '../' . $product['Imagen_URL'];
                if (file_exists($old_file_path) && is_file($old_file_path)) {
                    unlink($old_file_path);
                }
            }
            $imagen_url_db = null; // Establecer la URL de la imagen a NULL
        } elseif (!empty(trim($_POST['imagen_url'] ?? '')) && trim($_POST['imagen_url']) != $product['Imagen_URL']) {
            // Si no se subió un archivo, no se marcó para limpiar, y la URL de texto ha cambiado
            $imagen_url_input = trim($_POST['imagen_url']);
            if (filter_var($imagen_url_input, FILTER_VALIDATE_URL)) {
                // Eliminar la imagen antigua si era un archivo local y ahora se usa una URL externa
                if ($product['Imagen_URL'] && !filter_var($product['Imagen_URL'], FILTER_VALIDATE_URL)) {
                    $old_file_path = '../' . $product['Imagen_URL'];
                    if (file_exists($old_file_path) && is_file($old_file_path)) {
                        unlink($old_file_path);
                    }
                }
                $imagen_url_db = $imagen_url_input;
            } else {
                $message = "La URL de la imagen proporcionada no es válida.";
                $message_type = "danger";
            }
        } elseif (empty(trim($_POST['imagen_url'] ?? ''))) {
            // Si no se subió un archivo, no se marcó para limpiar, y el campo de URL está vacío
            // Esto significa que el usuario quiere que la imagen sea NULL si no la llenó y no subió
             if ($product['Imagen_URL'] && !filter_var($product['Imagen_URL'], FILTER_VALIDATE_URL)) {
                $old_file_path = '../' . $product['Imagen_URL'];
                if (file_exists($old_file_path) && is_file($old_file_path)) {
                    unlink($old_file_path);
                }
            }
            $imagen_url_db = null;
        }

        // Si el mensaje de error ya está seteado por validación de archivo/URL, no intentar insertar
        if (empty($message)) {
            try {
                if ($conn instanceof mysqli) {
                    // Prepara la consulta SQL para actualizar el producto
                    $stmt = $conn->prepare("UPDATE Producto SET Nombre = ?, Categoria = ?, Descripcion = ?, Precio = ?, Imagen_URL = ?, Activo_Catalogo = ? WHERE ID_Producto = ?");

                    if (!$stmt) {
                        throw new Exception("Error al preparar la consulta de actualización: " . $conn->error);
                    }

                    $stmt->bind_param("sssdsii", $nombre, $categoria, $descripcion, $precio, $imagen_url_db, $activo_catalogo, $product_id);

                    if ($stmt->execute()) {
                        $message = "Producto '<strong>" . htmlspecialchars($nombre) . "</strong>' actualizado exitosamente.";
                        $message_type = "success";
                        // Volver a cargar el producto después de la actualización para reflejar los cambios
                        $product['Nombre'] = $nombre;
                        $product['Categoria'] = $categoria;
                        $product['Descripcion'] = $descripcion;
                        $product['Precio'] = $precio;
                        $product['Imagen_URL'] = $imagen_url_db; // Actualizar con la nueva URL/ruta
                        $product['Activo_Catalogo'] = $activo_catalogo;

                    } else {
                        throw new Exception("Error al actualizar el producto: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Conexión a la base de datos no válida o no disponible.");
                }
            } catch (Exception $e) {
                error_log("Error al actualizar producto: " . $e->getMessage());
                $message = "Error al actualizar el producto: " . htmlspecialchars($e->getMessage());
                $message_type = "danger";
            }
        }
    }
}

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

                    <?php if ($product): // Solo mostrar el formulario si el producto fue cargado correctamente ?>
                        <h3>Editar Producto: <?php echo htmlspecialchars($product['Nombre']); ?></h3>
                        <form action="edit_product.php?id=<?php echo htmlspecialchars($product_id); ?>" method="POST" class="admin-form" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="nombre">Nombre del Producto:</label>
                                <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($product['Nombre'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="categoria">Categoría:</label>
                                <select id="categoria" name="categoria" required>
                                    <option value="">Seleccione una categoría</option>
                                    <option value="Mariposa" <?php echo (isset($product['Categoria']) && $product['Categoria'] == 'Mariposa') ? 'selected' : ''; ?>>Mariposa</option>
                                    <option value="Orquidea" <?php echo (isset($product['Categoria']) && $product['Categoria'] == 'Orquidea') ? 'selected' : ''; ?>>Orquídea</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="descripcion">Descripción:</label>
                                <textarea id="descripcion" name="descripcion" rows="5" required><?php echo htmlspecialchars($product['Descripcion'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="precio">Precio:</label>
                                <input type="number" id="precio" name="precio" step="0.01" min="0" required value="<?php echo htmlspecialchars($product['Precio'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label>Imagen Actual:</label>
                                <?php if (!empty($product['Imagen_URL'])): ?>
                                    <?php
                                    // Determinar si la URL es local o externa para mostrarla correctamente
                                    $display_image_src = $product['Imagen_URL'];
                                    if (!filter_var($product['Imagen_URL'], FILTER_VALIDATE_URL) && strpos($product['Imagen_URL'], 'uploads/productos/') === 0) {
                                        $display_image_src = '../' . $product['Imagen_URL']; // Añadir ../ para ruta relativa a admin/
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($display_image_src); ?>" alt="Imagen actual" style="max-width: 150px; height: auto; display: block; margin-top: 10px; border-radius: 5px;">
                                    <small style="display: block; margin-top: 5px;">URL/Ruta actual: <?php echo htmlspecialchars($product['Imagen_URL']); ?></small>
                                <?php else: ?>
                                    <p>No hay imagen cargada para este producto.</p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="imagen_file">Subir Nueva Imagen (reemplazará la actual):</label>
                                <input type="file" id="imagen_file" name="imagen_file" accept="image/*">
                                <small>Sube un archivo de imagen (JPG, PNG, GIF). Máx. 5MB.</small>
                            </div>

                            <div class="form-group">
                                <label for="imagen_url">O usar Nueva URL de la Imagen:</label>
                                <input type="url" id="imagen_url" name="imagen_url" value="<?php echo htmlspecialchars($product['Imagen_URL'] ?? ''); ?>">
                                <small>Ej: https://ejemplo.com/nueva_imagen.jpg (Dejar vacío si subes un archivo o mantienes la actual)</small>
                            </div>

                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="clear_image" name="clear_image" value="1">
                                <label for="clear_image">Eliminar imagen actual (Si subes una nueva o pones URL, esto se ignora)</label>
                            </div>


                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="activo_catalogo" name="activo_catalogo" value="1" <?php echo ($product['Activo_Catalogo'] == 1) ? 'checked' : ''; ?>>
                                <label for="activo_catalogo">Activo en Catálogo (Visible para usuarios)</label>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                                <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a Productos</a>
                            </div>
                        </form>
                    <?php elseif (empty($message)): ?>
                        <p class="alert alert-info">Producto no encontrado o no disponible para edición.</p>
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