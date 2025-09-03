<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Eco Mariposas, notificaciones, perfil de usuario, jardín, naturaleza, mariposas">
    <meta name="description" content="Centro de Notificaciones de Eco Mariposas, mantente al día con todas tus actividades.">
    <meta name='copyright' content='Eco Mariposas'>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <title>Notificaciones | Eco Mariposas</title>
    
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
<link rel="stylesheet" href="./css/notificaciones.css">
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

                <!-- Contenido principal -->
                <div class="col-lg-9 col-md-8 col-12">
                    <div class="user-main-content">
                        <h1>Centro de Notificaciones</h1>
                        
                        <!-- Header con estadísticas y filtros -->
                        <div class="notifications-header">
                            <div class="notifications-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?= count($notificaciones) ?></span>
                                    <span class="stat-label">Total</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= $totalNoLeidas ?></span>
                                    <span class="stat-label">No leídas</span>
                                </div>
                            </div>
                            
                            <div class="notification-filters">
                                <button class="filter-btn active" data-filter="all">Todas</button>
                                <button class="filter-btn" data-filter="unread">No leídas</button>
                                <button class="filter-btn" data-filter="read">Leídas</button>
                                <button class="action-btn" id="markAllRead" title="Marcar todas como leídas">
                                    <i class="fas fa-check-double"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Lista de notificaciones -->
                        <div class="notifications-container">
                            <?php if (!empty($notificaciones)): ?>
                                <ul class="notification-list">
                                    <?php foreach ($notificaciones as $notif): ?>
                                        <li class="notification-item <?= !$notif['Leida'] ? 'unread' : 'read' ?>" 
                                            data-id="<?= $notif['ID_Notificacion'] ?>" 
                                            data-status="<?= !$notif['Leida'] ? 'unread' : 'read' ?>">
                                            
                                            <div class="notification-icon" style="background-color: <?= getNotificationColor($notif['Tipo_Notificacion']) ?>">
                                                <i class="fas <?= getNotificationIcon($notif['Tipo_Notificacion']) ?>"></i>
                                            </div>
                                            
                                            <div class="notification-content">
                                                <h3><?= htmlspecialchars($notif['Tipo_Notificacion']) ?></h3>
                                                <p><?= htmlspecialchars($notif['Mensaje']) ?></p>
                                                
                                                <div class="notification-meta">
                                                    <div class="notification-time">
                                                        <i class="far fa-clock"></i>
                                                        <?= date('d/m/Y - H:i', strtotime($notif['Fecha_Notificacion'])) ?>
                                                    </div>
                                                    <span class="notification-type"><?= htmlspecialchars($notif['Tipo_Notificacion']) ?></span>
                                                </div>

                                                <!-- Acciones especiales según el tipo de notificación -->
                                                <?php if ($notif['Tipo_Notificacion'] == 'Pedido' && stripos($notif['Mensaje'], 'completado') !== false): ?>
                                                    <a href="opinion.php?pedido=<?= $notif['ID_Referencia'] ?>" class="special-action-btn">
                                                        <i class="fas fa-star"></i> Calificar Compra
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($notif['Tipo_Notificacion'] == 'Evento' && stripos($notif['Mensaje'], 'próximo') !== false): ?>
                                                    <a href="eventos.php" class="special-action-btn">
                                                        <i class="fas fa-calendar-check"></i> Ver Detalles
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($notif['Tipo_Notificacion'] == 'Promoción'): ?>
                                                    <a href="productos.php" class="special-action-btn">
                                                        <i class="fas fa-shopping-cart"></i> Ver Ofertas
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="notification-actions">
                                                <?php if (!$notif['Leida']): ?>
                                                    <button class="action-btn mark-read-btn" data-id="<?= $notif['ID_Notificacion'] ?>" title="Marcar como leída">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="action-btn delete-btn" data-id="<?= $notif['ID_Notificacion'] ?>" title="Eliminar notificación">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-bell-slash"></i>
                                    <h3>¡No tienes notificaciones!</h3>
                                    <p>Cuando tengas nuevas notificaciones, aparecerán aquí.<br>¡Explora nuestra tienda y eventos para mantenerte al día!</p>
                                    <a href="productos.php" class="btn-primary">Explorar Productos</a>
                                </div>
                            <?php endif; ?>
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
    $(document).ready(function() {
        // Filtros de notificaciones
        $('.filter-btn').click(function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            const notifications = $('.notification-item');
            
            notifications.show();
            
            if (filter === 'unread') {
                notifications.filter('.read').hide();
            } else if (filter === 'read') {
                notifications.filter('.unread').hide();
            }
        });
        
        // Marcar notificación individual como leída
        $('.mark-read-btn').click(function() {
            const id = $(this).data('id');
            const item = $(this).closest('.notification-item');
            
            $.post('update_notification.php', {
                action: 'read',
                id: id
            }, function(response) {
                if (response.success) {
                    item.removeClass('unread').addClass('read');
                    $(this).remove();
                    updateStats();
                }
            }.bind(this), 'json');
        });
        
        // Eliminar notificación
        $('.delete-btn').click(function() {
            if (confirm('¿Estás seguro de que quieres eliminar esta notificación?')) {
                const id = $(this).data('id');
                const item = $(this).closest('.notification-item');
                
                $.post('update_notification.php', {
                    action: 'delete',
                    id: id
                }, function(response) {
                    if (response.success) {
                        item.fadeOut(300, function() {
                            $(this).remove();
                            updateStats();
                            checkEmptyState();
                        });
                    }
                }, 'json');
            }
        });
        
        // Marcar todas como leídas
        $('#markAllRead').click(function() {
            if (confirm('¿Marcar todas las notificaciones como leídas?')) {
                $.post('update_notification.php', {
                    action: 'read_all'
                }, function(response) {
                    if (response.success) {
                        $('.notification-item').removeClass('unread').addClass('read');
                        $('.mark-read-btn').remove();
                        updateStats();
                    }
                }, 'json');
            }
        });
        
        // Actualizar estadísticas
        function updateStats() {
            const total = $('.notification-item').length;
            const unread = $('.notification-item.unread').length;
            
            $('.stat-item .stat-number').eq(0).text(total);
            $('.stat-item .stat-number').eq(1).text(unread);
            
            // Actualizar badge del sidebar
            const badge = $('.sidebar-menu .badge');
            if (unread > 0) {
                badge.text(unread).show();
            } else {
                badge.hide();
            }
        }
        
        // Verificar estado vacío
        function checkEmptyState() {
            if ($('.notification-item').length === 0) {
                $('.notifications-container').html(`
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>¡No tienes notificaciones!</h3>
                        <p>Cuando tengas nuevas notificaciones, aparecerán aquí.<br>¡Explora nuestra tienda y eventos para mantenerte al día!</p>
                        <a href="productos.php" class="btn-primary">Explorar Productos</a>
                    </div>
                `);
            }
        }
    });
    </script>
</body>
</html>