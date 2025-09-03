<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/paypal_errors.log');

include 'DB.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $canjearPuntos = $data['canjearPuntos'] ?? 0;

    $user_id = $_SESSION['user_id'] ?? null;
    $carrito = $_SESSION['carrito'] ?? [];

    if (!$user_id) throw new Exception('Usuario no autenticado.');
    if (empty($carrito)) throw new Exception('El carrito está vacío.');

    // 🔹 CONFIGURACIÓN PAYPAL
    $paypalEnv = "live"; // 👉 cambia a "sandbox" para pruebas
    if ($paypalEnv === "live") {
        $clientId = "AcLsA6mYTt4Ud_AAvOaBws5g158MYs0mkIS_Ldd5FCKmSvOwnMZIrzv9dmzU9Uzso-Qlj9ghp0ICbOe4";
        $secret   = "EAWb1amyVFvpwAtlnAYXv6AA7UkMsuL0pnErh7expMsgaRkAtdS549fQrKBimPBjegyImes0cwdvmTBb";
        $baseUrl  = "https://api-m.paypal.com";
    } else {
        $clientId = "TU_CLIENT_ID_SANDBOX";
        $secret   = "TU_SECRET_SANDBOX";
        $baseUrl  = "https://api-m.sandbox.paypal.com";
    }

    function convertirColonesADolares($colones) { return round($colones / 500, 2); }

    // 🔹 Total carrito
    $total_colones = 0;
    foreach ($carrito as $item) {
        $total_colones += $item['precio'] * $item['cantidad'];
    }

    // 🔹 Puntos / descuentos
    $descuentoAplicado = 0;
    if ($canjearPuntos) {
        $stmt = $conn->prepare("SELECT Puntos_Actuales FROM Puntos_Usuario WHERE ID_Usuario = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($puntosActuales);
        $stmt->fetch();
        $stmt->close();

        if ($puntosActuales >= 1000) {
            $descuentoAplicado = min($puntosActuales, $total_colones);
            $total_colones -= $descuentoAplicado;

            $conn->query("UPDATE Puntos_Usuario SET Puntos_Actuales = 0 WHERE ID_Usuario = $user_id");
            $_SESSION['user_points'] = 0;

            $stmtHist = $conn->prepare("
                INSERT INTO Historial_Puntos (ID_Usuario, Fecha, Accion, Monto, Descripcion) 
                VALUES (?, NOW(), 'Canjeado', ?, 'Canje de puntos en compra PayPal')
            ");
            $stmtHist->bind_param("ii", $user_id, $descuentoAplicado);
            $stmtHist->execute();
            $stmtHist->close();

            $_SESSION['puntos_canjeados'] = $puntosActuales;
            $_SESSION['monto_canjeado']   = $descuentoAplicado;
        }
    }

    $total_usd = convertirColonesADolares($total_colones);

    // 🔹 TOKEN PAYPAL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Accept-Language: en_US"]);
    $token_response = curl_exec($ch);
    curl_close($ch);

    error_log("TOKEN RESPONSE: " . $token_response);

    $token_data = json_decode($token_response, true);
    $accessToken = $token_data['access_token'] ?? null;
    if (!$accessToken) {
        throw new Exception('No se pudo obtener token PayPal: ' . json_encode($token_data));
    }

    // 🔹 CREAR ORDEN
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/v2/checkout/orders");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $accessToken"
    ]);
    $orderData = [
        "intent" => "CAPTURE",
        "purchase_units" => [[
            "amount" => [
                "currency_code" => "USD",
                "value" => number_format($total_usd, 2, '.', '')
            ]
        ]]
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    $response = curl_exec($ch);
    curl_close($ch);

    error_log("ORDER RESPONSE: " . $response);

    $responseData = json_decode($response, true);

    if (isset($responseData['id'])) {
        echo json_encode([
            'id' => $responseData['id'],
            'status' => $responseData['status'] ?? null
        ]);
    } else {
        echo json_encode([
            'error' => $responseData['error'] ?? $responseData['message'] ?? 'No se pudo crear la orden'
        ]);
    }
    exit;

} catch (Exception $e) {
    error_log("EXCEPTION: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
