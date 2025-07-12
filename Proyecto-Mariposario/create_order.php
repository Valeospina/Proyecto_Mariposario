<?php
session_start();
header('Content-Type: application/json');

// Asegúrate de que el carrito esté cargado
$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    http_response_code(400);
    echo json_encode(['error' => 'El carrito está vacío.']);
    exit;
}

// Tus credenciales PayPal (SANDBOX)
$clientId = 'ATaviM4-qfB_deZSXciXwtIalyjoNEseNB0FsCJ2riwp6fLYZzaVKTe4jjoY53IjHJx6UWQy48APsJ_H';
$secret = 'EEwY88QBM9WlzB1LK6g_03u3kHlwPvnUpL_mp4khsizEgE8NuYYU_cFxs4B57h9jjDO8EsQBD_Z2BeXT';

// Calcula el total en USD
function convertirColonesADolares($colones) {
    $tipoCambio = 500; // tasa fija o puedes usar API para tasa real
    return round($colones / $tipoCambio, 2);
}

$total_colones = 0;
foreach ($carrito as $item) {
    $total_colones += $item['precio'] * $item['cantidad'];
}
$total_usd = convertirColonesADolares($total_colones);

// Obtener el token
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

// Crear la orden
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
