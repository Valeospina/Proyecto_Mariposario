<?php
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$orderID = $data['orderID'] ?? '';

if (!$orderID) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de orden no recibido.']);
    exit;
}

// Tus credenciales de PayPal (usa modo sandbox para pruebas)
$clientId = "ATaviM4-qfB_deZSXciXwtIalyjoNEseNB0FsCJ2riwp6fLYZzaVKTe4jjoY53IjHJx6UWQy48APsJ_H";
$secret = "EEwY88QBM9WlzB1LK6g_03u3kHlwPvnUpL_mp4khsizEgE8NuYYU_cFxs4B57h9jjDO8EsQBD_Z2BeXT";

// Paso 1: Obtener access token
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

$response = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener token: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$accessToken = json_decode($response)->access_token ?? null;

if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo obtener el token de acceso.']);
    exit;
}

// Paso 2: Capturar la orden
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders/$orderID/capture");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $accessToken"
]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al capturar orden: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$result = json_decode($response, true);

// Paso 3: Limpiar carrito si el pago fue exitoso
if (!empty($result['status']) && $result['status'] === 'COMPLETED') {
    $_SESSION['carrito'] = [];
}

header('Content-Type: application/json');
echo json_encode($result);
