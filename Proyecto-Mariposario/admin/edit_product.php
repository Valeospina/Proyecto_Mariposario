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
    <style>
        /* Variables de color de admin.css para consistencia */
        :root {
            --sidebar-bg: #2C3E50; 
            --sidebar-link-color: #ECF0F1;
            --sidebar-hover-bg: #34495E;
            --sidebar-active-bg: #1ABC9C; 
            --sidebar-active-color: #FFFFFF;

            --main-bg: #F0F2F5; 
            --card-bg: #FFFFFF;
            --header-top-bg: #FFFFFF;
            --border-color: #E0E0E0;

            --text-dark: #333333;
            --text-secondary: #7F8C8D;
            --accent-blue: #3498DB; 
            --danger-red: #E74C3C; 

            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        /* Estilos generales para el formulario y sus elementos */
        .admin-form {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-medium);
            max-width: 700px; /* Ajustado para un poco más de espacio */
            margin: 20px auto; /* Centrar el formulario con margen superior */
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        /* Estilos para todos los inputs de texto, número, email, password, textarea y select */
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select,
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="url"] /* Nuevo estilo para input de URL */
        {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            color: var(--text-dark);
            background-color: var(--main-bg);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        /* Estilos de foco para todos los inputs y selects */
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group textarea:focus,
        .form-group select:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group input[type="url"]:focus /* Estilo de foco para input de URL */
        {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }

        /* Estilos para la sección de Imagen Actual */
        .form-group.image-display {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            background-color: var(--main-bg); /* Fondo ligeramente gris para la sección */
            margin-top: 25px;
        }
        .form-group.image-display label {
            font-size: 1.05rem;
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        .form-group.image-display img {
            max-width: 180px; /* Un poco más grande */
            height: auto;
            display: block;
            margin-top: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group.image-display small {
            display: block;
            margin-top: 8px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            word-break: break-all; /* Permite que la URL larga se rompa */
        }
        .form-group.image-display p {
            color: var(--text-secondary);
            font-style: italic;
            margin-top: 10px;
        }

        /* Estilos para input[type="file"] */
        .form-group input[type="file"] {
            padding: 10px 15px; /* Ajustar padding para archivos */
            height: auto; /* Permitir que la altura se ajuste al contenido */
            background-color: var(--card-bg); /* Fondo blanco para el input de archivo */
            cursor: pointer;
        }
        .form-group input[type="file"]::-webkit-file-upload-button {
            visibility: hidden;
        }
        .form-group input[type="file"]::before {
            content: 'Seleccionar archivo...';
            display: inline-block;
            background: var(--accent-blue);
            color: white;
            border: 1px solid var(--accent-blue);
            border-radius: 5px;
            padding: 8px 12px;
            outline: none;
            white-space: nowrap;
            -webkit-user-select: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .form-group input[type="file"]:hover::before {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .form-group input[type="file"]:active::before {
            background-color: #2471a3;
        }
        .form-group input[type="file"]::file-selector-button { /* Para Firefox */
            background: var(--accent-blue);
            color: white;
            border: 1px solid var(--accent-blue);
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .form-group input[type="file"]::file-selector-button:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        /* Estilos para el checkbox-group (activo en catálogo, eliminar imagen) */
        .form-group.checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .form-group.checkbox-group input[type="checkbox"] {
            width: auto; /* Sobrescribe el 100% */
            margin-right: 10px;
            transform: scale(1.2); /* Aumenta el tamaño del checkbox */
            cursor: pointer;
        }
        .form-group.checkbox-group label {
            margin-bottom: 0; /* Elimina el margen inferior del label */
            display: inline-block; /* Para que el label esté en línea con el checkbox */
            cursor: pointer;
            font-weight: 400; /* Menos negrita para labels de checkbox */
            color: var(--text-dark);
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* Estilos para el grupo de botones de acción del formulario */
        .form-actions {
            display: flex;
            justify-content: flex-end; /* Alinea los botones a la derecha */
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .btn-primary {
            background-color: var(--sidebar-active-bg); /* Verde Turquesa */
            color: var(--sidebar-active-color);
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 10px rgba(26, 188, 156, 0.2);
        }
        .btn-primary:hover {
            background-color: #16A085;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(26, 188, 156, 0.3);
        }

        .btn-secondary {
            background-color: var(--text-secondary); /* Gris */
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 10px rgba(127, 140, 141, 0.2);
        }
        .btn-secondary:hover {
            background-color: #6C7A89;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(127, 140, 141, 0.3);
        }
        .btn .fas {
            margin-right: 8px;
        }


        /* Responsive adjustments */
        @media (max-width: 768px) {
            .admin-form {
                padding: 20px;
                margin: 15px auto;
            }
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            .btn-primary, .btn-secondary {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .admin-form {
                padding: 15px;
            }
            .form-group label {
                font-size: 0.9rem;
            }
            .form-group input, .form-group textarea, .form-group select {
                font-size: 0.9rem;
                padding: 10px 12px;
            }
            .form-group.image-display img {
                max-width: 120px;
            }
            .form-group.image-display small {
                font-size: 0.75rem;
            }
            .form-group input[type="file"]::before,
            .form-group input[type="file"]::file-selector-button {
                font-size: 0.8rem;
                padding: 6px 10px;
            }
            .form-group.checkbox-group label {
                font-size: 0.85rem;
            }
            .btn-primary, .btn-secondary {
                font-size: 0.9rem;
                padding: 10px 15px;
            }
        }
    </style>

    <div class="admin-dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="gestion_empleados.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'gestion_empleados.php' || basename($_SERVER['PHP_SELF']) == 'add_empleado.php' || basename($_SERVER['PHP_SELF']) == 'edit_empleado.php') ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Gestionar Empleados</a></li>
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'add_user.php' || basename($_SERVER['PHP_SELF']) == 'edit_user.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="active"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="inventarioAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'inventarioAdmin.php' || basename($_SERVER['PHP_SELF']) == 'add_inventario.php' || basename($_SERVER['PHP_SELF']) == 'edit_inventario.php') ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li> 
                    <li><a href="ReservaAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ReservaAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Reservas</a></li>
                    <li><a href="InsEventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'InsEventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Asistencia</a></li>
                    <li><a href="pedidos.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'pedidos.php' || basename($_SERVER['PHP_SELF']) == 'edit_pedido.php') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Gestionar Pedidos</a></li>
                    <li><a href="reporte_ventas.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reporte_ventas.php') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> Reporte de Ventas</a></li>
                    <li><a href="reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
                    <li><a href="reportAsis.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reportes Asistencia</a></li>                    
                    <li><a href="admin-chats.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-chats.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Soporte</a></li>  
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

                            <div class="form-group image-display">
                                <label>Imagen Actual:</label>
                                <?php if (!empty($product['Imagen_URL'])): ?>
                                    <?php
                                    // Determinar si la URL es local o externa para mostrarla correctamente
                                    $display_image_src = $product['Imagen_URL'];
                                    if (!filter_var($product['Imagen_URL'], FILTER_VALIDATE_URL) && strpos($product['Imagen_URL'], 'uploads/productos/') === 0) {
                                        $display_image_src = '../' . $product['Imagen_URL']; // Añadir ../ para ruta relativa a admin/
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($display_image_src); ?>" alt="Imagen actual">
                                    <small>URL/Ruta actual: <?php echo htmlspecialchars($product['Imagen_URL']); ?></small>
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
