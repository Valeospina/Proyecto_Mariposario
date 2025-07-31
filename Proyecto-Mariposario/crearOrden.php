<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/paypal_errors.log');

include 'DB.php';

$response = ['success' => false, 'error' => 'Error desconocido'];

try {
    // Leer datos enviados por fetch
    $data = json_decode(file_get_contents("php://input"), true);
    $orderID = $data['orderID'] ?? '';
    if (!$orderID) throw new Exception('ID de orden no recibido.');

    // Configuración PayPal
    $clientId = "ATaviM4-qfB_deZSXciXwtIalyjoNEseNB0FsCJ2riwp6fLYZzaVKTe4jjoY53IjHJx6UWQy48APsJ_H";
    $secret = "EEwY88QBM9WlzB1LK6g_03u3kHlwPvnUpL_mp4khsizEgE8NuYYU_cFxs4B57h9jjDO8EsQBD_Z2BeXT";

    // 1. Obtener token PayPal
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api-m.sandbox.paypal.com/v1/oauth2/token",
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_USERPWD => "$clientId:$secret",
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => ["Accept: application/json", "Accept-Language: en_US"]
    ]);
    $responseToken = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($responseToken, true);
    $accessToken = $tokenData['access_token'] ?? null;
    if (!$accessToken) throw new Exception('No se pudo obtener token PayPal.');

    // 2. Capturar orden PayPal
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api-m.sandbox.paypal.com/v2/checkout/orders/$orderID/capture",
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer $accessToken"
        ]
    ]);
    $responsePayPal = curl_exec($ch);
    curl_close($ch);

    $paypalCaptureResult = json_decode($responsePayPal, true);
    if (!isset($paypalCaptureResult['status']) || $paypalCaptureResult['status'] !== 'COMPLETED') {
        throw new Exception('Orden no completada en PayPal.');
    }

    // 3. Validar sesión y carrito
    $user_id = $_SESSION['user_id'] ?? null;
    $cart_data = $_SESSION['carrito'] ?? [];
    if (!$user_id || empty($cart_data)) throw new Exception('Usuario no autenticado o carrito vacío.');

    // 4. Datos adicionales (descuentos)
    $puntos_canjeados = $_SESSION['puntos_canjeados'] ?? 0;
    $monto_canjeado = $_SESSION['monto_canjeado'] ?? 0;

    // Total del carrito
    $total_carrito_final = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $cart_data));

    // 5. Iniciar transacción
    $conn->begin_transaction();

    // 6. Insertar pedido
    $numero_proforma = 'PF' . time();
    $estado_pedido = 'Pago Confirmado';
    $estado_envio = 'Pedido Recibido';
    $observaciones = "Pago PayPal ID: " . $paypalCaptureResult['id'];

    $stmt = $conn->prepare("
        INSERT INTO Pedido 
        (ID_Usuario, Fecha_Pedido, Total_Pedido, Estado_Pedido, Numero_Proforma, Observaciones, Metodo_Pago, Estado_Envio, Puntos_Canjeados, Monto_Canjeado)
        VALUES (?, NOW(), ?, ?, ?, ?, 'PayPal', ?, ?, ?)
    ");
    $stmt->bind_param("idsssiii", $user_id, $total_carrito_final, $estado_pedido, $numero_proforma, $observaciones, $estado_envio, $puntos_canjeados, $monto_canjeado);
    if (!$stmt->execute()) {
        throw new Exception('Error al insertar pedido: ' . $stmt->error);
    }
    $idPedido = $stmt->insert_id;
    $stmt->close();

    // 7. Insertar detalle de productos
    $stmtDet = $conn->prepare("INSERT INTO Pedido_Producto (ID_Pedido, ID_Producto, Cantidad, Precio_Unitario) VALUES (?, ?, ?, ?)");
    foreach ($cart_data as $item) {
        $stmtDet->bind_param("iiid", $idPedido, $item['id'], $item['cantidad'], $item['precio']);
        if (!$stmtDet->execute()) {
            throw new Exception('Error al insertar producto en pedido: ' . $stmtDet->error);
        }
    }
    $stmtDet->close();

    // 8. Calcular puntos ganados
    $totalParaPuntos = max(0, $total_carrito_final - $monto_canjeado);
    $puntos_ganados = floor($totalParaPuntos / 100);

    if ($puntos_ganados > 0) {
        $stmtHist = $conn->prepare("
            INSERT INTO Historial_Puntos (ID_Usuario, Fecha, Accion, Monto, Descripcion, ID_Referencia, Tipo_Referencia)
            VALUES (?, NOW(), 'Ganado', ?, 'Puntos por pedido', ?, 'Pedido')
        ");
        $stmtHist->bind_param("iii", $user_id, $puntos_ganados, $idPedido);
        $stmtHist->execute();
        $stmtHist->close();

        $stmtPts = $conn->prepare("
            INSERT INTO Puntos_Usuario (ID_Usuario, Puntos_Actuales) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE Puntos_Actuales = Puntos_Actuales + VALUES(Puntos_Actuales)
        ");
        $stmtPts->bind_param("ii", $user_id, $puntos_ganados);
        $stmtPts->execute();
        $stmtPts->close();

        $_SESSION['user_points'] = ($_SESSION['user_points'] ?? 0) + $puntos_ganados;
    }

    // Confirmar transacción
    $conn->commit();

    // ✅ Generar y enviar factura con protección para evitar errores fatales
    try {
        $numeroFactura = 'FAC-' . strtoupper(uniqid());
        $rutaFacturaDir = "uploads/facturas/";
        if (!file_exists($rutaFacturaDir)) {
            mkdir($rutaFacturaDir, 0777, true);
        }
        $rutaFactura = $rutaFacturaDir . $numeroFactura . ".pdf";

        require_once __DIR__ . '/FacturaService.php';
        $facturaService = new FacturaService();
        $facturaService->generarFacturaPDF(
            [
                'numero_factura' => $numeroFactura,
                'nombre_cliente' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'fecha' => date('d/m/Y'),
                'subtotal' => $total_carrito_final,
                'descuento' => $monto_canjeado,
                'total' => $total_carrito_final - $monto_canjeado,
                'metodo_pago' => 'PayPal'
            ],
            $cart_data,
            $rutaFactura
        );

        $stmtFactura = $conn->prepare("
            INSERT INTO Factura (ID_Pedido, Subtotal, Total, Metodo_Pago, Numero_Factura, Ruta_PDF_Factura) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        // Variables para bind_param
        $metodoPago = 'PayPal';
        $totalFactura = $total_carrito_final - $monto_canjeado;

        $stmtFactura = $conn->prepare("
            INSERT INTO Factura (ID_Pedido, Subtotal, Total, Metodo_Pago, Numero_Factura, Ruta_PDF_Factura) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmtFactura->bind_param("iddsss", 
            $idPedido, 
            $total_carrito_final, 
            $totalFactura, 
            $metodoPago, 
            $numeroFactura, 
            $rutaFactura
        );

        $stmtFactura->execute();
        $stmtFactura->close();


        require_once __DIR__ . '/FacturaEmailService.php';
        $emailFactura = new FacturaEmailService();
        $emailFactura->enviarFactura(
            [
                'nombre' => $_SESSION['user_name'],
                'email'  => $_SESSION['user_email']
            ],
            $rutaFactura
        );
    } catch (Exception $ex) {
        error_log("Error generando o enviando factura: " . $ex->getMessage());
    }

    // Limpiar carrito y variables temporales
    $_SESSION['carrito'] = [];
    unset($_SESSION['puntos_canjeados'], $_SESSION['monto_canjeado']);

    // Respuesta final
    $response = [
        'success' => true,
        'pedido_id' => $idPedido,
        'numero_proforma' => $numero_proforma,
        'puntos_ganados' => $puntos_ganados,
        'puntos_actualizados' => $_SESSION['user_points']
    ];

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    $response = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($response);
exit;
