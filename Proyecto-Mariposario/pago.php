<?php
session_start();

$carrito = $_SESSION['carrito'] ?? [];
$total = 0;

foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago - Eco Mariposas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Estilos -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- SDK de PayPal -->
    <script src="https://www.paypal.com/sdk/js?client-id=AciSwp0yUOh_48qfOrNbHEakgsaUbDhmPc6xE1YePsFJtGKbFlTuCxsan0_KOw14bqNxAU2Bgkr7nnBz&currency=CRC"></script>
</head>
<body>
    <?php include 'layout/nav2.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Confirmación de Pago</h2>

        <?php if (empty($carrito)): ?>
            <div class="alert alert-warning text-center">
                Tu carrito está vacío. <a href="tienda.php">Volver a la tienda</a>
            </div>
        <?php else: ?>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Productos:</h5>
                    <ul class="list-group mb-3">
                        <?php foreach ($carrito as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($item['nombre']); ?> x <?php echo $item['cantidad']; ?>
                                <span>₡<?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?></span>
                            </li>
                        <?php endforeach; ?>
                        <li class="list-group-item d-flex justify-content-between font-weight-bold">
                            Total a pagar:
                            <span>₡<?php echo number_format($total, 2, ',', '.'); ?></span>
                        </li>
                    </ul>

                    <div class="text-center mt-4">
                        <p>Completa tu pago con PayPal:</p>
                        <div id="paypal-button-container"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="text-center text-muted mt-5 mb-3">
        &copy; <?php echo date("Y"); ?> Eco Mariposas
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <script>
    paypal.Buttons({
        createOrder: function(data, actions) {
            return fetch('crearOrden.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(function(res) {
                if (!res.ok) throw new Error('No se pudo crear la orden');
                return res.json();
            })
            .then(function(orderData) {
                return orderData.id;
            })
            .catch(function(err) {
                console.error('create_order_error', err);
                alert('Hubo un error al crear la orden de PayPal.');
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                alert('Pago completado por ' + details.payer.name.given_name + '. ¡Gracias!');
                window.location.href = 'confirmation.php';
            });
        },
        onError: function(err) {
            console.error('Error en el pago:', err);
            alert('Error al procesar el pago.');
        }
    }).render('#paypal-button-container');
    </script>
</body>
</html>