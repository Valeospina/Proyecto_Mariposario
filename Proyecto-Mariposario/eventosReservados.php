<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obtén el nombre de la página actual para el estado "active" del menú
$currentPage = basename($_SERVER['PHP_SELF']);

// Conexión a la base de datos
include 'DB.php';

// Definir el ID del usuario desde la sesión
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Usuario';

// Foto de perfil
$fotoPerfil = "img/default-user.png";
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Eco Mariposas, perfil de usuario, jardín, naturaleza, mariposas">
    <meta name="description" content="Panel de usuario de Eco Mariposas, un espacio donde puedes gestionar tus pedidos, eventos y notificaciones.">
    <meta name='copyright' content='Eco Mariposas'>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <title>Mis Reservas | Eco Mariposas</title>
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
<link rel="stylesheet" href="./css/eventosR.css">
<link rel="stylesheet" href="css/responsive.css">
</head>

<body>
    <?php include 'layout/nav.php'; ?>

    <section class="user-panel section">
        <div class="container">
            <div class="row">
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
                <div class="col-lg-9 col-md-8 col-12">
                    <div class="user-main-content">
                        <h1>Mis Reservas</h1>

                        <?php
                        // Consulta dinámica de reservas
                        $sql = "
                            SELECT
                                r.ID_Reserva,
                                e.Nombre       AS evento,
                                e.Fecha        AS fecha_evento,
                                e.Hora         AS hora_evento,
                                r.Cantidad_Personas,
                                r.Fecha_Reserva,
                                r.Estado
                            FROM Reserva r
                            JOIN Evento e ON r.ID_Evento = e.ID_Evento
                            WHERE r.ID_Usuario = ?
                            ORDER BY e.Fecha DESC
                        ";
                        
                        $stmt = $conn->prepare($sql);
                        if (!$stmt) {
                            echo "<div class='alert alert-danger'>Error en la consulta: " . $conn->error . "</div>";
                        } else {
                            $stmt->bind_param('i', $userId); // CORRECCIÓN: cambié $userID por $userId
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                        ?>
                                <div class="reservation-item order-item">
                                    <h2><?= htmlspecialchars($row['evento']) ?></h2> <!-- CORRECCIÓN: cambié 'Evento' por 'evento' -->
                                    <div class="reservation-details order-details">
                                        <p><span>Fecha:</span> <?= date('d/m/Y', strtotime($row['fecha_evento'])) ?></p>
                                        <p><span>Hora:</span> <?= htmlspecialchars($row['hora_evento']) ?></p>
                                        <p><span>Cantidad de personas:</span> <?= (int)$row['Cantidad_Personas'] ?></p>
                                    </div>
                                    <p class="status-date">Última actualización: <?= date('d/m/Y', strtotime($row['Fecha_Reserva'])) ?></p>
                                </div>
                        <?php
                                endwhile;
                                $stmt->close();
                            else:
                        ?>
                                <div class="reservation-item text-center">
                                    <i class="fas fa-calendar-times fa-3x mb-3 text-muted"></i>
                                    <h2>¡Aún no tienes reservas!</h2>
                                    <p>Parece que todavía no has realizado ninguna reserva con nosotros.<br>¡Explora nuestros eventos y reserva tu experiencia!</p>
                                    <a href="eventos.php" class="btn">Ver eventos disponibles</a>
                                </div>
                        <?php
                            endif;
                        }
                        $conn->close();
                        ?>

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
</body>
</html>