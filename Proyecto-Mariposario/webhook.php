<?php
require 'vendor/autoload.php';
require 'DB.php';

$input = @file_get_contents("php://input");
$sig_header = $_SERVER["HTTP_STRIPE_SIGNATURE"];
$secret = 'sk_test_51RjRay2LHFQdQmmkk5xtgl78eJ983Al5poJSNKBwvoP47C2Y3oxcwjsPtsuW8YYh3vOUXFqtYNksEQxFrPV50TPu00A2ZJiLpq'; 

try {
    $event = \Stripe\Webhook::constructEvent($input, $sig_header, $secret);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit("Payload inválido");
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit("Firma no válida");
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    $pedido_id = $session->metadata->pedido_id;

    // Confirmar pago en DB
    $stmt = $conn->prepare("UPDATE Pedido SET Estado_Pedido = 'Pago Confirmado' WHERE ID_Pedido = ?");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();

    // Aquí puedes generar factura, puntos, correo, etc.
}

http_response_code(200);
