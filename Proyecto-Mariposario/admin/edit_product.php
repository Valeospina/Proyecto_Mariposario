<?php
session_start();
include '../DB.php';

// --- Access Control ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$message_type = '';
$product = null;

// Directorio de imágenes
$upload_directory = '../uploads/productos/';
if (!is_dir($upload_directory)) {
    mkdir($upload_directory, 0755, true);
}

// --- Validación ID ---
if ($product_id <= 0) {
    header('Location: products.php?message=' . urlencode('ID de producto no válido.') . '&type=danger');
    exit;
}

// --- Consultar Producto ---
try {
    $stmt = $conn->prepare("SELECT * FROM Producto WHERE ID_Producto = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        header('Location: products.php?message=' . urlencode('Producto no encontrado.') . '&type=danger');
        exit;
    }
} catch (Exception $e) {
    $message = "Error al cargar el producto: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// --- Procesar Formulario ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    $nombre = trim($_POST['nombre']);
    $categoria = trim($_POST['categoria']);
    $descripcion = trim($_POST['descripcion']);
    $precio = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT);
    $activo_catalogo = isset($_POST['activo_catalogo']) ? 1 : 0;
    $clear_image = isset($_POST['clear_image']);

    $imagen_url_db = $product['Imagen_URL'];

    if (empty($nombre) || empty($categoria) || empty($descripcion) || $precio === false || $precio < 0) {
        $message = "Complete todos los campos obligatorios correctamente.";
        $message_type = "danger";
    } else {
        $upload_success = true;

        // Subir archivo
        if (!empty($_FILES['imagen_file']['name']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($_FILES['imagen_file']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $max_size = 2 * 1024 * 1024;

            if (!in_array($file_ext, $allowed_exts)) {
                $message = "Formato no permitido. Usa JPG, PNG, GIF o WEBP.";
                $message_type = "warning";
                $upload_success = false;
            } elseif ($_FILES['imagen_file']['size'] > $max_size) {
                $message = "La imagen supera los 2MB.";
                $message_type = "warning";
                $upload_success = false;
            } else {
                $new_file = uniqid('prod_', true) . '.' . $file_ext;
                $dest_path = $upload_directory . $new_file;

                if (move_uploaded_file($_FILES['imagen_file']['tmp_name'], $dest_path)) {
                    // Eliminar imagen antigua si es local
                    if ($product['Imagen_URL'] && !filter_var($product['Imagen_URL'], FILTER_VALIDATE_URL)) {
                        $old_path = '../' . ltrim($product['Imagen_URL'], '/');
                        if (file_exists($old_path)) unlink($old_path);
                    }
                    $imagen_url_db = str_replace('../', '', $dest_path);
                } else {
                    $message = "Error al guardar la imagen en el servidor.";
                    $message_type = "danger";
                    $upload_success = false;
                }
            }
        } elseif ($clear_image) {
            // Eliminar imagen
            if ($product['Imagen_URL'] && !filter_var($product['Imagen_URL'], FILTER_VALIDATE_URL)) {
                $old_path = '../' . ltrim($product['Imagen_URL'], '/');
                if (file_exists($old_path)) unlink($old_path);
            }
            $imagen_url_db = null;
        } elseif (!empty($_POST['imagen_url']) && filter_var($_POST['imagen_url'], FILTER_VALIDATE_URL)) {
            $imagen_url_db = trim($_POST['imagen_url']);
        }

        if ($upload_success) {
            try {
                $stmt = $conn->prepare("UPDATE Producto SET Nombre=?, Categoria=?, Descripcion=?, Precio=?, Imagen_URL=?, Activo_Catalogo=? WHERE ID_Producto=?");
                $stmt->bind_param("sssdsii", $nombre, $categoria, $descripcion, $precio, $imagen_url_db, $activo_catalogo, $product_id);

                if ($stmt->execute()) {
                    $message = "Producto actualizado correctamente.";
                    $message_type = "success";
                    $product['Nombre'] = $nombre;
                    $product['Categoria'] = $categoria;
                    $product['Descripcion'] = $descripcion;
                    $product['Precio'] = $precio;
                    $product['Imagen_URL'] = $imagen_url_db;
                    $product['Activo_Catalogo'] = $activo_catalogo;
                } else {
                    $message = "Error al actualizar: " . htmlspecialchars($stmt->error);
                    $message_type = "danger";
                }
                $stmt->close();
            } catch (Exception $e) {
                $message = "Error DB: " . htmlspecialchars($e->getMessage());
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
    <title><?php echo $page_title; ?> - Panel Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body {font-family: 'Poppins', sans-serif;background: #f5f6fa;margin: 0;}
        .admin-content {max-width: 900px;margin: 40px auto;background: #fff;padding: 35px;border-radius: 12px;box-shadow: 0 4px 15px rgba(0,0,0,0.08);}
        .admin-content h3 {text-align: center;font-size: 1.8rem;margin-bottom: 20px;color: #2c3e50;border-bottom: 1px solid #e0e0e0;padding-bottom: 10px;}
        .admin-form {display: flex;flex-direction: column;gap: 18px;}
        .form-group label {font-weight: 500;margin-bottom: 8px;color: #34495e;}
        .form-group input,.form-group select,.form-group textarea {padding: 12px;font-size: 1rem;border: 1px solid #dcdcdc;border-radius: 8px;background: #f9f9f9;}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus {border-color: #3498db;box-shadow: 0 0 8px rgba(52,152,219,0.2);background: #fff;outline: none;}
        .form-group textarea {min-height: 120px;}
        .image-preview img {max-width: 180px;border-radius: 8px;margin: 10px 0;display: block;}
        .checkbox-group {display: flex;align-items: center;gap: 10px;}
        .form-actions {display: flex;justify-content: flex-end;gap: 12px;margin-top: 20px;}
        .btn {display: inline-flex;align-items: center;gap: 8px;padding: 12px 20px;font-weight: 600;border-radius: 8px;text-decoration: none;transition: 0.3s ease;cursor: pointer;}
        .btn-primary {background: #28a745;color: #fff;}
        .btn-primary:hover {background: #218838;}
        .btn-secondary {background: #6c757d;color: #fff;}
        .btn-secondary:hover {background: #5a6268;}
        .alert {padding: 12px 18px;border-radius: 6px;margin-bottom: 20px;font-size: 0.95rem;}
        .alert-success {background: #d4edda;color: #155724;border-left: 5px solid #28a745;}
        .alert-danger {background: #f8d7da;color: #721c24;border-left: 5px solid #dc3545;}
        .alert-warning {background: #fff3cd;color: #856404;border-left: 5px solid #ffc107;}
        @media(max-width:768px){.admin-content{margin:20px;padding:20px;}.form-actions{flex-direction:column;gap:10px;}.btn{width:100%;justify-content:center;}}
    </style>
</head>
<body>
<div class="admin-dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>Admin Panel</h3></div>
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
        <div class="sidebar-footer"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></div>
    </aside>

    <div class="main-panel">
        <header class="main-panel-header">
            <h2><?php echo $page_title; ?></h2>
            <div class="user-profile"><span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span><img src="../images/user-avatar.png" alt="User Avatar"></div>
        </header>

        <main class="content-area">
            <div class="admin-content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <h3>Editar Producto</h3>
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label>Nombre:</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($product['Nombre']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Categoría:</label>
                        <select name="categoria" required>
                            <option value="Mariposa" <?php echo ($product['Categoria']=='Mariposa')?'selected':''; ?>>Mariposa</option>
                            <option value="Orquidea" <?php echo ($product['Categoria']=='Orquidea')?'selected':''; ?>>Orquídea</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Precio (₡):</label>
                        <input type="number" step="0.01" name="precio" value="<?php echo htmlspecialchars($product['Precio']); ?>" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción:</label>
                        <textarea name="descripcion" required><?php echo htmlspecialchars($product['Descripcion']); ?></textarea>
                    </div>

                    <div class="image-preview">
                        <?php if ($product['Imagen_URL']): ?>
                            <img src="<?php echo (filter_var($product['Imagen_URL'], FILTER_VALIDATE_URL)) ? $product['Imagen_URL'] : '../'.htmlspecialchars($product['Imagen_URL']); ?>" alt="Imagen actual">
                        <?php else: ?>
                            <p>No hay imagen actual.</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Subir nueva imagen:</label>
                        <input type="file" name="imagen_file" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>O URL de imagen:</label>
                        <input type="url" name="imagen_url" value="<?php echo htmlspecialchars($product['Imagen_URL']); ?>">
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="clear_image">
                        <label>Eliminar imagen actual</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="activo_catalogo" <?php echo ($product['Activo_Catalogo'])?'checked':''; ?>>
                        <label>Activo en catálogo</label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                        <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
<?php if (isset($conn)) $conn->close(); ?>
