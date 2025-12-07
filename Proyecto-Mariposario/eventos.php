<?php

require_once 'DB.php';

// Iniciar sesión para obtener el ID del usuario logueado

session_start();

// eventos.php
require_once 'DB.php';
include 'layout/nav2.php';

$sql       = "SELECT ID_Evento, Nombre, Descripcion, Imagen_URL FROM Evento WHERE Activo = 1";
$resultado = $conn->query($sql);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Site keywords here">
    <meta name="description" content="">
    <meta name='copyright' content=''>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Jardín De Mariposas - Eventos</title>
    <link rel="icon" href="img/favicon.png">

    <link href="https://fonts.googleapis.com/css?family=Poppins:200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

	
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/icofont.css">
		<link rel="stylesheet" href="css/slicknav.min.css">
		<link rel="stylesheet" href="css/owl-carousel.css">
	  <link rel="stylesheet" href="css/datepicker.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
		<link rel="stylesheet" href="css/tienda.css">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    
</head>
<body class="mariposa">

<section class="products-catalog-section">
  <div class="catalog-wrapper">
    <h2 class="mb-4">Eventos disponibles</h2>
    <div class="product-link-custom row">
      <?php while ($e = $resultado->fetch_assoc()): ?>
        <div class="col-lg-4 col-md-6 col-sm-6 col-12">
          <div class="product-card-custom">
            <div class="product-image-container-custom">
              <img src="<?= htmlspecialchars($e['Imagen_URL']) ?>"
                   alt="<?= htmlspecialchars($e['Nombre']) ?>"
                   class="product-image-custom">
            </div>
            <div class="product-content-custom">
              <h5 class="product-name-custom"><?= htmlspecialchars($e['Nombre']) ?></h5>
              <p class="product-description-custom"><?= nl2br(htmlspecialchars(substr($e['Descripcion'], 0, 150))) ?>…</p>
              <a href="evento_info.php?id=<?= $e['ID_Evento'] ?>" class="add-to-cart-button-custom small-button-custom">
                Ver más información
              </a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<?php include 'layout/Footer.php'; ?>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
