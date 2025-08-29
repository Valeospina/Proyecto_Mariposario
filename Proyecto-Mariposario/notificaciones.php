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
    /* Estilo general de la página */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
        color: #333;
        margin: 0;
        padding: 0;
    }

    /* Estilos del contenedor */
    .containerNotificaciones {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Título de la página */
    h1 {
        text-align: center;
        margin-bottom: 40px;
        font-size: 2.5em;
        color: #42764D;
    }

    /* Header de notificaciones */
    .notifications-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e9ecef;
    }

    .notifications-stats {
        display: flex;
        gap: 20px;
    }

    .stat-item {
        text-align: center;
        padding: 10px 20px;
        background: linear-gradient(135deg, #8BC34A, #66bb6a);
        border-radius: 10px;
        color: white;
        min-width: 100px;
    }

    .stat-number {
        font-size: 24px;
        font-weight: 600;
        display: block;
    }

    .stat-label {
        font-size: 12px;
        opacity: 0.9;
    }

    /* Filtros */
    .notification-filters {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .filter-btn {
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: #fff;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .filter-btn:hover, .filter-btn.active {
        background: #8BC34A;
        color: white;
        border-color: #8BC34A;
    }

    .notification-list {
        list-style: none;
        padding: 0;
    }

    .notification-item {
        background-color: #fff;
        border-radius: 12px;
        margin-bottom: 15px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease-in-out;
        border-left: 4px solid transparent;
        display: flex;
        align-items: flex-start;
        gap: 15px;
        position: relative;
    }

    .notification-item:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }

    .notification-item.unread {
        background: linear-gradient(135deg, #f8fff4, #ffffff);
        border-left-color: #8BC34A;
    }

    .notification-item.unread::before {
        content: '';
        position: absolute;
        top: 15px;
        right: 15px;
        width: 8px;
        height: 8px;
        background: #8BC34A;
        border-radius: 50%;
    }

    .notification-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        flex-shrink: 0;
    }

    .notification-content {
        flex: 1;
    }

    .notification-content h3 {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }

    .notification-content p {
        margin: 0 0 8px 0;
        font-size: 14px;
        color: #666;
        line-height: 1.5;
    }

    .notification-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 12px;
        color: #999;
    }

    .notification-time {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .notification-type {
        background: #e9ecef;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }

    /* Acciones de notificación */
    .notification-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: center;
    }

    .action-btn {
        background: none;
        border: none;
        padding: 8px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #999;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .action-btn:hover {
        background: #f0f0f0;
        color: #8BC34A;
    }

    .mark-read-btn:hover {
        background: #e8f5e8;
        color: #4caf50;
    }

    .delete-btn:hover {
        background: #ffebee;
        color: #f44336;
    }

    /* Botones de acción especiales */
    .special-action-btn {
        display: inline-block;
        background: linear-gradient(135deg, #ff9800, #f57c00);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 500;
        margin-top: 10px;
        transition: all 0.3s ease;
    }

    .special-action-btn:hover {
        background: linear-gradient(135deg, #f57c00, #e65100);
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
    }

    /* Estado vacío */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .empty-state i {
        font-size: 4em;
        color: #ddd;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: #666;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #999;
        margin-bottom: 30px;
    }

    /* Estilos del sidebar (copiados del primer script) */
    :root {
        --primary-color: #8BC34A;
        --secondary-color: #8BC34A;
        --text-color: #333;
        --light-color: #f9f9f9;
        --dark-color: #222;
        --grey-color: #f4f4f4;
        --border-color: #e9e9e9;
    }
            
    .user-sidebar {
        background-color: white;
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
        border: 4px solid var(--primary-color);
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
        background-color: var(--primary-color);
        color: white;
    }
    
    .sidebar-menu li a i {
        margin-right: 10px;
    }
    
    .user-main-content {
        background-color: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        padding: 10px 25px;
        font-weight: 500;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
    }

    /* Badge para notificaciones no leídas */
    .badge {
        background-color: #dc3545;
        color: white;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .notifications-header {
            flex-direction: column;
            gap: 15px;
            align-items: stretch;
        }
        
        .notifications-stats {
            justify-content: center;
        }
        
        .notification-filters {
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .notification-item {
            flex-direction: column;
            text-align: center;
        }
        
        .notification-actions {
            flex-direction: row;
            justify-content: center;
            margin-top: 15px;
        }
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
                            <a href="user-settings.php" class="btn btn-sm btn-primary">Editar Perfil</a>
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

    <!-- Footer -->
    <footer id="footer" class="footer">
        <!-- Tu footer aquí -->
    </footer>

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