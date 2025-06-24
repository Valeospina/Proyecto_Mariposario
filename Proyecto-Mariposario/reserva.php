<?php
require_once 'DB.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $telefono = htmlspecialchars(trim($_POST['telefono']));
    $evento_id = intval($_POST['evento']);
    $personas = intval($_POST['personas']);
    $mensaje = htmlspecialchars(trim($_POST['mensaje'])); // descripción

    // Obtener nombre y fecha del evento desde la base de datos
    $stmt = $conn->prepare("SELECT Nombre, Fecha FROM Evento WHERE ID_Evento = ?");
    $stmt->bind_param("i", $evento_id);
    $stmt->execute();
    $stmt->bind_result($nombre_evento, $fecha_evento);
    $evento_valido = $stmt->fetch();
    $stmt->close();

    if (!$evento_valido) {
        die("<div style='color: red; font-weight: bold;'>Error: El evento seleccionado no es válido.</div>");
    }

    // Verificar si ya existe una reserva en esa fecha para ese evento
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Reserva WHERE Fecha_Reserva = ? AND ID_Evento = ?");
    $stmt->bind_param("si", $fecha_evento, $evento_id);
    $stmt->execute();
    $stmt->bind_result($existe);
    $stmt->fetch();
    $stmt->close();

    if ($existe > 0) {
        echo "<div style='background-color: #ffe6e6; padding: 15px; border-radius: 10px; border: 1px solid red;'>
                <h3 style='color: red;'>Ya hay una reserva para ese evento en esa fecha.</h3>
                <a href='menu.php' style='display: inline-block; margin-top: 15px; background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Volver al Menú</a>
              </div>";
    } else {
        // Insertar la reserva con la fecha obtenida del evento
        $stmt = $conn->prepare("INSERT INTO Reserva (ID_Evento, cantidad_personas, Fecha_Reserva, telefono, correo, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $evento_id, $personas, $fecha_evento, $telefono, $email, $mensaje);

        if ($stmt->execute()) {
            echo "
            <div style='background: #f9f9f9; border: 1px solid #d1e7dd; border-left: 5px solid #198754; padding: 30px; border-radius: 10px; max-width: 700px; margin: 30px auto; font-family: Arial, sans-serif; box-shadow: 0 4px 8px rgba(0,0,0,0.05);'>
                <h2 style='color: #198754; margin-bottom: 10px;'>
                    <img src='https://img.icons8.com/color/48/000000/ok--v1.png' style='vertical-align: middle; width: 30px; height: 30px;'/>
                    Reserva Confirmada
                </h2>
                <ul style='list-style: none; padding: 0; font-size: 15px; color: #555;'>
                    <li><strong>Nombre:</strong> $nombre</li>
                    <li><strong>Email:</strong> $email</li>
                    <li><strong>Teléfono:</strong> $telefono</li>
                    <li><strong>Fecha del Evento:</strong> " . date("d/m/Y", strtotime($fecha_evento)) . "</li>
                    <li><strong>Tipo de Evento:</strong> $nombre_evento</li>
                    <li><strong>Personas:</strong> $personas</li>
                    <li><strong>Comentarios:</strong> " . (!empty($mensaje) ? nl2br($mensaje) : 'Ninguno') . "</li>
                </ul>
                <a href='index.php' style='display: inline-block; margin-top: 20px; background-color: #198754; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Volver al inicio</a>
            </div>";
        } else {
            echo "<div style='color: red;'>Error al guardar la reserva: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }

    $conn->close();
}
?>



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
