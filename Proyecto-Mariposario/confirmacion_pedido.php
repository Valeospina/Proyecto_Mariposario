<?php
session_start();
$numero_proforma = $_GET['proforma'] ?? 'No disponible';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Confirmación de Pedido - Eco Mariposas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css"> <link rel="stylesheet" href="css/carrito.css"> </head>
<body>
    <?php include 'layout/nav2.php'; ?>

    <section class="section pt-5 pb-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10 col-12">
                    <div class="card shadow p-4">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                            <h2 class="card-title text-success mb-3">¡Pedido Realizado con Éxito!</h2>
                            <p class="lead">Tu pedido ha sido registrado correctamente.</p>
                            <p class="fs-5">Tu número de Proforma es: <strong><?php echo htmlspecialchars($numero_proforma); ?></strong></p>
                            <p class="mt-4">Por favor, acércate a nuestra tienda física para completar el pago y recoger tus productos.</p>
                            <p>¡Gracias por tu compra!</p>
                            <div class="mt-5">
                                <a href="tienda.php" class="btn btn-primary me-2">Seguir Comprando</a>
                                <a href="MisPedidos.php" class="btn btn-outline-primary">Ver Mis Pedidos</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php // include 'layout/footer.php'; ?> <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>