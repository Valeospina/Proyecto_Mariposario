<?php
session_start();
header('Content-Type: application/json');
include 'DB.php'; // Conexión a la base de datos

// Leer datos del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);
$canjearPuntos = $data['canjearPuntos'] ?? 0;

$user_id = $_SESSION['user_id'] ?? null;
$carrito = $_SESSION['carrito'] ?? [];

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario no autenticado.']);
    exit;
}

if (empty($carrito)) {
    http_response_code(400);
    echo json_encode(['error' => 'El carrito está vacío.']);
    exit;
}

// --- Configuración de PayPal ---
$clientId = 'ATaviM4-qfB_deZSXciXwtIalyjoNEseNB0FsCJ2riwp6fLYZzaVKTe4jjoY53IjHJx6UWQy48APsJ_H';
$secret = 'EEwY88QBM9WlzB1LK6g_03u3kHlwPvnUpL_mp4khsizEgE8NuYYU_cFxs4B57h9jjDO8EsQBD_Z2BeXT';

function convertirColonesADolares($colones) {
    $tipoCambio = 500; // Tipo de cambio fijo, puedes traerlo dinámicamente
    return round($colones / $tipoCambio, 2);
}

// --- Calcular total del carrito ---
$total_colones = 0;
foreach ($carrito as $item) {
    $total_colones += $item['precio'] * $item['cantidad'];
}

// --- Aplicar descuento si se canjean puntos ---
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

        // Reiniciar puntos del usuario
        $stmtUpdate = $conn->prepare("UPDATE Puntos_Usuario SET Puntos_Actuales = 0 WHERE ID_Usuario = ?");
        $stmtUpdate->bind_param("i", $user_id);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // Actualizar sesión
        $_SESSION['user_points'] = 0;

        // Registrar en historial de puntos
        $descripcion = "Canje de puntos en compra PayPal";
        $stmtHist = $conn->prepare("INSERT INTO Historial_Puntos (ID_Usuario, Fecha, Accion, Monto, Descripcion) VALUES (?, NOW(), 'Canjeado', ?, ?)");
        $stmtHist->bind_param("iis", $user_id, $descuentoAplicado, $descripcion);
        $stmtHist->execute();
        $stmtHist->close();
    }
}

// Convertir total ajustado a dólares
$total_usd = convertirColonesADolares($total_colones);

// --- Obtener Token de PayPal ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "Accept-Language: en_US"
]);

$token_response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($token_response, true);
$accessToken = $token_data['access_token'] ?? null;

if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo obtener el token de acceso.']);
    exit;
}

// --- Crear la orden en PayPal ---
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

// Devolvemos la respuesta de PayPal tal cual
echo $response;
