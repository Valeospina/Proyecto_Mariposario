<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'DB.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $canjearPuntos = $data['canjearPuntos'] ?? 0;

    $user_id = $_SESSION['user_id'] ?? null;
    $carrito = $_SESSION['carrito'] ?? [];

    if (!$user_id) throw new Exception('Usuario no autenticado.');
    if (empty($carrito)) throw new Exception('El carrito está vacío.');

    $clientId = 'ATaviM4-qfB_deZSXciXwtIalyjoNEseNB0FsCJ2riwp6fLYZzaVKTe4jjoY53IjHJx6UWQy48APsJ_H';
    $secret = 'EEwY88QBM9WlzB1LK6g_03u3kHlwPvnUpL_mp4khsizEgE8NuYYU_cFxs4B57h9jjDO8EsQBD_Z2BeXT';

    function convertirColonesADolares($colones) { return round($colones / 500, 2); }

    $total_colones = 0;
    foreach ($carrito as $item) { $total_colones += $item['precio'] * $item['cantidad']; }

    $descuentoAplicado = 0;
    $puntosCanjeados = 0;

    if ($canjearPuntos) {
        $stmt = $conn->prepare("SELECT Puntos_Actuales FROM Puntos_Usuario WHERE ID_Usuario = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($puntosActuales);
        $stmt->fetch();
        $stmt->close();

        if ($puntosActuales >= 1000) {
            $puntosCanjeados = $puntosActuales;
            $descuentoAplicado = min($puntosActuales, $total_colones);
            $total_colones -= $descuentoAplicado;

            $conn->query("UPDATE Puntos_Usuario SET Puntos_Actuales = 0 WHERE ID_Usuario = $user_id");
            $_SESSION['user_points'] = 0;

            $stmtHist = $conn->prepare("INSERT INTO Historial_Puntos (ID_Usuario, Fecha, Accion, Monto, Descripcion) VALUES (?, NOW(), 'Canjeado', ?, 'Canje de puntos en compra PayPal')");
            $stmtHist->bind_param("ii", $user_id, $descuentoAplicado);
            $stmtHist->execute();
            $stmtHist->close();

            $_SESSION['puntos_canjeados'] = $puntosCanjeados;
            $_SESSION['monto_canjeado'] = $descuentoAplicado;
        }
    }

    $total_usd = convertirColonesADolares($total_colones);

    // Token PayPal
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json", "Accept-Language: en_US"]);
    $token_response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($token_response, true);
    $accessToken = $token_data['access_token'] ?? null;
    if (!$accessToken) throw new Exception('No se pudo obtener token PayPal.');

    // Crear orden
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders");
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

    echo $response;
    exit;

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
