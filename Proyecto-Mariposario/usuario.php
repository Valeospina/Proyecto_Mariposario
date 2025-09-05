<!doctype html>
<html class="no-js" lang="es">
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Conexión a la base de datos
require_once 'DB.php';

$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Usuario';

// Consultar foto de perfil desde BD
$fotoPerfil = "img/default-user.png"; // Por defecto
if ($userId) {
    $sqlFoto = "SELECT Foto_Perfil FROM Usuario WHERE ID_Usuario = ?";
    $stmtFoto = $conn->prepare($sqlFoto);
    $stmtFoto->bind_param('i', $userId);
    $stmtFoto->execute();
    $resultFoto = $stmtFoto->get_result()->fetch_assoc();
    if (!empty($resultFoto['Foto_Perfil'])) {
        $fotoPerfil = htmlspecialchars($resultFoto['Foto_Perfil']);
    }
    $stmtFoto->close();
}

// Consultar notificaciones del usuario
$notificaciones = [];
$totalNoLeidas = 0;

if ($userId) {
$sql = "
  SELECT 
    ID_Notificacion,
    Categoria AS Tipo_Notificacion,  -- para ser 100% compatible con tu UI actual
    Subtipo,
    Mensaje,
    Fecha_Notificacion,
    Mostrar_Desde,
    Leida,
    ID_Referencia,
    Accion_URL
  FROM Notificacion
  WHERE ID_Usuario = ? 
    AND Mostrar_Desde <= NOW()
  ORDER BY Fecha_Notificacion DESC
";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $notificaciones[] = $row;
        if (!$row['Leida']) {
            $totalNoLeidas++;
        }
    }
    $stmt->close();
}

// Función para obtener el icono según el tipo de notificación
function getNotificationIcon($tipo) {
    switch($tipo) {
        case 'Bienvenida': return 'fa-heart';
        case 'Pedido': return 'fa-shopping-cart';
        case 'Evento': return 'fa-calendar-alt';
        case 'Sistema': return 'fa-cog';
        case 'Promoción': return 'fa-tag';
        default: return 'fa-bell';
    }
}

// Función para obtener la clase de color según el tipo
function getNotificationColor($tipo) {
    switch($tipo) {
        case 'Bienvenida': return '#e91e63';
        case 'Pedido': return '#4caf50';
        case 'Evento': return '#2196f3';
        case 'Sistema': return '#ff9800';
        case 'Promoción': return '#9c27b0';
        default: return '#8BC34A';
    }
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
    
<!-- Favicon -->
<link rel="icon" href="img/favicon.png">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Librerías externas -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- CSS base -->
<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/nice-select.css">
<link rel="stylesheet" href="css/slicknav.min.css">
<link rel="stylesheet" href="css/owl-carousel.css">
<link rel="stylesheet" href="css/datepicker.css">
<link rel="stylesheet" href="css/animate.min.css">
<link rel="stylesheet" href="css/magnific-popup.css">
<link rel="stylesheet" href="css/normalize.css">

<!-- Estilos personalizados -->

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="./css/usuario.css">
<link rel="stylesheet" href="css/responsive.css">

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
                            <a href="editarperfil.php" class="btn btn-sm btn-primary">Editar Perfil</a>
                        </div>
                        <ul class="sidebar-menu">
                            <li><a href="usuario.php" class="<?= $currentPage=='usuario.php'?'active':'' ?>"><i class="fas fa-user"></i> Perfil</a></li>
                            <li><a href="MisPedidos.php" class="<?= $currentPage=='MisPedidos.php'?'active':'' ?>"><i class="fas fa-shopping-bag"></i> Mis Pedidos</a></li>
                            <li><a href="eventosReservados.php" class="<?= $currentPage=='eventosReservados.php'?'active':'' ?>"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
                            <li><a href="notificaciones.php" class="<?= $currentPage=='notificaciones.php'?'active':'' ?>"><i class="fas fa-bell"></i> Notificaciones 
                                <?php if ($totalNoLeidas > 0): ?>
                                    <span class="badge"><?= $totalNoLeidas ?></span>
                                <?php endif; ?>
                            </a></li>
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
