<?php
header('Content-Type: application/json');
session_start();
include 'DB.php';

$response = ['success' => false, 'message' => 'Error desconocido'];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuario no autenticado.');
    }
    if (empty($_SESSION['carrito'])) {
        throw new Exception('El carrito está vacío.');
    }

    $idUsuario = $_SESSION['user_id'];
    $carrito = $_SESSION['carrito'];

    $totalPedido = 0;
    foreach ($carrito as $item) {
        $totalPedido += $item['precio'] * $item['cantidad'];
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    $action = $_POST['action'] ?? '';
    if ($action !== 'manual_order_complete') {
        throw new Exception('Acción inválida.');
    }

    $metodoPago = $_POST['metodo_pago_final'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $canjearPuntos = ($_POST['canjearPuntos'] ?? '0') === '1';

    if (!in_array($metodoPago, ['Efectivo Tienda', 'SINPE Movil', 'PayPal'])) {
        throw new Exception('Método de pago no válido.');
    }

    // --- BLOQUE PARA CANJE DE PUNTOS ---
    $puntosCanjeados = 0;
    $montoCanjeado = 0;
    $subtotalOriginal = $totalPedido; // Guardamos para la factura

    if ($canjearPuntos) {
        // Consultar puntos actuales del usuario
        $stmt = $conn->prepare("SELECT Puntos_Actuales FROM Puntos_Usuario WHERE ID_Usuario = ?");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $stmt->bind_result($puntosActuales);
        $stmt->fetch();
        $stmt->close();

        if ($puntosActuales >= 1000) {
            // Determinar cuánto se puede canjear
            $puntosCanjeados = min($puntosActuales, $totalPedido);
            $montoCanjeado = $puntosCanjeados;
            $totalPedido -= $montoCanjeado;

            // Actualizar puntos del usuario
            $stmt = $conn->prepare("UPDATE Puntos_Usuario SET Puntos_Actuales = Puntos_Actuales - ? WHERE ID_Usuario = ?");
            $stmt->bind_param("ii", $puntosCanjeados, $idUsuario);
            $stmt->execute();
            $stmt->close();

            // Registrar historial del canje
            $stmtHist = $conn->prepare("INSERT INTO Historial_Puntos (ID_Usuario, Fecha, Accion, Monto, Descripcion) VALUES (?, NOW(), 'Canjeado', ?, 'Canje de puntos en compra SINPE/Efectivo')");
            $stmtHist->bind_param("ii", $idUsuario, $puntosCanjeados);
            $stmtHist->execute();
            $stmtHist->close();

            // Actualizar variable en sesión
            $_SESSION['user_points'] = max(0, $puntosActuales - $puntosCanjeados);
        }
    }

    // --- Comprobante SINPE ---
    $rutaComprobante = null;
    if ($metodoPago === 'SINPE Movil') {
        if (!isset($_FILES['comprobanteSinpe']) || $_FILES['comprobanteSinpe']['error'] !== 0) {
            throw new Exception('El comprobante es obligatorio para SINPE.');
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($_FILES['comprobanteSinpe']['type'], $allowedTypes)) {
            throw new Exception('Formato de archivo no permitido.');
        }

        $targetDir = "uploads/comprobantes/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = uniqid() . "_" . basename($_FILES['comprobanteSinpe']['name']);
        $rutaComprobante = $targetDir . $fileName;

        if (!move_uploaded_file($_FILES['comprobanteSinpe']['tmp_name'], $rutaComprobante)) {
            throw new Exception('Error al subir el comprobante.');
        }
    }

    $numeroProforma = 'PROF-' . strtoupper(uniqid());
    $conn->begin_transaction();

    // Insertar pedido
    $estadoPedido = ($metodoPago === 'SINPE Movil') ? 'Pendiente de verificación' : 'Pendiente de Pago';
    $stmtPedido = $conn->prepare("
        INSERT INTO Pedido (ID_Usuario, Fecha_Pedido, Total_Pedido, Estado_Pedido, Numero_Proforma, Observaciones, Metodo_Pago, Puntos_Canjeados, Monto_Canjeado) 
        VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtPedido->bind_param("idssssii", $idUsuario, $totalPedido, $estadoPedido, $numeroProforma, $observaciones, $metodoPago, $puntosCanjeados, $montoCanjeado);
    $stmtPedido->execute();
    $idPedido = $stmtPedido->insert_id;
    $stmtPedido->close();

    // Insertar detalle productos
    $stmtDetalle = $conn->prepare("INSERT INTO Detalle_Pedido (ID_Pedido, ID_Producto, Cantidad, Precio) VALUES (?, ?, ?, ?)");
    foreach ($carrito as $item) {
        $idProducto = $item['id'];
        $cantidad = $item['cantidad'];
        $precio = $item['precio'];
        $stmtDetalle->bind_param("iiid", $idPedido, $idProducto, $cantidad, $precio);
        $stmtDetalle->execute();
    }
    $stmtDetalle->close();

    // Factura para SINPE
    if ($metodoPago === 'SINPE Movil' && $rutaComprobante) {
        $stmtFactura = $conn->prepare("
            INSERT INTO Factura (ID_Pedido, Subtotal, Total, Metodo_Pago, Numero_Factura, Ruta_PDF_Factura)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $dummyFactura = 'PENDIENTE-' . strtoupper(uniqid());
        $subtotalOriginal = $totalPedido + $montoCanjeado;
        $stmtFactura->bind_param("iddsss", $idPedido, $subtotalOriginal, $totalPedido, $metodoPago, $dummyFactura, $rutaComprobante);
        $stmtFactura->execute();
        $stmtFactura->close();
    }

    $conn->commit();

// ✅ SUMAR PUNTOS DESPUÉS DE LA COMPRA (solo SINPE o Efectivo)
if (in_array($metodoPago, ['SINPE Movil', 'Efectivo Tienda'])) {
    // Calcular puntos sobre el monto final (después del canje)
    $totalParaPuntos = max(0, $totalPedido);
    $puntosGanados = floor($totalParaPuntos / 100); // 1 punto por cada 100 colones

    if ($puntosGanados > 0) {
        // Registrar en historial
        $stmtHist = $conn->prepare("
            INSERT INTO Historial_Puntos 
            (ID_Usuario, Fecha, Accion, Monto, Descripcion, ID_Referencia, Tipo_Referencia) 
            VALUES (?, NOW(), 'Ganado', ?, 'Puntos por pedido SINPE/Efectivo', ?, 'Pedido')
        ");
        $stmtHist->bind_param("iii", $idUsuario, $puntosGanados, $idPedido);
        $stmtHist->execute();
        $stmtHist->close();

        // Actualizar tabla Puntos_Usuario
        $stmtPts = $conn->prepare("
            INSERT INTO Puntos_Usuario (ID_Usuario, Puntos_Actuales) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE Puntos_Actuales = Puntos_Actuales + VALUES(Puntos_Actuales)
        ");
        $stmtPts->bind_param("ii", $idUsuario, $puntosGanados);
        $stmtPts->execute();
        $stmtPts->close();

        // Actualizar puntos en sesión
        $_SESSION['user_points'] = ($_SESSION['user_points'] ?? 0) + $puntosGanados;
    }
}

// ✅ Generar factura y enviar SOLO si es efectivo (no SINPE)
if ($metodoPago !== 'SINPE Movil') {
    require_once __DIR__ . '/FacturaService.php';
    $numeroFactura = 'FAC-' . strtoupper(uniqid());
    $rutaFacturaDir = "uploads/facturas/";
    if (!file_exists($rutaFacturaDir)) mkdir($rutaFacturaDir, 0777, true);
    $rutaFactura = $rutaFacturaDir . $numeroFactura . ".pdf";

    $facturaService = new FacturaService();
    $facturaService->generarFacturaPDF([
        'numero_factura' => $numeroFactura,
        'nombre_cliente' => $_SESSION['user_name'] ?? 'Cliente',
        'email' => $_SESSION['user_email'] ?? 'sin-correo@dominio.com',
        'fecha' => date('d/m/Y'),
        'subtotal' => $subtotalOriginal,
        'descuento' => $montoCanjeado,
        'total' => $totalPedido,
        'metodo_pago' => $metodoPago
    ], $carrito, $rutaFactura);

    $stmtFactura = $conn->prepare("UPDATE Factura SET Numero_Factura = ?, Ruta_PDF_Factura = ? WHERE ID_Pedido = ?");
    $stmtFactura->bind_param("ssi", $numeroFactura, $rutaFactura, $idPedido);
    $stmtFactura->execute();
    $stmtFactura->close();

    require_once __DIR__ . '/FacturaEmailService.php';
    $emailFactura = new FacturaEmailService();
    $emailFactura->enviarFactura(['nombre' => $_SESSION['user_name'], 'email' => $_SESSION['user_email']], $rutaFactura);
}

unset($_SESSION['carrito']);
$response['success'] = true;
$response['message'] = ($metodoPago === 'SINPE Movil') 
    ? 'Pedido registrado. Comprobante en revisión por el administrador.' 
    : 'Pedido creado exitosamente. Factura enviada.';
$response['pedido_id'] = $idPedido;

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>