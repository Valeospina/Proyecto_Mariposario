<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Inicializar variables de mensaje
$message = '';
$message_type = '';

// Protección de la página de administración:
if (!isset($_SESSION['user_id'])) {
    header('Location: ../logind.php');
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

$page_title = 'Editar Pedido';
$pedido_id = $_GET['id'] ?? null; // Obtener el ID del pedido de la URL

$pedido_detalle = null;
$historial_estados = [];
$estados_disponibles = ['Pendiente', 'Procesando', 'Enviado', 'Entregado', 'Cancelado']; // Estados posibles

if (!$pedido_id || !is_numeric($pedido_id)) {
    $message = "ID de pedido no válido.";
    $message_type = "danger";
    header('Location: pedidos.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
    exit;
}

try {
    if (isset($conn) && $conn instanceof mysqli) {
        // Obtener detalles del pedido
        $stmt = $conn->prepare("
            SELECT p.ID_Pedido, p.ID_Usuario, p.Fecha_Pedido, p.Metodo_Pago, p.Total_Pedido, 
                u.Nombre AS Nombre_Usuario, u.Correo
            FROM Pedido p 
            JOIN Usuario u ON p.ID_Usuario = u.ID_Usuario
            WHERE p.ID_Pedido = ?

        ");
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $pedido_detalle = $result->fetch_assoc();
        } else {
            $message = "Pedido no encontrado.";
            $message_type = "danger";
        }
        $stmt->close();

        // Obtener historial de estados
        $stmt = $conn->prepare("SELECT Estado, Fecha FROM Estado_Pedido WHERE ID_Pedido = ? ORDER BY Fecha DESC");
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $historial_estados[] = $row;
        }
        $stmt->close();

        // --- Lógica para actualizar el estado del pedido ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
            $new_status = $_POST['new_status'];

            if (!in_array($new_status, $estados_disponibles)) {
                $message = "Estado seleccionado no válido.";
                $message_type = "danger";
            } else {
                // Insertar nuevo estado
                $stmt = $conn->prepare("INSERT INTO Estado_Pedido (ID_Pedido, Estado, Fecha) VALUES (?, ?, NOW())");
                $stmt->bind_param("is", $pedido_id, $new_status);

                if ($stmt->execute()) {
                    $message = "Estado del pedido actualizado exitosamente a '" . htmlspecialchars($new_status) . "'.";
                    $message_type = "success";

                if ($new_status === 'Procesando' && $pedido_detalle['Metodo_Pago'] === 'SINPE Movil') {
                    $checkFactura = $conn->prepare("SELECT Ruta_PDF_Factura FROM Factura WHERE ID_Pedido = ?");
                    $checkFactura->bind_param("i", $pedido_id);
                    $checkFactura->execute();
                    $facturaData = $checkFactura->get_result()->fetch_assoc();
                    $checkFactura->close();

                    if (empty($facturaData['Ruta_PDF_Factura']) || strpos($facturaData['Ruta_PDF_Factura'], 'uploads/comprobantes') !== false) {
                        require_once __DIR__ . '/../FacturaService.php';
                        $numeroFactura = 'FAC-' . strtoupper(uniqid());
                        $rutaFacturaDir = "../uploads/facturas/";
                        if (!file_exists($rutaFacturaDir)) mkdir($rutaFacturaDir, 0777, true);
                        $rutaFactura = $rutaFacturaDir . $numeroFactura . ".pdf";

                        //  Obtener productos del pedido
                        $productos_pedido = [];
                        $stmtProd = $conn->prepare("
                            SELECT p.Nombre AS nombre, dp.Cantidad AS cantidad, dp.Precio AS precio
                            FROM Detalle_Pedido dp
                            JOIN Producto p ON dp.ID_Producto = p.ID_Producto
                            WHERE dp.ID_Pedido = ?
                        ");
                        $stmtProd->bind_param("i", $pedido_id);
                        $stmtProd->execute();
                        $resProd = $stmtProd->get_result();
                        while ($row = $resProd->fetch_assoc()) {
                            $productos_pedido[] = $row;
                        }
                        $stmtProd->close();

                        // Generar factura con productos
                        $stmtExtra = $conn->prepare("SELECT Total_Pedido, Monto_Canjeado FROM Pedido WHERE ID_Pedido = ?");
                        $stmtExtra->bind_param("i", $pedido_id);
                        $stmtExtra->execute();
                        $stmtExtra->bind_result($totalPedido, $montoCanjeado);
                        $stmtExtra->fetch();
                        $stmtExtra->close();

                        // Calcular subtotal original (precio antes del descuento)
                        $subtotalOriginal = $totalPedido + $montoCanjeado;

                        // Generar factura con datos correctos
                        $facturaService = new FacturaService();
                        $facturaService->generarFacturaPDF(
                            [
                                'numero_factura' => $numeroFactura,
                                'nombre_cliente' => $pedido_detalle['Nombre_Usuario'],
                                'email'          => $pedido_detalle['Correo'],
                                'fecha'          => date('d/m/Y'),
                                'subtotal'       => $subtotalOriginal,
                                'descuento'      => $montoCanjeado,
                                'total'          => $totalPedido,
                                'metodo_pago'    => $pedido_detalle['Metodo_Pago']
                            ],
                            $productos_pedido, 
                            $rutaFactura
                        );

                        //  Actualizar Factura
                        $stmtUpdFactura = $conn->prepare("UPDATE Factura SET Numero_Factura = ?, Ruta_PDF_Factura = ? WHERE ID_Pedido = ?");
                        $stmtUpdFactura->bind_param("ssi", $numeroFactura, $rutaFactura, $pedido_id);
                        $stmtUpdFactura->execute();
                        $stmtUpdFactura->close();

                        //  Enviar email con la factura
                        if (!empty($pedido_detalle['Correo'])) {
                            require_once __DIR__ . '/../FacturaEmailService.php';
                            $emailFactura = new FacturaEmailService();
                            $emailFactura->enviarFactura(
                                ['nombre' => $pedido_detalle['Nombre_Usuario'], 'email' => $pedido_detalle['Correo']],
                                $rutaFactura
                            );
                        }
                    }
                }


                    // Recargar historial
                    $historial_estados = [];
                    $stmt_reload = $conn->prepare("SELECT Estado, Fecha FROM Estado_Pedido WHERE ID_Pedido = ? ORDER BY Fecha DESC");
                    $stmt_reload->bind_param("i", $pedido_id);
                    $stmt_reload->execute();
                    $result_reload = $stmt_reload->get_result();
                    while ($row = $result_reload->fetch_assoc()) {
                        $historial_estados[] = $row;
                    }
                    $stmt_reload->close();
                } else {
                    $message = "Error al actualizar el estado del pedido: " . $stmt->error;
                    $message_type = "danger";
                }
                $stmt->close();
            }
        }
    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception $e) {
    error_log("Error al cargar/actualizar pedido: " . $e->getMessage());
    $message = "Error: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// Lógica para mostrar mensajes de redirección (si los hay)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

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
    <style>
        .order-details-card {background:#fff;padding:25px;border-radius:8px;box-shadow:0 4px 15px rgba(0,0,0,0.08);margin-bottom:30px;}
        .order-details-card h3 {margin-top:0;color:#333;border-bottom:2px solid #eee;padding-bottom:10px;margin-bottom:20px;}
        .order-details-card p {margin-bottom:10px;font-size:1.1em;color:#555;}
        .status-update-form {background:#f9f9f9;padding:20px;border-radius:8px;border:1px solid #ddd;margin-top:30px;}
        .status-update-form select {width:100%;padding:10px;border-radius:5px;border:1px solid #ccc;margin-bottom:15px;font-size:1em;}
        .status-update-form button {background:#28a745;color:#fff;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;}
        .status-history {margin-top:30px;}
        .status-history ul {list-style:none;padding:0;}
        .status-history ul li {background:#fff;border:1px solid #e0e0e0;border-radius:5px;padding:10px 15px;margin-bottom:10px;display:flex;justify-content:space-between;}
        .back-button-container {margin-top:20px;text-align:right;}
        .btn-back {background:#6c757d;color:#fff;padding:10px 20px;border:none;border-radius:5px;text-decoration:none;}
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
                        <li><a href="admin-chats.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-chats.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Soporte</a></li>  
                    </ul>
                </div>
            </nav>
        <div class="sidebar-footer"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></div>
    </aside>
    <div class="main-panel">
        <header class="main-panel-header"><h2><?php echo $page_title; ?></h2></header>
        <main class="content-area">
            <div class="admin-content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <?php if ($pedido_detalle): ?>
                    <div class="order-details-card">
                        <h3>Detalles del Pedido #<?php echo htmlspecialchars($pedido_detalle['ID_Pedido']); ?></h3>
                        <p><strong>Usuario:</strong> <?php echo htmlspecialchars($pedido_detalle['Nombre_Usuario']); ?></p>
                        <p><strong>Fecha del Pedido:</strong> <?php echo htmlspecialchars($pedido_detalle['Fecha_Pedido']); ?></p>
                        <p><strong>Método de Pago:</strong> <?php echo htmlspecialchars($pedido_detalle['Metodo_Pago']); ?></p>
                        <p><strong>Total:</strong> ₡<?php echo htmlspecialchars($pedido_detalle['Total_Pedido']); ?></p>
                    </div>

                    <div class="status-update-form">
                        <h3>Actualizar Estado del Pedido</h3>
                        <form action="edit_pedido.php?id=<?php echo htmlspecialchars($pedido_id); ?>" method="POST">
                            <label for="new_status">Seleccionar nuevo estado:</label>
                            <select name="new_status" id="new_status" required>
                                <?php foreach ($estados_disponibles as $estado): ?>
                                    <option value="<?php echo htmlspecialchars($estado); ?>"
                                        <?php echo (!empty($historial_estados) && $historial_estados[0]['Estado'] == $estado) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($estado); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">Guardar Estado</button>
                        </form>
                    </div>

                    <div class="status-history">
                        <h3>Historial de Estados</h3>
                        <?php if (!empty($historial_estados)): ?>
                            <ul>
                                <?php foreach ($historial_estados as $estado_entry): ?>
                                    <li>
                                        <span><strong>Estado:</strong> <?php echo htmlspecialchars($estado_entry['Estado']); ?></span>
                                        <span><strong>Fecha:</strong> <?php echo htmlspecialchars($estado_entry['Fecha']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No hay historial de estados para este pedido.</p>
                        <?php endif; ?>
                    </div>
                    <div class="back-button-container">
                        <a href="pedidos.php" class="btn-back">Volver al Listado de Pedidos</a>
                    </div>
                <?php else: ?>
                    <p>No se pudo cargar la información del pedido.</p>
                    <div class="back-button-container">
                        <a href="pedidos.php" class="btn-back">Volver</a>
                    </div>
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
