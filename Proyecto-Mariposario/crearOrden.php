<?php
session_start();
header('Content-Type: application/json');

// ✅ Credenciales de PayPal SANDBOX (asegúrate de usar las correctas)
$clientId = "ATaviM4-qfB_deZSXciXwtIalyjoNEseNB0FsCJ2riwp6fLYZzaVKTe4jjoY53IjHJx6UWQy48APsJ_H";
$clientSecret = "AciSwp0yUOh_48qfOrNbHEakgsaUbDhmPc6xE1YePsFJtGKbFlTuCxsan0_KOw14bqNxAU2Bgkr7nnBz";

// 🛒 Obtener productos del carrito
$carrito = $_SESSION['carrito'] ?? [];

if (empty($carrito)) {
    http_response_code(400);
    echo json_encode(['error' => 'El carrito está vacío']);
    exit;
}

// 💰 Calcular el total del pedido
$total = 0;
foreach ($carrito as $item) {
    $subtotal = $item['precio'] * $item['cantidad'];
    $total += $subtotal;
}
$total = number_format($total, 2, '.', ''); // e.g. "1234.56"

// 1️⃣ Obtener access token de PayPal
$ch = curl_init("https://api-m.sandbox.paypal.com/v1/oauth2/token");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => "$clientId:$clientSecret",
    CURLOPT_POSTFIELDS => "grant_type=client_credentials",
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Accept-Language: en_US"
    ]
]);

$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($tokenResponse, true);

if (!isset($tokenData['access_token'])) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo obtener el token de acceso', 'paypal_response' => $tokenData]);
    exit;
}

$accessToken = $tokenData['access_token'];

// 2️⃣ Crear orden en PayPal
$orderData = [
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "amount" => [
            "currency_code" => "CRC",
            "value" => $total
        ]
    ]]
];

$ch = curl_init("https://api-m.sandbox.paypal.com/v2/checkout/orders");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($orderData),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer $accessToken"
    ]
]);

$orderResponse = curl_exec($ch);
curl_close($ch);

$orderData = json_decode($orderResponse, true);

if (isset($orderData['id'])) {
    echo json_encode(['id' => $orderData['id']]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo crear la orden en PayPal', 'paypal_response' => $orderData]);
}
