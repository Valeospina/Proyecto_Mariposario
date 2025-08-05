<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Nombre de la página actual
$currentPage = basename($_SERVER['PHP_SELF']);

// Conexión a la base de datos
require_once 'DB.php';

// Usuario actual
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

// Consultar notificaciones
$notificaciones = [];
$totalNoLeidas = 0;
$totalTodas = 0;

if ($userId) {
    $sqlNotif = "SELECT * FROM Notificacion WHERE ID_Usuario = ? ORDER BY Fecha_Notificacion DESC";
    $stmtNotif = $conn->prepare($sqlNotif);
    $stmtNotif->bind_param('i', $userId);
    $stmtNotif->execute();
    $resultNotif = $stmtNotif->get_result();
    while ($row = $resultNotif->fetch_assoc()) {
        $notificaciones[] = $row;
        if (!$row['Leida']) {
            $totalNoLeidas++;
        }
        $totalTodas++;
    }
    $stmtNotif->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Eco Mariposas, perfil de usuario, notificaciones, pedidos, eventos">
    <meta name="description" content="Centro de Notificaciones de Eco Mariposas">
    <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>

    <title>Notificaciones | Eco Mariposas</title>

    <link rel="icon" href="img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/slicknav.min.css">
    <link rel="stylesheet" href="css/owl-carousel.css">
    <link rel="stylesheet" href="css/datepicker.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        /* Contenedor principal */
        .container {
            max-width: 1200px;
            margin: 40px auto;
        }

        /* Sidebar */
        .user-sidebar {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .profile-info {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eaeaea;
            margin-bottom: 20px;
        }

        .profile-info img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #8BC34A;
        }

        .profile-info h3 {
            font-size: 18px;
            margin-top: 15px;
        }

        .profile-info p {
            color: #777;
            font-size: 14px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            margin-bottom: 12px;
        }

        .sidebar-menu li a {
            text-decoration: none;
            color: #333;
            padding: 10px 15px;
            display: block;
            border-radius: 6px;
            transition: 0.3s;
        }

        .sidebar-menu li a i {
            margin-right: 10px;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: #8BC34A;
            color: #fff;
        }

        /* Área de notificaciones */
        .user-main-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .notifications-header h2 {
            font-size: 24px;
            font-weight: 600;
        }

        .notification-actions button,
        .notification-actions select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #fff;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .notification-actions button:hover {
            background: #8BC34A;
            color: #fff;
        }

        /* Tarjeta de notificación */
        .notification-item {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            border-left: 5px solid transparent;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            transition: background 0.3s;
        }

        .notification-item.unread {
            background: #f9fff4;
            border-left-color: #8BC34A;
        }

        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #8BC34A;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            font-size: 20px;
            margin-right: 15px;
        }

        .notification-content h4 {
            font-size: 16px;
            margin: 0 0 5px;
        }

        .notification-content p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .notification-meta {
            font-size: 12px;
            color: #aaa;
            margin-top: 5px;
        }

        .notification-actions-buttons {
            margin-left: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .notification-actions-buttons button {
            background: none;
            border: none;
            font-size: 14px;
            color: #888;
            cursor: pointer;
        }

        .notification-actions-buttons button:hover {
            color: #8BC34A;
        }

        .btn-opinion {
            display: inline-block;
            margin-top: 10px;
            background: #f39c12;
            color: #fff;
            padding: 6px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
        }

        .btn-opinion:hover {
            background: #e67e22;
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
                        <h3>Hola, <?= htmlspecialchars($userName) ?></h3>
                        <p>Miembro desde: Abril 2023</p>
                        <a href="editarperfil.php" class="btn btn-primary">Editar Perfil</a>
                    </div>
                    <ul class="sidebar-menu">
                        <li><a href="usuario.php"><i class="fas fa-user"></i> Perfil</a></li>
                        <li><a href="MisPedidos.php"><i class="fas fa-shopping-bag"></i> Mis Pedidos</a></li>
                        <li><a href="eventosReservados.php"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
                        <li><a href="notificaciones.php" class="active"><i class="fas fa-bell"></i> Notificaciones
                            <?php if ($totalNoLeidas > 0): ?><span class="badge badge-primary"><?= $totalNoLeidas ?></span><?php endif; ?>
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Main -->
            <div class="col-lg-9 col-md-8 col-12">
                <div class="user-main-content">
                    <div class="notifications-header">
                        <h2>Centro de Notificaciones</h2>
                        <div class="notification-actions">
                            <button id="markAllRead"><i class="fas fa-check-double"></i> Marcar todas</button>
                            <select id="filterSelect">
                                <option value="all">Todas</option>
                                <option value="unread">No leídas</option>
                                <option value="read">Leídas</option>
                            </select>
                        </div>
                    </div>

                    <div class="notifications-container" id="notificationsContainer">
                        <?php if (!empty($notificaciones)): ?>
                            <?php foreach ($notificaciones as $n): ?>
                                <div class="notification-item <?= !$n['Leida'] ? 'unread' : '' ?>" data-id="<?= $n['ID_Notificacion'] ?>">
                                    <div class="notification-icon"><i class="fas fa-bell"></i></div>
                                    <div class="notification-content">
                                        <h4><?= htmlspecialchars($n['Tipo_Notificacion']) ?></h4>
                                        <p><?= htmlspecialchars($n['Mensaje']) ?></p>
                                        <div class="notification-meta"><i class="far fa-clock"></i> <?= date('d/m/Y H:i', strtotime($n['Fecha_Notificacion'])) ?></div>
                                        <?php if (stripos($n['Mensaje'], '¿Qué te pareció tu compra?') !== false): ?>
                                            <a href="https://tu-encuesta.com" class="btn-opinion" target="_blank">Dar mi Opinión</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-actions-buttons">
                                        <?php if (!$n['Leida']): ?>
                                            <button class="mark-read" data-id="<?= $n['ID_Notificacion'] ?>"><i class="fas fa-check"></i></button>
                                        <?php endif; ?>
                                        <button class="delete-notification" data-id="<?= $n['ID_Notificacion'] ?>"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No tienes notificaciones.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="js/jquery.min.js"></script>
<script>
$(document).ready(function(){
    $('.mark-read').click(function(){
        let id = $(this).data('id');
        $.post('update_notification.php', {action: 'read', id: id}, function(){location.reload();});
    });

    $('.delete-notification').click(function(){
        let id = $(this).data('id');
        $.post('update_notification.php', {action: 'delete', id: id}, function(){location.reload();});
    });

    $('#markAllRead').click(function(){
        $.post('update_notification.php', {action: 'read_all'}, function(){location.reload();});
    });

    $('#filterSelect').change(function(){
        let filter = $(this).val();
        $('.notification-item').show();
        if(filter === 'unread') {$('.notification-item:not(.unread)').hide();}
        else if(filter === 'read') {$('.notification-item.unread').hide();}
    });
});
</script>
</body>
</html>
