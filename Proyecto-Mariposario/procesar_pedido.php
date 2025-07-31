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
        INSERT INTO Pedido (ID_Usuario, Fecha_Pedido, Total_Pedido, Estado_Pedido, Numero_Proforma, Observaciones, Metodo_Pago) 
        VALUES (?, NOW(), ?, ?, ?, ?, ?)
    ");
    $stmtPedido->bind_param("idssss", $idUsuario, $totalPedido, $estadoPedido, $numeroProforma, $observaciones, $metodoPago);
    $stmtPedido->execute();
    $idPedido = $stmtPedido->insert_id;
    $stmtPedido->close();

    // Insertar productos en detalle
    $stmtDetalle = $conn->prepare("INSERT INTO Detalle_Pedido (ID_Pedido, ID_Producto, Cantidad, Precio) VALUES (?, ?, ?, ?)");
    foreach ($carrito as $item) {
        $stmtDetalle->bind_param("iiid", $idPedido, $item['id'], $item['cantidad'], $item['precio']);
        $stmtDetalle->execute();
    }
    $stmtDetalle->close();

    // Si SINPE, guardar comprobante en Factura como evidencia
    if ($metodoPago === 'SINPE Movil' && $rutaComprobante) {
        $stmtFactura = $conn->prepare("
            INSERT INTO Factura (ID_Pedido, Subtotal, Total, Metodo_Pago, Numero_Factura, Ruta_PDF_Factura)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $dummyFactura = 'PENDIENTE-' . strtoupper(uniqid());
        $stmtFactura->bind_param("iddsss", $idPedido, $totalPedido, $totalPedido, $metodoPago, $dummyFactura, $rutaComprobante);
        $stmtFactura->execute();
        $stmtFactura->close();
    }

    $conn->commit();

    // ✅ Generar factura y enviar SOLO si es PayPal o Efectivo
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
            'subtotal' => $totalPedido,
            'descuento' => 0,
            'total' => $totalPedido,
            'metodo_pago' => $metodoPago
        ], $carrito, $rutaFactura);

        // Insertar factura
        $stmtFactura = $conn->prepare("
            UPDATE Factura SET Numero_Factura = ?, Ruta_PDF_Factura = ? WHERE ID_Pedido = ?
        ");
        $stmtFactura->bind_param("ssi", $numeroFactura, $rutaFactura, $idPedido);
        $stmtFactura->execute();
        $stmtFactura->close();

        // Enviar correo
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
    if ($conn) $conn->rollback();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
