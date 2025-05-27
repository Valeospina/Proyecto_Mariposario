<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <!-- Meta Tags -->
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="keywords" content="Site keywords here" />
    <meta name="description" content="" />
    <meta name="copyright" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Title -->
    <title>Jardin De Mariposas</title>

    <!-- Favicon -->
    <link rel="icon" href="img/favicon.png" />
    <link rel="stylesheet" href="./css/tienda.css" />

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css?family=Poppins:200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap"
        rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <!-- Nice Select CSS -->
    <link rel="stylesheet" href="css/nice-select.css" />
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="css/font-awesome.min.css" />
    <!-- icofont CSS -->
    <link rel="stylesheet" href="css/icofont.css" />
    <!-- Slicknav -->
    <link rel="stylesheet" href="css/slicknav.min.css" />
    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="css/owl-carousel.css" />
    <!-- Datepicker CSS -->
    <link rel="stylesheet" href="css/datepicker.css" />
    <!-- Animate CSS -->
    <link rel="stylesheet" href="css/animate.min.css" />
    <!-- Magnific Popup CSS -->
    <link rel="stylesheet" href="css/magnific-popup.css" />
    <!-- Tienda CSS -->
    <link rel="stylesheet" href="css/tienda.css" />

    <!-- Medipro CSS -->
    <link rel="stylesheet" href="css/normalize.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="css/responsive.css" />
</head>

<body class="user"> <!-- Cambia 'admin' por 'user' si es un usuario normal -->

 		<!-- Preloader -->
        <div class="preloader">
            <div class="loader">
                <div class="loader-outter"></div>
                <div class="loader-inner"></div>

                <div class="indicator"> 
					<svg width="32px" height="32px" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
						<g>
						  <!-- Ala trasera izquierda -->
						  <path d="M32 32 C22 20, 10 40, 28 40" fill="none" stroke="#ffffff" stroke-width="2"/>
					  
						  <!-- Ala trasera derecha -->
						  <path d="M32 32 C42 20, 54 40, 36 40" fill="none" stroke="#ffffff" stroke-width="2"/>
					  
						  <!-- Ala delantera izquierda animada -->
						  <path d="M32 32 C18 14, 4 34, 24 36" fill="none" stroke="#80B78D" stroke-width="2">
							<animate attributeName="d" dur="1s" repeatCount="indefinite"
							  values="
								M32 32 C18 14, 4 34, 24 36;
								M32 32 C16 16, 2 32, 22 36;
								M32 32 C18 14, 4 34, 24 36"/>
						  </path>
					  
						  <!-- Ala delantera derecha animada -->
						  <path d="M32 32 C46 14, 60 34, 40 36" fill="none" stroke="#80B78D" stroke-width="2">
							<animate attributeName="d" dur="1s" repeatCount="indefinite"
							  values="
								M32 32 C46 14, 60 34, 40 36;
								M32 32 C48 16, 62 32, 42 36;
								M32 32 C46 14, 60 34, 40 36"/>
						  </path>
					  
						  <!-- Cuerpo -->
						  <line x1="32" y1="30" x2="32" y2="40" stroke="#ffffff" stroke-width="2" />
						</g>
					  </svg>
                </div>
            </div>
        </div>

        <!-- End Preloader -->

    <!-- Header Area -->
    <header class="header">
        <!-- Topbar -->
        <div class="topbar">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-5 col-12">
                        <!-- Contact -->
                        <ul class="top-link">
                            <li>
                                <a href="usuario.html" style="text-decoration: none;">
                                    <i class="fas fa-user" style="font-size: 18px; color: #80B78D; padding: 6px;"></i>
                                    <span style="color: #2C2D3F;">Usuario</span>
                                </a>
                            </li>
                            <!-- Admin section visible only for admins -->
                            <li class="admin"><a href="admin.html">Admin</a></li>
                        </ul>
                        <!-- End Contact -->
                    </div>
                    <div class="col-lg-6 col-md-7 col-12">
                        <!-- Top Contact -->
                        <ul class="top-contact">
                            <li><i class="fa fa-phone"></i>+506 8888 8888</li>
                            <li><i class="fa fa-envelope"></i><a href="mailto:info@mariposario.com">info@mariposario.com</a></li>
                        </ul>
                        <!-- End Top Contact -->
                    </div>
                </div>
            </div>
        </div>
        <!-- End Topbar -->

        <!-- Header Inner -->
        <div class="header-inner">
            <div class="container">
                <div class="inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-3 col-12">
                            <!-- Start Logo -->
                            <div class="logo">
                                <a href="index.html"><img src="img/logo.png" alt="Logo Mariposario" /></a>
                            </div>
                            <!-- End Logo -->
                            <!-- Mobile Nav -->
                            <div class="mobile-nav"></div>
                            <!-- End Mobile Nav -->
                        </div>
                        <div class="col-lg-7 col-md-9 col-12">
                            <!-- Main Menu -->
                            <div class="main-menu">
                                <nav class="navigation">
                                    <ul class="nav menu">
                                        <li><a href="index.html">Inicio</a></li>
                                        <li><a href="tienda.html">Volver a tienda</a></li>
                                        <li class="active"><a href="mariposas.html">Mariposas</a></li>
                                    </ul>
                                </nav>
                            </div>
                            <!--/ End Main Menu -->
                        </div>
                        <div class="col-lg-2 col-12">
                            <div class="get-quote">
                                <a href="carrito.php" class="btn btn-carrito"
                                    style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: transparent; box-shadow: none;">
                                    <i class="fa fa-shopping-cart icono-carrito" style="color: #42764D; font-size: 20px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Header Inner -->
    </header>
    <!-- End Header Area -->

    <!-- Sección de Búsqueda y Filtro -->
    <div id="imagen-facts" class="imagen-facts section">
        <div class="overlay-form-container">
            <form method="GET" action="">
                <div class="row justify-content-center align-items-center">
                    <!-- Campo de búsqueda -->
                    <div class="col-md-8 col-lg-5">
                        <div class="form-group">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre de producto"
                                style="height: 45px; padding: 6px 12px;"
                                value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>" />
                        </div>
                    </div>
                    <!-- Filtro por categoría -->
                    <div class="col-md-3 col-lg-2">
                        <div class="form-group">
                            <select class="form-control" name="categoria" style="height: 45px; padding: 6px 12px;">
                                <option value="">Categoría</option>
                                <option value="Mariposas" <?php if (isset($_GET['categoria']) && $_GET['categoria'] === 'Mariposas') echo 'selected'; ?>>Mariposas</option>
                                <option value="Orquideas" <?php if (isset($_GET['categoria']) && $_GET['categoria'] === 'Orquideas') echo 'selected'; ?>>Orquideas</option>               
                            </select>
                        </div>
                    </div>
                    <!-- Botón de búsqueda -->
                    <div class="col-md-1 col-lg-1">
                        <button type="submit" class="btn btn-success" style="height: 45px;">Buscar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sección de Resultados -->
 <section id="team" class="team section" style="margin-top: 40px;">
    <div class="container">
        <div class="row">
            <?php
            include 'db.php';

            if ($conn->connect_error) {
                die("<p>Conexión fallida: " . $conn->connect_error . "</p>");
            }

            $buscar = isset($_GET['buscar']) ? $conn->real_escape_string(trim($_GET['buscar'])) : '';
            $categoria = isset($_GET['categoria']) ? $conn->real_escape_string(trim($_GET['categoria'])) : '';

            $sql = "SELECT * FROM producto WHERE 1=1";

            if (!empty($buscar)) {
                $sql .= " AND nombre LIKE '%$buscar%'";
            }

            if (!empty($categoria)) {
                $sql .= " AND categoria = '$categoria'";
            }

            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($row["Imagen_URL"]); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row["Nombre"]); ?>" />
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($row["Nombre"]); ?></h5>
                                <p class="card-text">$<?php echo number_format($row["Precio"], 2); ?></p>

                                <!-- Formulario para agregar al carrito -->
                                <form method="POST" action="carrito.php" class="mt-auto">
                                    <input type="hidden" name="producto_id" value="<?php echo $row['ID_Producto']; ?>">
                                    <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($row['Nombre']); ?>">
                                    <input type="hidden" name="precio" value="<?php echo $row['Precio']; ?>">
                                    <button type="submit" class="btn btn-success btn-block">Agregar al carrito</button>
                                </form>

                                <a href="detalle.php?id=<?php echo intval($row["ID_Producto"]); ?>" class="btn btn-primary mt-2">Ver Detalle</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><p>No se encontraron productos que coincidan con la búsqueda.</p></div>';
            }

            $conn->close();
            ?>
        </div> <!-- cierre de row -->
    </div> <!-- cierre de container -->
</section>

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