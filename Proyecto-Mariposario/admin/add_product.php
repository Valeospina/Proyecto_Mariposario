<?php
session_start();
include '../DB.php'; // Archivo de conexión a la base de datos

// Protección: solo admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

$message = '';
$message_type = '';

// Directorio donde se guardarán las imágenes subidas
$upload_directory = '../uploads/productos/';
if (!is_dir($upload_directory)) {
    mkdir($upload_directory, 0755, true);
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = filter_var($_POST['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    $imagen_url_db = null;
    $activo_catalogo = isset($_POST['activo_catalogo']) ? 1 : 0;

    if (empty($nombre) || empty($categoria) || empty($descripcion) || $precio === false || $precio < 0) {
        $message = "Completa todos los campos obligatorios correctamente.";
        $message_type = "danger";
    } else {
        // Subir imagen si existe
        $file_uploaded = false;
        if (!empty($_FILES['imagen_file']['name']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
            $file_ext = strtolower(pathinfo($_FILES['imagen_file']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024;

            if (!in_array($file_ext, $allowed_ext)) {
                $message = "Formato no permitido. Usa JPG, PNG o GIF.";
                $message_type = "danger";
            } elseif ($_FILES['imagen_file']['size'] > $max_file_size) {
                $message = "La imagen no debe superar 5MB.";
                $message_type = "danger";
            } else {
                $new_file_name = uniqid('prod_', true) . '.' . $file_ext;
                $destination = $upload_directory . $new_file_name;
                if (move_uploaded_file($_FILES['imagen_file']['tmp_name'], $destination)) {
                    $imagen_url_db = str_replace('../', '', $destination);
                    $file_uploaded = true;
                }
            }
        }

        // Si no se subió imagen, validar URL
        if (!$file_uploaded && !empty($_POST['imagen_url'])) {
            if (filter_var($_POST['imagen_url'], FILTER_VALIDATE_URL)) {
                $imagen_url_db = trim($_POST['imagen_url']);
            } else {
                $message = "La URL de imagen no es válida.";
                $message_type = "danger";
            }
        }

        // Insertar en DB si no hay errores
        if (empty($message)) {
            try {
                $stmt = $conn->prepare("INSERT INTO Producto (Nombre, Categoria, Descripcion, Precio, Imagen_URL, Activo_Catalogo) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdsi", $nombre, $categoria, $descripcion, $precio, $imagen_url_db, $activo_catalogo);

                if ($stmt->execute()) {
                    header('Location: products.php?message=' . urlencode('Producto añadido correctamente') . '&type=success');
                    exit;
                } else {
                    throw new Exception("Error al guardar: " . $stmt->error);
                }
            } catch (Exception $e) {
                $message = "Error: " . htmlspecialchars($e->getMessage());
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
    <title><?php echo $page_title; ?> - Panel Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            margin: 0;
        }
        .admin-content {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .admin-content h3 {
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }
        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #34495e;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            font-size: 1rem;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52,152,219,0.2);
            background: #fff;
            outline: none;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s ease;
            cursor: pointer;
        }
        .btn-primary {
            background: #28a745;
            color: #fff;
        }
        .btn-primary:hover {
            background: #218838;
        }
        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        @media(max-width:768px){
            .admin-content {margin: 20px; padding: 20px;}
            .form-actions {flex-direction: column;}
            .btn {width:100%; justify-content:center;}
        }
    </style>
</head>
<body>
<div class="admin-dashboard-layout">
    <!-- Sidebar -->
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

    <!-- Main Content -->
    <div class="main-panel">
        <header class="main-panel-header">
            <h2><?php echo $page_title; ?></h2>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                <img src="../images/user-avatar.png" alt="User Avatar">
            </div>
        </header>

        <main class="content-area">
            <div class="admin-content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <h3>Añadir Nuevo Producto</h3>
                <form method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="categoria">Categoría:</label>
                        <select name="categoria" id="categoria" required>
                            <option value="">Seleccione...</option>
                            <option value="Mariposa">Mariposa</option>
                            <option value="Orquidea">Orquídea</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea name="descripcion" id="descripcion" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="precio">Precio:</label>
                        <input type="number" name="precio" id="precio" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="imagen_file">Subir Imagen:</label>
                        <input type="file" name="imagen_file" id="imagen_file" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="imagen_url">O URL de Imagen:</label>
                        <input type="url" name="imagen_url" id="imagen_url">
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="activo_catalogo" id="activo_catalogo" checked>
                        <label for="activo_catalogo">Activo en catálogo</label>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Añadir Producto</button>
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
