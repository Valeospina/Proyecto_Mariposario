<!doctype html>
<html class="no-js" lang="es">
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Site keywords here">
    <meta name="description" content="">
    <meta name='copyright' content=''>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <title>Jardin De Mariposas</title>
    
    <link rel="icon" href="img/favicon.png">
    
    <link href="https://fonts.googleapis.com/css?family=Poppins:200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

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
    <link rel="stylesheet" href="css/eventos.css">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">

</head>
<body>
</header>
<!DOCTYPE html>
<html lang="es">

    <?php include 'layout/nav2.php'; ?>

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

 <?php include 'layout/Footer.php'; ?>

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