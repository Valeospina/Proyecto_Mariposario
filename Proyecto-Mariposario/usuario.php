<!doctype html>
<html class="no-js" lang="es">
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Conexión a la base de datos
require_once 'DB.php';

$userID = $_SESSION['user_id'] ?? null;

// Consultar foto de perfil desde BD
$fotoPerfil = "img/default-user.png"; // Por defecto
if ($userID) {
    $sqlFoto = "SELECT Foto_Perfil FROM Usuario WHERE ID_Usuario = ?";
    $stmtFoto = $conn->prepare($sqlFoto);
    $stmtFoto->bind_param('i', $userID);
    $stmtFoto->execute();
    $resultFoto = $stmtFoto->get_result()->fetch_assoc();
    if (!empty($resultFoto['Foto_Perfil'])) {
        $fotoPerfil = htmlspecialchars($resultFoto['Foto_Perfil']);
    }
    $stmtFoto->close();
}

// 3.1) Pedidos Totales
$sqlPed = "SELECT COUNT(*) AS total_pedidos FROM Pedido WHERE ID_Usuario = ?";
$stmtPed = $conn->prepare($sqlPed);
$stmtPed->bind_param('i', $userID);
$stmtPed->execute();
$total_pedidos = $stmtPed->get_result()->fetch_assoc()['total_pedidos'];
$stmtPed->close();

// 3.2) Eventos Asistidos (Estado = 'Aprobada')
$sqlEvt = "SELECT COUNT(*) AS total_eventos FROM Reserva WHERE ID_Usuario = ? AND Estado = 'Aprobada'";
$stmtEvt = $conn->prepare($sqlEvt);
$stmtEvt->bind_param('i', $userID);
$stmtEvt->execute();
$total_eventos = $stmtEvt->get_result()->fetch_assoc()['total_eventos'];
$stmtEvt->close();

// 3.3) Actividad Reciente (últimos 5 registros)
$sqlAct = "
    SELECT Tipo_Evento, Descripcion, Fecha_Hora
    FROM Bitacora
    WHERE ID_Usuario = ?
    ORDER BY Fecha_Hora DESC
    LIMIT 5
";
$stmtAct = $conn->prepare($sqlAct);
$stmtAct->bind_param('i', $userID);
$stmtAct->execute();
$resultAct = $stmtAct->get_result();
$stmtAct->close();
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
    <link rel="stylesheet" href="css/tienda.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    
    <style>
        :root {
            --main-yellow: #8BC34A;
            --darker-yellow: #8BC34A;
            --main-green: #8BC34A;
            --darker-green: #8BC34A;
            --light-green-background: #E8F5E9;
            --text-color: #333;
            --light-text-color: #777;
            --card-background: #fff;
            --background-light: #f8f9fa;
            --border-color: #e9e9e9;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-light);
            color: var(--text-color);
        }
        .containerUsuario {
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
            border: 4px solid var(--main-green);
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
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background-color: var(--main-green);
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
            background-color: var(--card-background);
        }
        .user-dashboard .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .user-dashboard .card h3 {
            color: var(--text-color);
            margin-bottom: 15px;
            font-size: 22px;
        }
        .user-dashboard .card p {
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--light-text-color);
        }
        .user-dashboard .card .card-icon {
            font-size: 36px;
            margin-bottom: 15px;
            color: var(--main-green);
        }
        .btn-primary {
            background-color: var(--main-green);
            border-color: var(--main-green);
            padding: 10px 25px;
            font-weight: 500;
            border-radius: 5px;
            transition: all 0.3s ease;
            color: white;
        }
        .btn-primary:hover {
            background-color: var(--darker-green);
            border-color: var(--darker-green);
        }
        .stats-number {
            font-size: 32px;
            font-weight: 600;
            color: var(--main-yellow);
            margin-bottom: 5px;
        }
        .stats-text {
            color: var(--text-color);
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
            background-color: var(--main-green);
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
                <!-- Sidebar -->
                <div class="col-lg-3 col-md-4 col-12">
                    <div class="user-sidebar">
                        <div class="profile-info">
                            <img src="<?= $fotoPerfil ?>" alt="Foto de perfil">
                            <h3>Hola, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></h3>
                            <p>Miembro desde: Abril 2023</p>
                            <a href="editarperfil.php" class="btn btn-sm btn-primary">Editar Perfil</a>
                        </div>
                        <ul class="sidebar-menu">
                            <li><a href="usuario.php" class="<?= $currentPage=='user-profile.php'?'active':'' ?>"><i class="fas fa-user"></i> Perfil</a></li>
                            <li><a href="MisPedidos.php" class="<?= $currentPage=='MisPedidos.php'?'active':'' ?>"><i class="fas fa-shopping-bag"></i> Mis Pedidos</a></li>
                            <li><a href="eventosReservados.php" class="<?= $currentPage=='eventosReservados.php'?'active':'' ?>"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
                            <li><a href="notificaciones.php" class="<?= $currentPage=='notificaciones.php'?'active':'' ?>"><i class="fas fa-bell"></i> Notificaciones <span class="badge badge-primary">3</span></a></li>
                            <li><a href="cliente-chat.php" class="<?= $currentPage=='cliente-chat.php'?'active':'' ?>"><i class="fas fa-cog"></i> Soporte</a></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                        </ul>
                    </div>
                </div>
                <!-- Main Content -->
                <div class="col-lg-9 col-md-8 col-12">
                    <div class="user-main-content">
                        <h2>Bienvenido al Panel de Usuario</h2>
                        <p>Administra tu experiencia en Eco Mariposas desde aquí.</p>
                        
                        <!-- Estadísticas dinámicas -->
                        <div class="row mb-4">
                            <div class="col-lg-4 col-md-4 col-6">
                                <div class="text-center">
                                    <div class="stats-number"><?= $total_pedidos ?></div>
                                    <div class="stats-text">Pedidos Totales</div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-6">
                                <div class="text-center">
                                    <div class="stats-number"><?= $total_eventos ?></div>
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

                        <!-- Actividad Reciente dinámica -->
                        <div class="activity-feed">
                            <h3>Actividad Reciente</h3>

                            <?php if ($resultAct->num_rows > 0): ?>
                                <?php while ($act = $resultAct->fetch_assoc()): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-history"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h4><?= htmlspecialchars($act['Tipo_Evento']) ?></h4>
                                            <p><?= htmlspecialchars($act['Descripcion']) ?></p>
                                        </div>
                                        <div class="activity-time">
                                            <?= date('d/m/Y H:i', strtotime($act['Fecha_Hora'])) ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">No hay actividad reciente.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Dashboard Cards -->
                        <div class="user-dashboard">
                            <div class="row">
                                <div class="col-lg-4 col-md-6 col-12">
                                    <div class="card">
                                        <div class="card-icon"><i class="fas fa-shopping-bag"></i></div>
                                        <h3>Mis Pedidos</h3>
                                        <p>Revisa tu historial de pedidos y el estado de los mismos.</p>
                                        <a href="MisPedidos.php" class="btn btn-primary">Ver Pedidos</a>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-12">
                                    <div class="card">
                                        <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
                                        <h3>Mis Eventos</h3>
                                        <p>Consulta los eventos a los que estás registrado y su estado.</p>
                                        <a href="eventosReservados.php" class="btn btn-primary">Ver Eventos</a>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-12">
                                    <div class="card">
                                        <div class="card-icon"><i class="fas fa-bell"></i></div>
                                        <h3>Notificaciones</h3>
                                        <p>Consulta las notificaciones recientes sobre tus actividades.</p>
                                        <a href="notificaciones.php" class="btn btn-primary">Ver Notificaciones</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

 <?php include 'layout/Footer.php'; ?>

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

    <script>
        $(document).ready(function(){
            // Animar contador
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
            // Alerta bienvenida
            setTimeout(function(){
                alert("¡Bienvenido a tu panel de usuario en Eco Mariposas! Aquí podrás gestionar todas tus actividades y preferencias.");
            }, 3000);
        });
    </script>

</body>
</html>
