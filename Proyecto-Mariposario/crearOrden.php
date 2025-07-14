<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'DB.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$orderID = $data['orderID'] ?? '';

if (!$orderID) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de orden de PayPal no recibido.']);
    exit;
}

$clientId = "ATaviM4-qfB_deZSXciXwtIalyjoNEseNB0FsCJ2riwp6fLYZzaVKTe4jjoY53IjHJx6UWQy48APsJ_H";
$secret = "EEwY88QBM9WlzB1LK6g_03u3kHlwPvnUpL_mp4khsizEgE8NuYYU_cFxs4B57h9jjDO8EsQBD_Z2BeXT";

// Token PayPal
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Accept-Language: en_US"]);
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'] ?? null;

if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Token PayPal no obtenido']);
    exit;
}

// Capturar Orden
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders/$orderID/capture");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $accessToken"]);
$response = curl_exec($ch);
curl_close($ch);

$paypalCaptureResult = json_decode($response, true);

if ($paypalCaptureResult['status'] === 'COMPLETED') {
    $conn->begin_transaction();

    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $cart_data = $_SESSION['carrito'] ?? [];
        if (!$user_id || empty($cart_data)) throw new Exception("Usuario no autenticado o carrito vacío.");

        $total_carrito_final = 0;
        foreach ($cart_data as $item) {
            $total_carrito_final += $item['precio'] * $item['cantidad'];
        }

        $paypal_transaction_id = $paypalCaptureResult['id'];
        $payer_email = $paypalCaptureResult['payer']['email_address'];

        // Insertar en pagos
        $stmt_pagos = $conn->prepare("INSERT INTO pagos (id_transaccion, fecha, status, email, id_cliente, total) VALUES (?, NOW(), ?, ?, ?, ?)");
        $payment_status = 'COMPLETED';
        $stmt_pagos->bind_param("sssid", $paypal_transaction_id, $payment_status, $payer_email, $user_id, $total_carrito_final);
        $stmt_pagos->execute();
        $ID_Pago = $conn->insert_id;
        $stmt_pagos->close();

        // Insertar en Pedido
        $stmt_pedido = $conn->prepare("INSERT INTO Pedido (ID_Usuario, Fecha_Pedido, Total_Pedido, Estado_Pedido, Numero_Proforma, Observaciones, Metodo_Pago, Estado_Envio) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)");
        $numero_proforma = 'PF' . time();
        $estado_pedido = 'Pago Confirmado';
        $observaciones = "Pago PayPal ID: $paypal_transaction_id";
        $metodo_pago = 'PayPal';
        $estado_envio = 'Pedido Recibido';
        $stmt_pedido->bind_param("idsssss", $user_id, $total_carrito_final, $estado_pedido, $numero_proforma, $observaciones, $metodo_pago, $estado_envio);
        $stmt_pedido->execute();
        $ID_Pedido = $conn->insert_id;
        $stmt_pedido->close();

        // Insertar Pedido_Producto
        $stmt_detalle = $conn->prepare("INSERT INTO Pedido_Producto (ID_Pedido, ID_Producto, Cantidad, Precio_Unitario) VALUES (?, ?, ?, ?)");
        foreach ($cart_data as $item) {
            $stmt_detalle->bind_param("iiid", $ID_Pedido, $item['id'], $item['cantidad'], $item['precio']);
            $stmt_detalle->execute();
        }
        $stmt_detalle->close();

        // Calcular puntos
        $puntos_ganados = floor($total_carrito_final / 100);

        // Verificar si el usuario ya tiene puntos registrados
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Puntos_Usuario WHERE ID_Usuario = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $stmt = $conn->prepare("UPDATE Puntos_Usuario SET Puntos_Actuales = Puntos_Actuales + ? WHERE ID_Usuario = ?");
            $stmt->bind_param("ii", $puntos_ganados, $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO Puntos_Usuario (ID_Usuario, Puntos_Actuales) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $puntos_ganados);
            $stmt->execute();
            $stmt->close();
        }

        // Insertar historial
        $desc = "Puntos ganados por pedido #$ID_Pedido";
        $stmt = $conn->prepare("INSERT INTO Historial_Puntos (ID_Usuario, Fecha, Accion, Monto, Descripcion, ID_Referencia, Tipo_Referencia) VALUES (?, NOW(), 'Ganado', ?, ?, ?, 'Pedido')");
        $stmt->bind_param("iisi", $user_id, $puntos_ganados, $desc, $ID_Pedido);
        $stmt->execute();
        $stmt->close();

        $_SESSION['user_points'] = ($_SESSION['user_points'] ?? 0) + $puntos_ganados;
        $_SESSION['carrito'] = [];

        $conn->commit();
        echo json_encode(['success' => true, 'mensaje' => 'Pedido y puntos registrados exitosamente.', 'order_id_db' => $ID_Pedido]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Error al procesar pedido: ' . $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'La orden no fue completada.', 'paypal_status' => $paypalCaptureResult['status']]);
}
