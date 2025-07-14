<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Site keywords here">
    <meta name="description" content="">
    <meta name='copyright' content=''>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Jardin De Mariposas - Mariposas</title>
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">

    <style>
	.appointment {
		margin-bottom: 80px;
	}
	/* General form styling */
	.appointment .form {
		background-color: #ffffff; /* White background for a clean look */
		padding: 40px; /* Increased padding for more breathing room */
		border-radius: 12px; /* More rounded corners */
		box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08); /* Softer, more pronounced shadow */
		transition: all 0.4s ease-in-out; /* Add transition for hover effects later if desired */
	}

	/* Optional: Add a subtle hover effect to the form itself */
	.appointment .form:hover {
		box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
	}

	.appointment .form-group {
		margin-bottom: 25px; /* Slightly more space between form elements */
	}

	.appointment .form-group input[type="text"],
	.appointment .form-group input[type="email"],
	.appointment .form-group input[type="number"],
	.appointment .form-group select,
	.appointment .form-group textarea {
		width: 100%;
		padding: 14px 18px; /* Increased padding for better touch targets and appearance */
		border: 1px solid #e0e0e0; /* Lighter, softer border color */
		border-radius: 8px; /* Slightly more rounded inputs */
		font-size: 17px; /* Slightly larger font size for readability */
		color: #4a4a4a; /* Darker text color for better contrast */
		background-color: #fcfcfc; /* Very light background for inputs */
		transition: border-color 0.3s ease, box-shadow 0.3s ease; /* Smooth transitions for focus */
	}

	.appointment .form-group input:focus,
	.appointment .form-group select:focus,
	.appointment .form-group textarea:focus {
		border-color: #5b9bd5; /* A more modern blue for focus */
		box-shadow: 0 0 0 0.25rem rgba(91, 155, 213, 0.2); /* Softer focus shadow */
		outline: none;
		background-color: #ffffff; /* White background on focus */
	}

	.appointment .form-group textarea {
		resize: vertical;
		min-height: 120px; /* Slightly increased min-height for textareas */
	}

	.appointment .form .button .btn {
		background-color: #333; /* Your existing button color - kept for consistency */
		color: white;
		padding: 14px 30px; /* More padding for a bolder button */
		border: none;
		border-radius: 8px; /* Consistent border-radius with inputs */
		font-size: 19px; /* Slightly larger font for prominence */
		font-weight: 600; /* Bolder text */
		cursor: pointer;
		transition: background-color 0.3s ease, transform 0.2s ease; /* Add transform for subtle click effect */
		letter-spacing: 0.5px; /* Slight letter spacing */
	}

	.appointment .form .button .btn:hover {
		background-color: #333; /* Darker shade on hover */
		transform: translateY(-2px); /* Lift effect on hover */
	}

	.appointment .form .button .btn:active {
		transform: translateY(0); /* Reset on click */
		background-color: #333; /* Even darker on active */
	}

	.appointment .form p {
		margin-top: 20px; /* More space above the helper text */
		font-size: 15px; /* Slightly larger font */
		color: #7a7a7a; /* Softer gray color */
		line-height: 1.6; /* Better readability for longer texts */
	}

	/* Section title specific styling - Modernized */
	.section-title {
		text-align: center;
		margin-bottom: 50px; /* More space below the title */
		position: relative; /* For potential pseudo-element underlines */
	}

	.section-title h2 {
		font-size: 42px; /* Larger, more impactful heading */
		font-weight: 700;
		color: #2c3e50; /* Darker, more modern heading color */
		margin-bottom: 15px; /* More space between title and subtitle */
		position: relative;
		display: inline-block; /* To allow pseudo-element to size with text */
	}

	/* Optional: Modern underline effect for the title */
	.section-title h2::after {
		content: '';
		display: block;
		width: 60px; /* Width of the underline */
		height: 4px; /* Thickness of the underline */
		background: #8BC34A; /* Accent color for the underline */
		margin: 10px auto 0; /* Center the underline */
		border-radius: 2px;
	}

	.section-title p {
		font-size: 19px; /* Larger subtitle */
		color: #666;
		max-width: 700px; /* Limit width for better readability on large screens */
		margin-left: auto;
		margin-right: auto;
	}

	/* Topbar specific styles for user info and cart */
	.topbar {
		background-color: #f8f9fa;
		padding: 12px 0; /* Slightly more padding */
		border-bottom: 1px solid #e9ecef;
	}

	.topbar .top-link {
		list-style: none;
		margin: 0;
		padding: 0;
		display: flex;
		align-items: center;
	}

	.topbar .top-link li {
		margin-left: 20px; /* Increased spacing */
	}

	.topbar .top-link li a {
		color: #555;
		text-decoration: none;
		font-size: 16px; /* Slightly larger font */
		transition: color 0.3s ease;
		display: flex; /* For better icon alignment */
		align-items: center;
	}

	.topbar .top-link li a:hover {
		color: #d4ac0d; /* Use your brand color for hover */
	}

	.topbar .top-link li .user-info-link i {
		margin-right: 8px; /* More space between icon and text */
		font-size: 18px; /* Larger icon */
	}

	.topbar .top-link li .badge {
		margin-left: 8px; /* More space */
		background-color: #e74c3c; /* A slightly softer red for the badge */
		color: white;
		padding: 5px 9px; /* More padding for a nicer pill shape */
		border-radius: 15px; /* More rounded */
		font-size: 13px; /* Slightly larger font */
		font-weight: 600;
	}

	.topbar .top-link .separator {
		color: #d0d0d0; /* Lighter separator */
		margin: 0 10px; /* More spacing */
	}

	/* Header Inner (Logo and Main Menu) */
	.header-inner {
		background-color: #ffffff;
		padding: 25px 0; /* More vertical padding */
		box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07); /* Slightly stronger, but still soft shadow */
	}

	.header-inner .logo img {
		max-height: 55px; /* Slightly larger logo */
		transition: transform 0.3s ease;
	}
	.header-inner .logo img:hover {
		transform: scale(1.05); /* Subtle zoom on logo hover */
	}

	.main-menu .navigation .nav.menu {
		list-style: none;
		margin: 0;
		padding: 0;
		display: flex;
		justify-content: flex-end;
	}

	.main-menu .navigation .nav.menu li {
		margin-left: 35px; /* More space between menu items */
		position: relative;
	}

	.main-menu .navigation .nav.menu li a {
		color: #3a3a3a; /* Darker, more prominent menu text */
		font-size: 18px; /* Slightly larger menu items */
		font-weight: 500;
		text-decoration: none;
		padding: 12px 0; /* More padding */
		display: block;
		transition: color 0.3s ease, border-bottom 0.3s ease;
	}

	.main-menu .navigation .nav.menu li.active a,
	.main-menu .navigation .nav.menu li a:hover {
		color: #d4ac0d; /* Use your brand color for active/hover */
		border-bottom: 2px solid #d4ac0d; /* Add a subtle underline effect */
		padding-bottom: 10px; /* Adjust padding to make space for underline */
	}

		</style>
</head>
<body>

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
                    <div class="col-lg-9 col-md-9 col-12">
                        <div class="main-menu">
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
                <div class="col-lg-12 col-md-12 col-12"> <form class="form" action="reserva.php" method="post">
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
                                    <input name="personas" type="number" placeholder="Cantidad de Personas" required>
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12 col-12">
                                <div class="form-group">
                                    <textarea name="mensaje" placeholder="¿Algo más que debamos saber? (opcional)"></textarea>
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
            </div>
        </div>
    </section>

</body>

</html>


<footer id="footer" class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="single-footer">
                        <h2>Sobre Nosotros</h2>
                        <p>Somos un proyecto dedicado a la conservación y apreciación de mariposas y orquídeas en
                            Costa Rica. Promovemos el turismo sostenible y la educación ambiental.</p>
                        <ul class="social">
                            <li><a href="#"><i class="icofont-facebook"></i></a></li>
                            <li><a href="#"><i class="icofont-instagram"></i></a></li>
                            <li><a href="#"><i class="icofont-twitter"></i></a></li>
                        </ul>
                        </div>
                </div>

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
    </footer>
<script src="js/jquery.min.js"></script>
<script src="js/jquery-migrate-3.0.0.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/easing.js"></script>
<script src="js/colors.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="js/jquery.nav.js"></script>
<script src="js/slicknav.min.js"></script>
<script src="js/jquery.scrollUp.min.js"></script>
<script src="js/niceselect.js"></script>
<script src="js/tilt.jquery.min.js"></script>
<script src="js/owl-carousel.js"></script>
<script src="js/jquery.counterup.min.js"></script>
<script src="js/steller.js"></script>
<script src="js/wow.min.js"></script>
<script src="js/jquery.magnific-popup.min.js"></script>
<script src="http://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/main.js"></script>
</body>

</html>