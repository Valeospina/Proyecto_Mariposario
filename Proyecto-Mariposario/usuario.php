<!doctype html>
<html class="no-js" lang="es">
    <?php
// Asegúrate de que la sesión esté iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtén el nombre de la página actual para el estado "active" del menú
$currentPage = basename($_SERVER['PHP_SELF']);
?>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="keywords" content="Eco Mariposas, perfil de usuario, jardín, naturaleza, mariposas">
        <meta name="description" content="Panel de usuario de Eco Mariposas, un espacio donde puedes gestionar tus pedidos, eventos y notificaciones.">
        <meta name='copyright' content='Eco Mariposas'>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        
        <title>Mi Perfil | Eco Mariposas</title>
        
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
                /* Tu color principal (Amarillo Dorado) */
                --main-yellow: #8BC34A; 
                --darker-yellow: #8BC34A; /* Un tono un poco más oscuro para hover/acentos sutiles si es necesario */

                /* Verdes complementarios */
                --main-green: #8BC34A; /* Un verde medio, natural y con buen contraste */
                --darker-green: #8BC34A; /* Un verde más oscuro para hover y acentos */
                --light-green-background: #E8F5E9; /* Un verde muy claro, casi blanco, si se necesita un fondo sutil */

                /* Neutros y Textos */
                --text-color: #333; /* Negro suave para texto principal */
                --light-text-color: #777; /* Gris más claro para texto secundario */
                --card-background: #fff; /* Fondo blanco para las tarjetas */
                --background-light: #f8f9fa; /* Fondo general de la página, muy sutil */
                --border-color: #e9e9e9; /* Gris claro para bordes */


            }
            
            body {
                font-family: 'Poppins', sans-serif;
                background-color: var(--background-light);
                color: var(--text-color);
            }
            .containerUsuario{
                max-width: 1200px;
                margin: 40px auto;
                padding: 20px;
                background-color: var(--card-background);
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
            
            .user-sidebar {
                background-color: var(--card-background);
                border-radius: 10px;
                box-shadow: 0 0 15px rgba(0,0,0,0.05);
                padding: 25px;
                margin-bottom: 30px;
            }
            
            .profile-info {
                text-align: center;
                padding-bottom: 20px;
                border-bottom: 1px solid var(--border-color);
                margin-bottom: 20px;
            }
            
            .profile-info img {
                width: 120px;
                height: 120px;
                border-radius: 50%;
                object-fit: cover;
                border: 4px solid var(--main-green); /* Verde para el borde de la imagen de perfil */
                margin-bottom: 15px;
            }
            
            .sidebar-menu li {
                margin-bottom: 10px;
            }
            
            .sidebar-menu li a {
                color: var(--text-color);
                padding: 10px 15px;
                display: block;
                border-radius: 5px;
                transition: all 0.3s ease;
            }
            
            .sidebar-menu li a:hover, .sidebar-menu li a.active {
                background-color: var(--main-green); /* Verde para el hover y activo del menú lateral */
                color: white;
            }
            
            .sidebar-menu li a i {
                margin-right: 10px;
            }
            
            .user-main-content {
                background-color: var(--card-background);
                border-radius: 10px;
                padding: 30px;
                box-shadow: 0 0 15px rgba(0,0,0,0.05);
            }
            
            .user-dashboard .card {
                border: none;
                border-radius: 10px;
                padding: 25px 20px;
                margin-bottom: 30px;
                box-shadow: 0 0 15px rgba(0,0,0,0.05);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
                background-color: var(--card-background); /* Asegurar fondo blanco para las tarjetas */
            }
            
            .user-dashboard .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            }
            
            .user-dashboard .card h3 {
                color: var(--text-color); /* Títulos de las tarjetas en texto oscuro estándar */
                margin-bottom: 15px;
                font-size: 22px;
            }
            
            .user-dashboard .card p {
                margin-bottom: 20px;
                font-size: 14px;
                color: var(--light-text-color); /* Descripción de las tarjetas en gris claro */
            }
            
            .user-dashboard .card .card-icon {
                font-size: 36px;
                margin-bottom: 15px;
                color: var(--main-green); /* Iconos de las tarjetas en verde */
            }
            
            .btn-primary {
                background-color: var(--main-green); /* Botones primarios en verde */
                border-color: var(--main-green);
                padding: 10px 25px;
                font-weight: 500;
                border-radius: 5px;
                transition: all 0.3s ease;
                color: white; /* Texto blanco para mejor contraste */
            }
            
            .btn-primary:hover {
                background-color: var(--darker-green); /* Hover de botones primarios en verde más oscuro */
                border-color: var(--darker-green);
            }
            
            .stats-number {
                font-size: 32px;
                font-weight: 600;
                color: var(--main-yellow); /* Números de estadísticas en tu amarillo principal */
                margin-bottom: 5px;
            }

            .stats-text {
                color: var(--text-color); /* Texto debajo de las estadísticas en color de texto estándar */
            }
            
            .activity-feed {
                margin-top: 30px;
            }
            
            .activity-item {
                padding: 15px;
                border-bottom: 1px solid var(--border-color);
                display: flex;
                align-items: center;
            }
            
            .activity-icon {
                width: 40px;
                height: 40px;
                background-color: var(--main-green); /* Iconos de actividad en verde */
                border-radius: 50%;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 15px;
            }
            
            .activity-content {
                flex: 1;
            }
            
            .activity-content h4 {
                font-size: 16px;
                margin-bottom: 5px;
                color: var(--text-color);
            }
            
            .activity-content p {
                font-size: 12px;
                color: var(--light-text-color);
                margin: 0;
            }
                      
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body>
    

            <?php include 'layout/nav.php'; ?>

        <section class="user-panel section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-3 col-md-4 col-12">
                        <div class="user-sidebar">
                            <div class="profile-info">
                                <img src="img/user-profile.jpg" alt="Foto de perfil">
                                <h3>Hola, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></h3>
                                <p>Miembro desde: Abril 2023</p>
                                <a href="user-settings.php" class="btn btn-sm btn-primary">Editar Perfil</a>
                            </div>
                            <ul class="sidebar-menu">
                                <li><a href="user-profile.php" class="active"><i class="fas fa-user"></i> Perfil</a></li>
                                <li><a href="MisPedidos.php"><i class="fas fa-shopping-bag"></i> Mis Pedidos</a></li>
                                <li><a href="eventosReservados.php"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
                                <li><a href="notificaciones.php"><i class="fas fa-bell"></i> Notificaciones <span class="badge badge-primary">3</span></a></li>
                                <li><a href="user-favorites.php"><i class="fas fa-heart"></i> Favoritos</a></li>
                                <li><a href="user-settings.php"><i class="fas fa-cog"></i> Configuración</a></li>
                                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                            </ul>
                        </div>
                        </div>
                    <div class="col-lg-9 col-md-8 col-12">
                        <div class="user-main-content">
                            <h2>Bienvenido al Panel de Usuario</h2>
                            <p>Administra tu experiencia en Eco Mariposas desde aquí.</p>
                            
                            <div class="row mb-4">
                                <div class="col-lg-4 col-md-4 col-6">
                                    <div class="text-center">
                                        <div class="stats-number">5</div>
                                        <div class="stats-text">Pedidos Totales</div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-6">
                                    <div class="text-center">
                                        <div class="stats-number">2</div>
                                        <div class="stats-text">Eventos Asistidos</div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-12 mt-md-0 mt-3">
                                    <div class="text-center">
                                        <div class="stats-number">8</div>
                                        <div class="stats-text">Productos Favoritos</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="user-dashboard">
                                <div class="row">
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="card">
                                            <div class="card-icon">
                                                <i class="fas fa-shopping-bag"></i>
                                            </div>
                                            <h3>Mis Pedidos</h3>
                                            <p>Revisa tu historial de pedidos y el estado de los mismos.</p>
                                            <a href="MisPedidos.php" class="btn btn-primary">Ver Pedidos</a>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="card">
                                            <div class="card-icon">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                            <h3>Mis Eventos</h3>
                                            <p>Consulta los eventos a los que estás registrado y su estado.</p>
                                            <a href="eventosReservados.php" class="btn btn-primary">Ver Eventos</a>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="card">
                                            <div class="card-icon">
                                                <i class="fas fa-bell"></i>
                                            </div>
                                            <h3>Notificaciones</h3>
                                            <p>Consulta las notificaciones recientes sobre tus actividades.</p>
                                            <a href="notificaciones.php" class="btn btn-primary">Ver Notificaciones</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="activity-feed">
                                <h3>Actividad Reciente</h3>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>Nuevo pedido realizado</h4>
                                        <p>Has realizado un pedido de plantas nativas. #ORD-2596</p>
                                    </div>
                                    <div class="activity-time">
                                        Hace 2 días
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>Inscripción a evento confirmada</h4>
                                        <p>Te has inscrito al taller "Conservación de Mariposas"</p>
                                    </div>
                                    <div class="activity-time">
                                        Hace 1 semana
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4>Nuevo favorito añadido</h4>
                                        <p>Has añadido "Kit Jardín de Mariposas" a tus favoritos</p>
                                    </div>
                                    <div class="activity-time">
                                        Hace 2 semanas
                                    </div>
                                </div>
                            </div>
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
                                <p>Somos un proyecto dedicado a la conservación y apreciación de mariposas y orquídeas en Costa Rica. 
                                    Promovemos el turismo sostenible y la educación ambiental.</p>
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
        <a href="#" class="scroll-up"><i class="fa fa-chevron-up"></i></a>
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
        
        <script>
            $(document).ready(function(){
                // Activar contador de estadísticas con animación
                $('.stats-number').each(function () {
                    $(this).prop('Counter', 0).animate({
                        Counter: $(this).text()
                    }, {
                        duration: 2000,
                        easing: 'swing',
                        step: function (now) {
                            $(this).text(Math.ceil(now));
                        }
                    });
                });
                
                // Notificación de bienvenida
                setTimeout(function(){
                    alert("¡Bienvenido a tu panel de usuario en Eco Mariposas! Aquí podrás gestionar todas tus actividades y preferencias.");
                }, 3000);
            });
        </script>
    </body>
</html>