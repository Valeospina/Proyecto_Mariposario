
<?php 
session_start(); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compra Exitosa - ECOMARIPOSAS</title>
    <link rel="stylesheet" href="css/confirmacion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
</head>
<body class="confirmacion-body">
    <div class="confirmacion-container">
        <div class="confirmacion-box">
            <h1 class="logo-title"> 🦋 <span>ECOMARIPOSAS</span> 🦋</h1>
            <div class="icono-confirmacion"><i class="fas fa-check-circle"></i></div>
            <h2 class="titulo">¡Gracias por tu compra!</h2>
            <p class="mensaje">Tu pedido ha sido procesado correctamente.<br>Pronto recibirás un correo con los detalles.</p>
            <div class="botones">
                <a href="tienda.php" class="btn-confirmacion"><i class="fas fa-store"></i> Seguir Comprando</a>
                <a href="MisPedidos.php" class="btn-outline-confirmacion"><i class="fas fa-box-open"></i> Ver Mis Pedidos</a>
            </div>
        </div>
    </div>
</body>
</html>