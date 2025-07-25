<?php
header('Content-Type: application/json');
session_start();
include 'DB.php'; // Este archivo debe crear la conexión $conn (MySQLi)

$response = ['success' => false, 'message' => 'Error desconocido'];

try {
    // 1. Validar sesión y carrito
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuario no autenticado.');
    }
    if (empty($_SESSION['carrito'])) {
        throw new Exception('El carrito está vacío.');
    }

    $idUsuario = $_SESSION['user_id'];
    $carrito = $_SESSION['carrito'];

    // 2. Calcular total del pedido antes del descuento
    $totalPedido = 0;
    foreach ($carrito as $item) {
        $totalPedido += $item['precio'] * $item['cantidad'];
    }

    // 3. Validar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    $action = $_POST['action'] ?? '';
    if ($action !== 'manual_order_complete') {
        throw new Exception('Acción inválida.');
    }

    // 4. Capturar datos enviados
    $metodoPago = $_POST['metodo_pago_final'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $canjearPuntos = ($_POST['canjearPuntos'] ?? '0') === '1';

    if (!in_array($metodoPago, ['Efectivo Tienda', 'SINPE Movil', 'PayPal'])) {
        throw new Exception('Método de pago no válido.');
    }

    // 5. Validar comprobante SINPE
    $rutaComprobante = null;
    if ($metodoPago === 'SINPE Movil') {
        if (!isset($_FILES['comprobanteSinpe']) || $_FILES['comprobanteSinpe']['error'] !== 0) {
            throw new Exception('El comprobante es obligatorio para SINPE.');
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($_FILES['comprobanteSinpe']['type'], $allowedTypes)) {
            throw new Exception('Formato de archivo no permitido. Solo JPG, PNG o PDF.');
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

    // 6. Generar proforma única
    $numeroProforma = 'PROF-' . strtoupper(uniqid());

    // 7. Iniciar transacción
    $conn->begin_transaction();

    // 8. Aplicar descuento por puntos si corresponde
    $descuentoAplicado = 0;
    if ($canjearPuntos) {
        // Obtener puntos actuales del usuario
        $stmtCheck = $conn->prepare("SELECT Puntos_Actuales FROM Puntos_Usuario WHERE ID_Usuario = ?");
        $stmtCheck->bind_param("i", $idUsuario);
        $stmtCheck->execute();
        $stmtCheck->bind_result($puntosActuales);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($puntosActuales >= 1000) {
            // Calcular el descuento aplicable
            $descuentoAplicado = min($puntosActuales, $totalPedido); // No excede el total del pedido
            $totalPedido -= $descuentoAplicado;

            // ✅ Dejar puntos en 0 después del canje
            $stmtUpdate = $conn->prepare("UPDATE Puntos_Usuario SET Puntos_Actuales = 0 WHERE ID_Usuario = ?");
            $stmtUpdate->bind_param("i", $idUsuario);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            // ✅ Actualizar la sesión para reflejar visualmente 0 puntos
            $_SESSION['user_points'] = 0;

            // Registrar en historial
            $stmtHist = $conn->prepare("
                INSERT INTO Historial_Puntos (ID_Usuario, Fecha, Accion, Monto, Descripcion)
                VALUES (?, NOW(), 'Canjeado', ?, 'Canje de puntos en compra')
            ");
            $stmtHist->bind_param("ii", $idUsuario, $descuentoAplicado);
            $stmtHist->execute();
            $stmtHist->close();
        }
    }

    // 9. Insertar en Pedido con el total ya actualizado
    $estadoPedido = 'Pendiente de Pago';
    $stmtPedido = $conn->prepare("
        INSERT INTO Pedido (ID_Usuario, Fecha_Pedido, Total_Pedido, Estado_Pedido, Numero_Proforma, Observaciones, Metodo_Pago) 
        VALUES (?, NOW(), ?, ?, ?, ?, ?)
    ");
    $stmtPedido->bind_param("idssss", $idUsuario, $totalPedido, $estadoPedido, $numeroProforma, $observaciones, $metodoPago);
    if (!$stmtPedido->execute()) {
        throw new Exception('Error al crear el pedido: ' . $stmtPedido->error);
    }
    $idPedido = $stmtPedido->insert_id;
    $stmtPedido->close();

    // 10. Insertar productos en Detalle_Pedido
    $stmtDetalle = $conn->prepare("INSERT INTO Detalle_Pedido (ID_Pedido, ID_Producto, Cantidad, Precio) VALUES (?, ?, ?, ?)");
    foreach ($carrito as $item) {
        $stmtDetalle->bind_param("iiid", $idPedido, $item['id'], $item['cantidad'], $item['precio']);
        if (!$stmtDetalle->execute()) {
            throw new Exception('Error al insertar detalle: ' . $stmtDetalle->error);
        }
    }
    $stmtDetalle->close();

    // 11. Insertar comprobante si es SINPE
    if ($metodoPago === 'SINPE Movil' && $rutaComprobante) {
        $stmtFactura = $conn->prepare("INSERT INTO Factura (ID_Pedido, Ruta_PDF_Factura) VALUES (?, ?)");
        $stmtFactura->bind_param("is", $idPedido, $rutaComprobante);
        if (!$stmtFactura->execute()) {
            throw new Exception('Error al guardar el comprobante: ' . $stmtFactura->error);
        }
        $stmtFactura->close();
    }

    // 12. Insertar estado inicial en Estado_Pedido
    $estadoInicial = 'Pendiente';
    $stmtEstado = $conn->prepare("INSERT INTO Estado_Pedido (ID_Pedido, Estado, Fecha) VALUES (?, ?, NOW())");
    $stmtEstado->bind_param("is", $idPedido, $estadoInicial);
    if (!$stmtEstado->execute()) {
        throw new Exception('Error al insertar el estado inicial: ' . $stmtEstado->error);
    }
    $stmtEstado->close();

    // 13. Confirmar transacción
    $conn->commit();

    // 14. Vaciar carrito
    unset($_SESSION['carrito']);

    // ✅ Respuesta con datos adicionales
    $response['success'] = true;
    $response['message'] = 'Pedido creado exitosamente.';
    $response['pedido_id'] = $idPedido;
    $response['numero_proforma'] = $numeroProforma;
    $response['total_final'] = $totalPedido;
    $response['descuento_aplicado'] = $descuentoAplicado;
    $response['puntos_restantes'] = 0; // ✅ Siempre 0 tras el canje completo

} catch (Exception $e) {
    if ($conn) {
        $conn->rollback();
    }
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
