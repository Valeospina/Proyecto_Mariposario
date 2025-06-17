<!doctype html>
<html class="no-js" lang="zxx">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="keywords" content="Site keywords here">
        <meta name="description" content="">
        <meta name='copyright' content=''>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>Jardin De Mariposas - Mariposas</title> <link rel="icon" href="img/favicon.png">
        <link rel="stylesheet" href="./css/tienda.css">

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

    <body class="user">

       <?php
include 'layout/nav2.php';
require_once 'DB.php';

// Obtener eventos desde la base de datos
$sql = "SELECT ID_Evento, Nombre, Descripcion, Precio, Imagen_URL FROM Evento";
$resultado = $conn->query($sql);
?>

<div class="eventos-section py-5">
    <div class="eventos-wrapper px-4 px-md-5">
        <h2 class="eventos-titulo mb-4">Eventos disponibles</h2>

        <?php while ($evento = $resultado->fetch_assoc()): ?>
            <div class="evento-card d-flex flex-column flex-md-row mb-4 shadow-sm">
                <div class="evento-img">
                    <img src="<?= htmlspecialchars($evento['Imagen_URL']) ?>" alt="<?= htmlspecialchars($evento['Nombre']) ?>">
                </div>
                <div class="evento-detalles p-4">
                    <h5 class="mb-2" style="color: #2d7452; font-weight: bold;"><?= htmlspecialchars($evento['Nombre']) ?></h5>
                    <p class="text-muted mb-1"><?= htmlspecialchars($evento['Descripcion']) ?></p>
                    <p class="fw-bold text-success mb-3">₡<?= number_format($evento['Precio'], 2) ?></p>
                    <a href="ReservaForm.php?id=<?= $evento['ID_Evento'] ?>" class="btn btn-success" style="color: white; font-weight: bold;">Reservar</a>
                    <a href="VerCalendario.php?id=<?= $evento['ID_Evento'] ?>" class="btn btn-success" style="color: white; font-weight: bold;">Ver fechas disponibles</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!--------------------------------------------------------------------------------------------------------------->
			
			<!-- Footer Area -->
			<footer id="footer" class="footer">
				<!-- Footer Top -->
				<div class="footer-top">
					<div class="container">
						<div class="row">
							<!-- Acerca del Proyecto -->
							<div class="col-lg-3 col-md-6 col-12">
								<div class="single-footer">
									<h2>Sobre Nosotros</h2>
									<p>Somos un proyecto dedicado a la conservación y apreciación de mariposas y orquídeas en Costa Rica. Promovemos el turismo sostenible y la educación ambiental.</p>
									<!-- Social -->
									<ul class="social">
										<li><a href="#"><i class="icofont-facebook"></i></a></li>
										<li><a href="#"><i class="icofont-instagram"></i></a></li>
										<li><a href="#"><i class="icofont-twitter"></i></a></li>
									</ul>
									<!-- End Social -->
								</div>
							</div>

							<!-- Enlaces Rápidos -->
							<div class="col-lg-3 col-md-6 col-12">
								<div class="single-footer f-link">
									<h2>Enlaces Rápidos</h2>
									<div class="row">
										<div class="col-12">
											<ul>
												<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Inicio</a></li>
												<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Reservaciones</a></li>
												<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Galería</a></li>
												<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Eventos</a></li>
												<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Contáctanos</a></li>
											</ul>
										</div>
									</div>
								</div>
							</div>

							<!-- Horarios -->
							<div class="col-lg-3 col-md-6 col-12">
								<div class="single-footer">
									<h2>Horario de Atención</h2>
									<p>Visítanos para vivir una experiencia rodeado de naturaleza y belleza.</p>
									<ul class="time-sidual">
										<li class="day">Lunes - Viernes <span>8:00 - 17:00</span></li>
										<li class="day">Sábado <span>9:00 - 16:00</span></li>
										<li class="day">Domingo <span>Cerrado</span></li>
									</ul>
								</div>
							</div>

							<!-- Newsletter -->
							<div class="col-lg-3 col-md-6 col-12">
								<div class="single-footer">
									<h2>Boletín</h2>
									<p>Suscríbete para recibir noticias sobre nuestras mariposas, orquídeas y próximos eventos especiales.</p>
									<form action="#" method="get" target="_blank" class="newsletter-inner">
										<input name="email" placeholder="Tu correo electrónico" class="common-input" required type="email">
										<button class="button"><i class="icofont icofont-paper-plane"></i></button>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- End Footer Top -->

				<!-- Copyright -->
				<div class="copyright">
					<div class="container">
						<div class="row">
							<div class="col-12">
								<div class="copyright-content">
									<p>© 2025 Mariposas y Orquídeas | Todos los derechos reservados</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- End Copyright -->
			</footer>
			<!-- End Footer Area -->

			
			<!-- jquery Min JS -->
			<script src="js/jquery.min.js"></script>
			<!-- jquery Migrate JS -->
			<script src="js/jquery-migrate-3.0.0.js"></script>
			<!-- jquery Ui JS -->
			<script src="js/jquery-ui.min.js"></script>
			<!-- Easing JS -->
			<script src="js/easing.js"></script>
			<!-- Color JS -->
			<script src="js/colors.js"></script>
			<!-- Popper JS -->
			<script src="js/popper.min.js"></script>
			<!-- Bootstrap Datepicker JS -->
			<script src="js/bootstrap-datepicker.js"></script>
			<!-- Jquery Nav JS -->
			<script src="js/jquery.nav.js"></script>
			<!-- Slicknav JS -->
			<script src="js/slicknav.min.js"></script>
			<!-- ScrollUp JS -->
			<script src="js/jquery.scrollUp.min.js"></script>
			<!-- Niceselect JS -->
			<script src="js/niceselect.js"></script>
			<!-- Tilt Jquery JS -->
			<script src="js/tilt.jquery.min.js"></script>
			<!-- Owl Carousel JS -->
			<script src="js/owl-carousel.js"></script>
			<!-- counterup JS -->
			<script src="js/jquery.counterup.min.js"></script>
			<!-- Steller JS -->
			<script src="js/steller.js"></script>
			<!-- Wow JS -->
			<script src="js/wow.min.js"></script>
			<!-- Magnific Popup JS -->
			<script src="js/jquery.magnific-popup.min.js"></script>
			<!-- Counter Up CDN JS -->
			<script src="http://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
			<!-- Bootstrap JS -->
			<script src="js/bootstrap.min.js"></script>
			<!-- Main JS -->
			<script src="js/main.js"></script>
		</body>
	</html>