<!doctype html>
<html class="no-js" lang="es">
<?php
// Después de verificar credenciales:
$_SESSION['id_usuario'] = $row['ID_Usuario'];

session_start();
$currentPage = basename($_SERVER['PHP_SELF']);

// 1) Obtén ID de usuario de la sesión y redirige si no existe
$userID = $_SESSION['id_usuario'] ?? null;
if (!$userID) {
    header('Location: logind.php');
    exit;
}

// 2) Conexión a la base de datos
require_once 'DB.php';

// 3) Procesar envío de formulario
$msg = '';
$msgClass = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']);
    $apellido  = trim($_POST['apellido']);
    $correo    = trim($_POST['correo']);
    $telefono  = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);

    $sqlUpd = "
        UPDATE Usuario
        SET Nombre    = ?,
            Apellido  = ?,
            Correo    = ?,
            Telefono  = ?,
            Direccion = ?
        WHERE ID_Usuario = ?
    ";
    $stmtUpd = $conn->prepare($sqlUpd);
    $stmtUpd->bind_param('sssssi', $nombre, $apellido, $correo, $telefono, $direccion, $userID);
    if ($stmtUpd->execute()) {
        $msg      = 'Perfil actualizado correctamente.';
        $msgClass = 'alert-success';
    } else {
        $msg      = 'Error al actualizar el perfil. Inténtalo de nuevo.';
        $msgClass = 'alert-danger';
    }
    $stmtUpd->close();
}

// 4) Obtener datos actuales del usuario
$sqlGet = "
    SELECT Nombre, Apellido, Correo, Contrasena, Telefono, Direccion
    FROM Usuario
    WHERE ID_Usuario = ?
";
$stmtGet = $conn->prepare($sqlGet);
$stmtGet->bind_param('i', $userID);
$stmtGet->execute();
$res = $stmtGet->get_result();
if ($res->num_rows === 0) {
    die("Usuario no encontrado");
}
$user = $res->fetch_assoc();
$stmtGet->close();
?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Eco Mariposas, perfil de usuario, jardín, naturaleza, mariposas">
    <meta name="description" content="Panel de usuario de Eco Mariposas, un espacio donde puedes editar tu perfil.">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Editar Perfil | Eco Mariposas</title>
    <link rel="icon" href="img/favicon.png">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/slicknav.min.css">
    <link rel="stylesheet" href="css/owl-carousel.css">
    <link rel="stylesheet" href="css/datepicker.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">

    <style>
      :root {
        --main-green: #8BC34A;
        --background-light: #f8f9fa;
        --card-background: #fff;
        --text-color: #333;
        --border-color: #e9e9e9;
      }
      body {
        background-color: var(--background-light);
        color: var(--text-color);
        font-family: 'Poppins', sans-serif;
      }
      .user-sidebar,
      .user-main-content {
        background-color: var(--card-background);
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
      }
      .user-sidebar {
        padding: 25px;
        margin-bottom: 30px;
      }
      .profile-info {
        text-align: center;
        margin-bottom: 20px;
      }
      .profile-info img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--main-green);
        margin-bottom: 15px;
      }
      .sidebar-menu li {
        margin-bottom: 10px;
      }
      .sidebar-menu li a {
        display: block;
        padding: 10px 15px;
        color: var(--text-color);
        border-radius: 5px;
        transition: background-color .3s;
      }
      .sidebar-menu li a.active,
      .sidebar-menu li a:hover {
        background-color: var(--main-green);
        color: #fff;
      }
      .user-main-content {
        padding: 30px;
      }
      .form-heading {
        color: var(--main-green);
        margin-bottom: 20px;
      }
      .form-group label {
        font-weight: 500;
      }
      .form-control:disabled {
        background-color: #e9ecef;
      }
      .btn-save {
        background-color: var(--main-green);
        color: #fff;
        padding: 10px 25px;
        border: none;
        border-radius: 5px;
        transition: background-color .3s;
      }
      .btn-save:hover {
        background-color: #6faf3f;
      }
    </style>
</head>

<body>

    <?php include 'layout/nav.php'; ?>

    <section class="user-panel section">
      <div class="container">
        <div class="row">

          <!-- Sidebar -->
          <div class="col-lg-3 col-md-4 col-12">
            <div class="user-sidebar">
              <div class="profile-info">
                <img src="img/user-profile.jpg" alt="Foto de perfil">
                <h3>Hola, <?= htmlspecialchars($user['Nombre'] . ' ' . $user['Apellido']) ?></h3>
              </div>
              <ul class="sidebar-menu">
                <li><a href="usuario.php"><i class="fas fa-user"></i> Perfil</a></li>
                <li><a href="MisPedidos.php"><i class="fas fa-shopping-bag"></i> Mis Pedidos</a></li>
                <li><a href="eventosReservados.php"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
                <li><a href="notificaciones.php"><i class="fas fa-bell"></i> Notificaciones</a></li>
                <li><a href="editarperfil.php" class="active"><i class="fas fa-edit"></i> Editar Perfil</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
              </ul>
            </div>
          </div>

          <!-- Main Content -->
          <div class="col-lg-9 col-md-8 col-12">
            <div class="user-main-content">
              <h2 class="form-heading">Editar Perfil</h2>

              <?php if ($msg): ?>
                <div class="alert <?= $msgClass ?>"><?= htmlspecialchars($msg) ?></div>
              <?php endif; ?>

              <form action="editarperfil.php" method="post">
                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" class="form-control"
                           value="<?= htmlspecialchars($user['Nombre'] ?? '') ?>" required>
                  </div>
                  <div class="form-group col-md-6">
                    <label for="apellido">Apellido</label>
                    <input type="text" id="apellido" name="apellido" class="form-control"
                           value="<?= htmlspecialchars($user['Apellido'] ?? '') ?>">
                  </div>
                </div>
                <div class="form-group">
                  <label for="correo">Correo electrónico</label>
                  <input type="email" id="correo" name="correo" class="form-control"
                         value="<?= htmlspecialchars($user['Correo'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label for="telefono">Teléfono</label>
                  <input type="tel" id="telefono" name="telefono" class="form-control"
                         value="<?= htmlspecialchars($user['Telefono'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label for="direccion">Dirección</label>
                  <textarea id="direccion" name="direccion" class="form-control" rows="2"><?= htmlspecialchars($user['Direccion'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                  <label for="contrasena">Contraseña</label>
                  <input type="password" id="contrasena" class="form-control" value="********" disabled>
                </div>
                <button type="submit" class="btn-save btn btn-lg">Guardar Cambios</button>
              </form>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- Footer Area -->
    <footer id="footer" class="footer">
      <div class="footer-top">
        <div class="container">
          <div class="row">
            <div class="col-lg-3 col-md-6 col-12">
              <div class="single-footer">
                <h2>Sobre Nosotros</h2>
                <p>Eco Mariposas es un emprendimiento dedicado a la conservación de mariposas y educación ambiental en Costa Rica. Trabajamos para promover ecosistemas saludables y sostenibles.</p>
                <ul class="social">
                  <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                  <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                  <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                  <li><a href="#"><i class="fab fa-youtube"></i></a></li>
                  <li><a href="#"><i class="fab fa-pinterest-p"></i></a></li>
                </ul>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
              <div class="single-footer f-link">
                <h2>Enlaces Rápidos</h2>
                <div class="row">
                  <div class="col-lg-6 col-md-6 col-12">
                    <ul>
                      <li><a href="index.php">Inicio</a></li>
                      <li><a href="about.php">Sobre Nosotros</a></li>
                      <li><a href="services.php">Servicios</a></li>
                      <li><a href="gallery.php">Galería</a></li>
                      <li><a href="blog.php">Blog</a></li>
                    </ul>
                  </div>
                  <div class="col-lg-6 col-md-6 col-12">
                    <ul>
                      <li><a href="workshops.php">Talleres</a></li>
                      <li><a href="conservation.php">Conservación</a></li>
                      <li><a href="testimonials.php">Testimonios</a></li>
                      <li><a href="faq.php">Preguntas Frecuentes</a></li>
                      <li><a href="contact.php">Contáctanos</a></li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
              <div class="single-footer">
                <h2>Boletín Informativo</h2>
                <p>Suscríbete para recibir actualizaciones sobre eventos, talleres y nuevos productos.</p>
                <form class="newsletter-form">
                  <input type="email" placeholder="Tu correo electrónico" required>
                  <button type="submit" class="btn btn-primary">Suscribir</button>
                </form>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
              <div class="single-footer f-link">
                <h2>Contáctanos</h2>
                <ul>
                  <li><a href="#"><i class="fas fa-map-marker-alt"></i>200 metros sur de la escuela bajo la paz</a></li>
                  <li><a href="tel:+50662525969"><i class="fas fa-phone-alt"></i>+506 6252-5969</a></li>
                  <li><a href="mailto:soportejardinlapaz@gmail.com"><i class="fas fa-envelope"></i>soportejardinlapaz@gmail.com</a></li>
                  <li><a href="#"><i class="fas fa-clock"></i>Lunes - Sábado: 8:00 AM - 5:00 PM</a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <div class="container">
          <div class="row">
            <div class="col-12">
              <p>&copy; 2023 Eco Mariposas. Todos los derechos reservados.</p>
            </div>
            <div class="col-12">
              <ul class="footer-bottom-links">
                <li><a href="privacy-policy.php">Política de Privacidad</a></li>
                <li><a href="terms-conditions.php">Términos y Condiciones</a></li>
                <li><a href="cookie-policy.php">Política de Cookies</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </footer>
    <!-- End Footer Area -->

    <!-- Scroll Up -->
    <a href="#" class="scroll-up"><i class="fa fa-chevron-up"></i></a>

    <!-- JS Files -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
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
    <script src="js/waypoints.min.js"></script>
    <script src="js/main.js"></script>

</body>
</html>
