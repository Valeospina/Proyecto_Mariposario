<?php
session_start();
include '../DB.php'; // Archivo de conexión a la base de datos

// Protección: solo admin puede acceder
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../logind.php');
    exit;
}

$message = '';
$message_type = '';
$products_list = []; // Para llenar el selector de productos

// Obtener la lista de productos para el dropdown
try {
    if ($conn instanceof mysqli) {
        $products_result = $conn->query("SELECT ID_Producto, Nombre FROM Producto ORDER BY Nombre");
        if ($products_result) {
            while ($row = $products_result->fetch_assoc()) {
                $products_list[] = $row;
            }
            $products_result->free();
        } else {
            throw new Exception("Error al obtener la lista de productos: " . $conn->error);
        }
    } else {
        throw new Exception("Conexión a la base de datos no válida o no disponible.");
    }
} catch (Exception $e) {
    error_log("Error al cargar lista de productos para inventario: " . $e->getMessage());
    $message = "Error al cargar la lista de productos: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// Si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_producto = filter_var($_POST['id_producto'] ?? '', FILTER_VALIDATE_INT);
    $sku = trim($_POST['sku'] ?? '');
    $stock_actual = filter_var($_POST['stock_actual'] ?? '', FILTER_VALIDATE_INT);
    $stock_minimo = filter_var($_POST['stock_minimo'] ?? '', FILTER_VALIDATE_INT);
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0; // Checkbox

    // Validaciones básicas
    if ($id_producto === false || $id_producto <= 0 || empty($sku) || $stock_actual === false || $stock_actual < 0 || $stock_minimo === false || $stock_minimo < 0 || empty($ubicacion)) {
        $message = "Todos los campos obligatorios deben ser completados correctamente.";
        $message_type = "danger";
    } else {
        try {
            if ($conn instanceof mysqli) {
                // Prepara la consulta SQL para insertar un nuevo ítem de inventario
                $stmt = $conn->prepare("INSERT INTO Inventario (ID_Producto, SKU, Stock_Actual, Stock_Minimo, Ubicacion, Activo) VALUES (?, ?, ?, ?, ?, ?)");

                if (!$stmt) {
                    throw new Exception("Error al preparar la consulta: " . $conn->error);
                }

                $stmt->bind_param("isiisi", $id_producto, $sku, $stock_actual, $stock_minimo, $ubicacion, $activo);

                if ($stmt->execute()) {
                    $message = "Ítem de inventario (SKU: <strong>" . htmlspecialchars($sku) . "</strong>) añadido exitosamente.";
                    $message_type = "success";
                    // Redirigir para limpiar el POST y mostrar el mensaje
                    header('Location: inventarioAdmin.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                    exit;
                } else {
                    // Manejo de error para SKU duplicado u otros errores de BD
                    if ($conn->errno == 1062) { // Código de error para entrada duplicada (SKU)
                         throw new Exception("El SKU '" . htmlspecialchars($sku) . "' ya existe. Por favor, usa uno diferente.");
                    } else {
                         throw new Exception("Error al añadir el ítem de inventario: " . $stmt->error);
                    }
                }
                $stmt->close();
            } else {
                throw new Exception("Conexión a la base de datos no válida o no disponible.");
            }
        } catch (Exception $e) {
            error_log("Error al añadir ítem de inventario: " . $e->getMessage());
            $message = "Error al añadir el ítem de inventario: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

$page_title = 'Añadir Nuevo Ítem de Inventario';
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
                    <li><a href="products.php"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="inventarioAdmin.php" class="active"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
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

                    <h3>Formulario para Añadir Ítem de Inventario</h3>
                    <form action="add_inventario.php" method="POST" class="admin-form">
                        <div class="form-group">
                            <label for="id_producto">Producto Asociado:</label>
                            <select id="id_producto" name="id_producto" required>
                                <option value="">Seleccione un producto</option>
                                <?php foreach ($products_list as $prod): ?>
                                    <option value="<?php echo htmlspecialchars($prod['ID_Producto']); ?>"
                                        <?php echo (isset($_POST['id_producto']) && $_POST['id_producto'] == $prod['ID_Producto']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prod['Nombre']); ?> (ID: <?php echo htmlspecialchars($prod['ID_Producto']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($products_list) && empty($message)): ?>
                                <p class="alert alert-info" style="margin-top: 10px;">No hay productos registrados. Por favor, <a href="add_product.php">añade un producto primero</a>.</p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="sku">SKU (Stock Keeping Unit):</label>
                            <input type="text" id="sku" name="sku" required value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                            <small>Identificador único para este ítem específico de inventario.</small>
                        </div>

                        <div class="form-group">
                            <label for="stock_actual">Stock Actual:</label>
                            <input type="number" id="stock_actual" name="stock_actual" min="0" required value="<?php echo htmlspecialchars($_POST['stock_actual'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="stock_minimo">Stock Mínimo:</label>
                            <input type="number" id="stock_minimo" name="stock_minimo" min="0" required value="<?php echo htmlspecialchars($_POST['stock_minimo'] ?? ''); ?>">
                            <small>Nivel de stock para alertar de reabastecimiento.</small>
                        </div>

                        <div class="form-group">
                            <label for="ubicacion">Ubicación:</label>
                            <input type="text" id="ubicacion" name="ubicacion" required value="<?php echo htmlspecialchars($_POST['ubicacion'] ?? ''); ?>">
                            <small>Ej: Almacén A, Estantería 3</small>
                        </div>

                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="activo" name="activo" value="1" <?php echo (isset($_POST['activo']) && $_POST['activo'] == '1') ? 'checked' : 'checked'; ?>>
                            <label for="activo">Ítem de Inventario Activo</label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Añadir Ítem</button>
                            <a href="inventarioAdmin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a Inventario</a>
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