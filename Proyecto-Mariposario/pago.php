<?php
session_start();

// Validar carrito
$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    header("Location: carrito.php");
    exit();
}

// Calcular total en colones y convertir a USD (tasa aproximada)
function convertirColonesADolares($colones) {
    $tipoCambio = 500; // puedes usar tipo de cambio dinámico si lo deseas
    return round($colones / $tipoCambio, 2);
}

$total_colones = 0;
foreach ($carrito as $item) {
    $total_colones += $item['precio'] * $item['cantidad'];
}
$total_usd = convertirColonesADolares($total_colones);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago con PayPal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/tienda.css">
    <link rel="stylesheet" href="css/carrito.css">
    <script src="https://www.paypal.com/sdk/js?client-id=ATaviM4-qfB_deZSXciXwtIalyjoNEseNB0FsCJ2riwp6fLYZzaVKTe4jjoY53IjHJx6UWQy48APsJ_H&currency=USD"></script>
</head>
<body>
    <?php include 'layout/nav2.php'; ?>
    <section class="user-panel section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="user-main-content">
                        <h2 class="text-center mb-4">Resumen del Pedido</h2>
                        <ul class="list-group mb-4">
                            <?php foreach ($carrito as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($item['nombre']); ?></span>
                                    <span>₡<?php echo number_format($item['precio'], 2); ?> x <?php echo $item['cantidad']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="alert alert-info">
                            <h5>Total a pagar:</h5>
                            <p><strong>₡<?php echo number_format($total_colones, 2); ?></strong></p>
                            <p>Se convertirá aproximadamente a <strong>$<?php echo number_format($total_usd, 2); ?></strong> para el pago con PayPal.</p>
                        </div>

                        <div id="paypal-button-container" class="text-center"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        paypal.Buttons({
            createOrder: function(data, actions) {
                return fetch('create-order.php', {
                    method: 'post'
                }).then(res => res.json())
                  .then(orderData => orderData.id);
            },
            onApprove: function(data, actions) {
                return fetch('crearOrden.php', {
                    method: 'post',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        orderID: data.orderID
                    })
                })
                .then(res => res.json())
                .then(details => {
                    if (details.status === 'COMPLETED') {
                        alert('Pago realizado por ' + details.payer.name.given_name);
                        window.location.href = 'confirmacion.php';
                    } else {
                        alert('Error en el pago. Estado: ' + details.status);
                    }
                })
                .catch(err => {
                    console.error('Error al capturar la orden:', err);
                    alert('Hubo un problema al procesar el pago.');
                });
            },
            onError: function(err) {
                console.error('Error en el botón de PayPal:', err);
                alert('Ocurrió un error con el botón de PayPal.');
            }
        }).render('#paypal-button-container');
    </script>
</body>
</html>