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
	<header class="header">
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <ul class="top-link justify-content-end">
                        <li>
                            <a href="usuario.php" class="user-info-link">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></span>
                            </a>
                        </li>
                        <li class="separator">|</li>
                        <li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="logout.php" class="btn-topbar-action">Cerrar Sesión</a>
                            <?php else: ?>
                                <a href="logind.php" class="btn-topbar-action">Iniciar Sesión</a>
                            <?php endif; ?>
                        </li>
                        <li class="separator">|</li>
                        <li>
                            <a href="carrito.php" class="btn-carrito-topbar">
                                <i class="fa fa-shopping-cart"></i>
                                <span id="cart-item-count" class="badge badge-pill badge-danger">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="header-inner">
        <div class="container">
            <div class="inner">
                <div class="row">
                    <div class="col-lg-3 col-md-3 col-12">
                        <div class="logo">
                            <a href="index.php"><img src="img/logo.png" alt="Logo Mariposario"></a>
                        </div>
                        <div class="mobile-nav"></div>
                    </div>
                    <div class="col-lg-9 col-md-9 col-12"> <div class="main-menu">
                            <nav class="navigation">
                                <ul class="nav menu">
                                    <li class="<?= ($currentPage == 'index.php') ? 'active' : '' ?>">
                                        <a href="index.php">Inicio</a>
                                    </li>
                                    <li class="<?= ($currentPage == 'tienda.php') ? 'active' : '' ?>">
                                        <a href="tienda.php">Tienda</a>
                                    </li>
                                    <li class="<?= ($currentPage == 'eventos.php') ? 'active' : '' ?>">
                                        <a href="eventos.php">Eventos</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
	<!-- End Header Area -->


	<!DOCTYPE html>
	<html lang="es">

	<head>
		<meta charset="UTF-8">
		<title>Formulario de Reserva</title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	</head>

	<body>

		<section class="appointment">
			<div class="container">
				<div class="row">
					<div class="col-lg-12">
						<div class="section-title">
							<br>
							<br>
							<h2>Reserva tu Evento con Nosotros</h2>
							<p>Selecciona tu evento para realizar tu reserva.</p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6 col-md-12 col-12">
						<form class="form" action="reserva.php" method="post">
							<div class="row">
								<div class="col-lg-6 col-md-6 col-12">
									<div class="form-group">
										<input name="nombre" type="text" placeholder="Nombre Completo" required>
									</div>
								</div>
								<div class="col-lg-6 col-md-6 col-12">
									<div class="form-group">
										<input name="email" type="email" placeholder="Correo Electrónico" required>
									</div>
								</div>
								<div class="col-lg-6 col-md-6 col-12">
									<div class="form-group">
										<input name="telefono" type="text" placeholder="Teléfono" required>
									</div>
								</div>
								<div class="col-lg-6 col-md-6 col-12">
									<div class="form-group">
										<select name="evento" class="form-control" id="evento" required>
											<option value="">-- Selecciona un evento --</option>
											<?php
											include 'DB.php';
											$resultado = $conn->query("SELECT ID_Evento, Nombre FROM Evento");
											while ($fila = $resultado->fetch_assoc()) {
												echo '<option value="' . $fila['ID_Evento'] . '">' . htmlspecialchars($fila['Nombre']) . '</option>';
											}
											$conn->close();
											?>
										</select>
									</div>
								</div>
								<div class="col-lg-6 col-md-6 col-12">
									<div class="form-group">
										<input name="personas" type="number" placeholder="Cantidad de Personas"
											required>
									</div>
								</div>
								<div class="col-lg-12 col-md-12 col-12">
									<div class="form-group">
										<textarea name="mensaje"
											placeholder="¿Algo más que debamos saber? (opcional)"></textarea>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-lg-5 col-md-4 col-12">
									<div class="form-group">
										<div class="button">
											<button type="submit" class="btn">Reservar Evento</button>
										</div>
									</div>
								</div>
								<div class="col-lg-7 col-md-8 col-12">
									<p>(Nos pondremos en contacto contigo para confirmar tu reserva)</p>
								</div>
							</div>
						</form>
					</div>
					<div class="col-lg-6 col-md-12">
						<div class="appointment-image">
							<img src="img/reserva-removebg-preview.png" alt="Reservas">
						</div>
					</div>
				</div>
			</div>
		</section>

	</body>

	</html>






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
							<p>Somos un proyecto dedicado a la conservación y apreciación de mariposas y orquídeas en
								Costa Rica. Promovemos el turismo sostenible y la educación ambiental.</p>
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
										<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Inicio</a>
										</li>
										<li><a href="#"><i class="fa fa-caret-right"
													aria-hidden="true"></i>Reservaciones</a></li>
										<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Galería</a>
										</li>
										<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Eventos</a>
										</li>
										<li><a href="#"><i class="fa fa-caret-right"
													aria-hidden="true"></i>Contáctanos</a></li>
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
							<p>Suscríbete para recibir noticias sobre nuestras mariposas, orquídeas y próximos eventos
								especiales.</p>
							<form action="#" method="get" target="_blank" class="newsletter-inner">
								<input name="email" placeholder="Tu correo electrónico" class="common-input" required
									type="email">
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