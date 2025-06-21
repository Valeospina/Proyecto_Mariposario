<?php
session_start();

// Redirige a login si no hay sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Redirige a dashboard de admin si el usuario es administrador
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
    header('Location: admin/dashboard.php');
    exit;
}
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
    
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    
</head>

<body> 
    <?php include 'layout/nav.php'; ?>
 
    <section class="slider">
        <div class="hero-slider">
            <div class="single-slider" style="background-image:url('img/Mariposa1.jpg')">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="text">
                                <h1>Descubrí la <span>Magia</span> de las mariposas y la belleza de las <span>Orquídeas</span></h1>
                                <p>Viví una experiencia única en nuestro mariposario y llevate a casa la elegancia natural de nuestras orquídeas cultivadas con amor.</p>
                                <div class="button">
                                    <a href="Reserva.html" class="btn">Reservar visita</a>
                                    <a href="tienda.php" class="btn primary">Ver tienda</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="single-slider" style="background-image:url('img/orquidea1.jpg')">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="text">
                                <h1>Llevá la <span>Belleza</span> de la naturaleza a tu hogar con nuestras <span>Orquídeas</span></h1>
                                <p>Explorá nuestra colección exclusiva de orquídeas, perfectas para regalar o decorar tu espacio con elegancia y color.</p>
                                <div class="button">
                                    <a href="tienda.php" class="btn">Explorar tienda</a>
                                    <a href="contact.php" class="btn primary">Contáctanos</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="single-slider" style="background-image:url('img/mariposa2.jpg')">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="text">
                                <h1>Un <span>Refugio</span> natural donde las mariposas vuelan libres</h1>
                                <p>Conocé nuestro mariposario, un espacio de conservación y aprendizaje para todas las edades. ¡Una visita que no vas a olvidar!</p>
                                <div class="button">
                                    <a href="Reserva.html" class="btn">Reservar recorrido</a>
                                    <a href="Info.html" class="btn primary">Más información</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
    </section>
    ---

    <section class="schedule">
        <div class="container">
            <div class="schedule-inner">
                <div class="row">
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="single-schedule first">
                            <div class="inner">
                                <div class="single-content">
                                    <span>Explorá la naturaleza</span>
                                    <h4>Visitas guiadas</h4>
                                    <p>Disfrutá de un recorrido educativo por el mariposario y conocé el ciclo de vida de nuestras mariposas.</p>
                                    <a href="Reserva.html">Reservar ahora <i class="fa fa-long-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="single-schedule middle">
                            <div class="inner">
                                <div class="single-content">
                                    <span>Aprendé y divertite</span>
                                    <h4>Talleres y actividades</h4>
                                    <p>Ofrecemos talleres sobre cuidado de orquídeas, crianza de mariposas y actividades para todas las edades.</p>
                                    <a href="Info.html">Ver actividades <i class="fa fa-long-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12 col-12">
                        <div class="single-schedule last">
                            <div class="inner">
                                <div class="single-content">
                                    <span>Te esperamos</span>
                                    <h4>Horarios de atención</h4>
                                    <ul class="time-sidual">
                                        <li class="day">Lunes a Viernes <span>9:00 - 17:00</span></li>
                                        <li class="day">Sábados y Domingos <span>10:00 - 18:00</span></li>
                                        <li class="day">Feriados <span>10:00 - 15:00</span></li>
                                    </ul>
                                    <a href="contact.php">Contáctanos <i class="fa fa-long-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    ---

    <section class="Feautes section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title">
                        <h2>Conectá con la naturaleza y llevate un pedacito a casa</h2>
                        <p>Explorá el mundo mágico de las mariposas y descubrí nuestras orquídeas únicas disponibles en tienda.</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-12">
                    <a href="Info.html">
                        <div class="single-features">
                            <div class="signle-icon">
                                <i class="icofont icofont-butterfly-alt"></i>
                            </div>
                            <h3>Ciclo de vida de la mariposa</h3>
                            <p>Aprendé sobre la metamorfosis: desde el huevo, la oruga, la crisálida hasta convertirse en una mariposa.</p>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-12">
                    <a href="tienda.php">
                        <div class="single-features">
                            <div class="signle-icon">
                                <i class="icofont icofont-shopping-cart"></i>
                            </div>
                            <h3>Comprar orquídeas</h3>
                            <p>Visitá nuestra tienda en línea, explorá las especies disponibles y agregá tus favoritas al carrito.</p>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-12">
                    <a href="contact.php">
                        <div class="single-features last">
                            <div class="signle-icon">
                                <i class="icofont icofont-delivery-time"></i>
                            </div>
                            <h3>Entrega o recolección</h3>
                            <p>Recibí tus orquídeas en casa o retíralas directamente en el mariposario. ¡Simple y rápido!</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <div id="fun-facts" class="fun-facts section overlay">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="single-fun">
                        <i class="icofont icofont-butterfly-alt"></i>
                        <div class="content">
                            <span class="counter">150</span>
                            <p>Especies de Mariposas</p>
                        </div>
                    </div>
                    </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="single-fun">
                        <i class="icofont-flora-flower"></i>
                        <div class="content">
                            <span class="counter">80</span>
                            <p>Tipos de Orquídeas</p>
                        </div>
                    </div>
                    </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="single-fun">
                        <i class="icofont icofont-heart"></i>
                        <div class="content">
                            <span class="counter">5000</span>
                            <p>Visitas Anuales</p>
                        </div>
                    </div>
                    </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="single-fun">
                        <i class="icofont icofont-clock-time"></i>
                        <div class="content">
                            <span class="counter">10</span>
                            <p>Años de Experiencia</p>
                        </div>
                    </div>
               </div>
            </div>
        </div>
    </div>
    <section class="why-choose section">
        <div class="container">
        <div class="row">
            <div class="col-lg-12">
            <div class="section-title">
                <h2>Descubrí el Encanto de la Naturaleza con Nosotros</h2>
                <p>Vení a conocer nuestro mariposario y la belleza única de nuestras orquídeas. ¡Una experiencia inolvidable para toda la familia!</p>
            </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-12">
            <div class="choose-left">
                <h3>¿Quiénes Somos?</h3>
                <p>En nuestro mariposario, podrás disfrutar de un ambiente lleno de color y naturaleza, donde las mariposas viven en su hábitat natural. Además, ofrecemos una exclusiva colección de orquídeas para los amantes de la jardinería y la naturaleza.</p>
                <div class="row">
                <div class="col-lg-6">
                    <ul class="list">
                    <li><i class="fa fa-caret-right"></i>Mariposario interactivo con diversas especies. </li>
                    <li><i class="fa fa-caret-right"></i>Venta de orquídeas cultivadas con amor. </li>
                    <li><i class="fa fa-caret-right"></i>Actividades educativas para toda la familia.</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="list">
                    <li><i class="fa fa-caret-right"></i>Espacio ideal para eventos especiales. </li>
                    <li><i class="fa fa-caret-right"></i>Variedad única de orquídeas disponibles.</li>
                    <li><i class="fa fa-caret-right"></i>Un lugar de respeto y conservación de la biodiversidad.</li>
                    </ul>
                </div>
                </div>
            </div>
            </div>
            <div class="col-lg-6 col-12">
            <div class="choose-right">
                <div class="video-image">
                <div class="promo-video">
                    <div class="waves-block">
                    <div class="waves wave-1"></div>
                    <div class="waves wave-2"></div>
                    <div class="waves wave-3"></div>
                    </div>
                </div>
                <a href="https://www.youtube.com/watch?v=RFVXy6CRVR4" class="video video-popup mfp-iframe"><i class="fa fa-play"></i></a>
                </div>
            </div>
            </div>
        </div>
        </div>
    </section>
    <section class="call-action overlay" data-stellar-background-ratio="0.5">
        <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-12">
            <div class="content">
                <h2>¿Te apasiona la naturaleza? Ven a ver nuestras mariposas y orquídeas únicas</h2>
                <p>Explora un mundo lleno de mariposas coloridas y orquídeas únicas. En nuestro mariposario podrás disfrutar de un ambiente natural y aprender sobre estas maravillosas criaturas. ¡No esperes más y ven a vivir esta experiencia!</p>
                <div class="button">
                <a href="Reserva.html" class="btn">Visítanos Ahora</a>
                <a href="tienda.php" class="btn second">Descubre Nuestras Orquídeas<i class="fa fa-long-arrow-right"></i></a>
                </div>
            </div>
            </div>
        </div>
        </div>
    </section>
    <section class="portfolio section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title">
                    <h2>Explora la Belleza de Nuestras Mariposas y Orquídeas</h2>
                    <p>Descubre la magia de las mariposas y orquídeas en nuestro mariposario. Aquí podrás ver y aprender sobre diferentes especies mientras disfrutas de un ambiente natural y tranquilo.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-12">
                    <div class="owl-carousel portfolio-slider">
                    <div class="single-pf">
                        <img src="img/orquidea_cattleya.jpg" alt="Mariposa">
                        <a href="GaleriaOrquideas.html" class="btn">Ver Detalles</a>
                    </div>
                    <div class="single-pf">
                        <img src="img/orquidea1.jpg" alt="Orquídea">
                        <a href="GaleriaOrquideas.html" class="btn">Ver Detalles</a>
                    </div>
                    <div class="single-pf">
                        <img src="img/mariposa2.jpg" alt="Mariposa">
                        <a href="GaleriaMariposas.html" class="btn">Ver Detalles</a>
                    </div>
                    <div class="single-pf">
                        <img src="img/Mariposa1.jpg" alt="Orquídea">
                        <a href="GaleriaOrquideas.html" class="btn">Ver Detalles</a>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="newsletter section">
        <div class="container">
            <div class="row ">
                <div class="col-lg-6 col-12">
                    <div class="subscribe-text">
                        <h6>Suscríbete para recibir noticias</h6>
                        <p>Mantente al tanto de nuevos eventos, promociones y más.</p>
                    </div>
                </div>
                <div class="col-lg-6 col-12">
                    <div class="subscribe-form">
                        <form action="suscribir.php" method="post" class="newsletter-inner">
                            <input name="email" placeholder="Tu correo electrónico" class="common-input" type="email" required>
                            <button class="btn">Suscribirme</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>          
    <footer id="footer" class="footer">
        <div class="footer-top">
            <div class="container">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="single-footer">
                            <h2>Sobre Nosotros</h2>
                            <p>Somos un proyecto dedicado a la conservación y apreciación de mariposas y orquídeas en Costa Rica. Promovemos el turismo sostenible y la educación ambiental.</p>
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
                                        <li><a href="index.php"><i class="fa fa-caret-right" aria-hidden="true"></i>Inicio</a></li>
                                        <li><a href="Reserva.html"><i class="fa fa-caret-right" aria-hidden="true"></i>Reservaciones</a></li>
                                        <li><a href="GaleriaMariposas.html"><i class="fa fa-caret-right" aria-hidden="true"></i>Galería</a></li>
                                        <li><a href="Info.html"><i class="fa fa-caret-right" aria-hidden="true"></i>Actividades</a></li>
                                        <li><a href="contact.php"><i class="fa fa-caret-right" aria-hidden="true"></i>Contáctanos</a></li>
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