<?php
session_start();
include '../DB.php';

// --- Protección: solo admin ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

// --- Inicialización ---
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$inventario_item = null;
$products_list = [];
$message = '';
$message_type = '';

// Validar ID
if ($item_id <= 0) {
    header('Location: inventarioAdmin.php?message=' . urlencode('ID de ítem de inventario no válido.') . '&type=danger');
    exit;
}

// Obtener lista de productos
try {
    $res = $conn->query("SELECT ID_Producto, Nombre FROM Producto ORDER BY Nombre");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $products_list[] = $row;
        }
        $res->free();
    } else {
        throw new Exception("Error al obtener la lista de productos: " . $conn->error);
    }
} catch (Exception $e) {
    $message = "Error al cargar productos: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// Obtener datos del ítem
try {
    $stmt = $conn->prepare("SELECT i.ID_Inventario, i.ID_Producto, i.SKU, i.Stock_Actual, i.Stock_Minimo, i.Ubicacion, i.Activo, p.Nombre AS NombreProducto 
                            FROM Inventario i 
                            JOIN Producto p ON i.ID_Producto = p.ID_Producto 
                            WHERE i.ID_Inventario = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $inventario_item = $result->fetch_assoc();
    $stmt->close();

    if (!$inventario_item) {
        header('Location: inventarioAdmin.php?message=' . urlencode('Ítem de inventario no encontrado.') . '&type=danger');
        exit;
    }
} catch (Exception $e) {
    $message = "Error al cargar el ítem: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// --- Procesar actualización ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $inventario_item) {
    $id_producto = filter_var($_POST['id_producto'] ?? '', FILTER_VALIDATE_INT);
    $sku = trim($_POST['sku'] ?? '');
    $stock_actual = filter_var($_POST['stock_actual'] ?? '', FILTER_VALIDATE_INT);
    $stock_minimo = filter_var($_POST['stock_minimo'] ?? '', FILTER_VALIDATE_INT);
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($id_producto === false || $id_producto <= 0 || empty($sku) || $stock_actual === false || $stock_actual < 0 || $stock_minimo === false || $stock_minimo < 0 || empty($ubicacion)) {
        $message = "Todos los campos obligatorios deben ser completados correctamente.";
        $message_type = "danger";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE Inventario SET ID_Producto=?, SKU=?, Stock_Actual=?, Stock_Minimo=?, Ubicacion=?, Activo=? WHERE ID_Inventario=?");
            $stmt->bind_param("isiisii", $id_producto, $sku, $stock_actual, $stock_minimo, $ubicacion, $activo, $item_id);

            if ($stmt->execute()) {
                $message = "Ítem actualizado correctamente.";
                $message_type = "success";
                $inventario_item['ID_Producto'] = $id_producto;
                $inventario_item['SKU'] = $sku;
                $inventario_item['Stock_Actual'] = $stock_actual;
                $inventario_item['Stock_Minimo'] = $stock_minimo;
                $inventario_item['Ubicacion'] = $ubicacion;
                $inventario_item['Activo'] = $activo;
                foreach ($products_list as $prod) {
                    if ($prod['ID_Producto'] == $id_producto) {
                        $inventario_item['NombreProducto'] = $prod['Nombre'];
                        break;
                    }
                }
            } else {
                if ($conn->errno == 1062) {
                    throw new Exception("El SKU '" . htmlspecialchars($sku) . "' ya existe.");
                } else {
                    throw new Exception("Error al actualizar: " . $stmt->error);
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

$page_title = 'Editar Ítem de Inventario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?> - Panel Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../css/admin.css">
<style>
    body {font-family: 'Poppins', sans-serif;background:#f5f6fa;margin:0;}
    .admin-content {max-width: 900px;margin:40px auto;background:#fff;padding:35px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.08);}
    .admin-content h3 {font-size:1.8rem;margin-bottom:20px;text-align:center;color:#2c3e50;border-bottom:1px solid #e0e0e0;padding-bottom:10px;}
    .admin-form {display:flex;flex-direction:column;gap:18px;}
    .form-group label {font-weight:500;margin-bottom:8px;color:#34495e;}
    .form-group input,.form-group select {padding:12px;font-size:1rem;border:1px solid #dcdcdc;border-radius:8px;background:#f9f9f9;}
    .form-group input:focus,.form-group select:focus {border-color:#3498db;box-shadow:0 0 8px rgba(52,152,219,0.2);background:#fff;outline:none;}
    .checkbox-group {display:flex;align-items:center;gap:10px;}
    .form-actions {display:flex;justify-content:flex-end;gap:12px;margin-top:20px;}
    .btn {display:inline-flex;align-items:center;gap:8px;padding:12px 20px;font-weight:600;border-radius:8px;text-decoration:none;transition:0.3s;cursor:pointer;}
    .btn-primary {background:#28a745;color:#fff;}
    .btn-primary:hover {background:#218838;}
    .btn-secondary {background:#6c757d;color:#fff;}
    .btn-secondary:hover {background:#5a6268;}
    .alert {padding:12px 18px;border-radius:6px;margin-bottom:20px;font-size:0.95rem;}
    .alert-success {background:#d4edda;color:#155724;border-left:5px solid #28a745;}
    .alert-danger {background:#f8d7da;color:#721c24;border-left:5px solid #dc3545;}
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

                <?php if ($inventario_item): ?>
                    <h3>Editar Ítem: <?php echo htmlspecialchars($inventario_item['NombreProducto']); ?> (SKU: <?php echo htmlspecialchars($inventario_item['SKU']); ?>)</h3>
                    <form method="POST" class="admin-form">
                        <div class="form-group">
                            <label>Producto Asociado:</label>
                            <select name="id_producto" required>
                                <option value="">Seleccione un producto</option>
                                <?php foreach ($products_list as $prod): ?>
                                    <option value="<?php echo $prod['ID_Producto']; ?>" <?php echo ($inventario_item['ID_Producto'] == $prod['ID_Producto'])?'selected':''; ?>>
                                        <?php echo htmlspecialchars($prod['Nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>SKU:</label>
                            <input type="text" name="sku" value="<?php echo htmlspecialchars($inventario_item['SKU']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Stock Actual:</label>
                            <input type="number" name="stock_actual" value="<?php echo htmlspecialchars($inventario_item['Stock_Actual']); ?>" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Stock Mínimo:</label>
                            <input type="number" name="stock_minimo" value="<?php echo htmlspecialchars($inventario_item['Stock_Minimo']); ?>" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Ubicación:</label>
                            <input type="text" name="ubicacion" value="<?php echo htmlspecialchars($inventario_item['Ubicacion']); ?>" required>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="activo" value="1" <?php echo ($inventario_item['Activo'])?'checked':''; ?>>
                            <label>Ítem Activo</label>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                            <a href="inventarioAdmin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
</body>
</html>
<?php if (isset($conn)) $conn->close(); ?>
