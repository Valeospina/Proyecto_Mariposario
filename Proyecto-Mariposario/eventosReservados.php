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


?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Eco Mariposas, perfil de usuario, jardín, naturaleza, mariposas">
    <meta name="description" content="Panel de usuario de Eco Mariposas, un espacio donde puedes gestionar tus pedidos, eventos y notificaciones.">
    <meta name='copyright' content='Eco Mariposas'>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <title>Mis Reservas | Eco Mariposas</title>
    
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
    .containerPedidos {
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


    .order-list {
        list-style: none;
        padding: 0;
    }

    .order-item {
        background-color: #fff;
        border-radius: 10px;
        margin-bottom: 20px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease-in-out;
    }

    .order-item:hover {
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        transform: translateY(-5px);
    }
    .order-item h2 {
        margin-top: 0;
        font-size: 1.5em;
        color: #333;
    }

    /* Barra de progreso */
    .progress-bar {
        display: flex;
        justify-content: space-between;
        margin: 20px 0;
    }

    .progress-step {
        width: 24%;
        text-align: center;
        padding: 10px;
        background-color: #f1f1f1;
        border-radius: 5px;
        font-size: 0.9em;
        font-weight: bold;
        color: #666;
        transition: all 0.3s ease;
    }

    .progress-step.active {
        background-color: #8BC34A;
        color: white;
    }

    .progress-step.completed {
        background-color: #4cc1d7;
        color: white;
    }

    .order-details {
        margin-top: 10px;
        font-size: 1em;
        color: #666;
    }

    .order-details span {
        font-weight: bold;
        color: #333;
    }

    .status-date {
        font-size: 0.9em;
        color: #999;
    }

    /* Estilo del botón */
    .btn {
        display: inline-block;
        background-color: #8BC34A;
        color: white;
        padding: 12px 20px;
        border-radius: 5px;
        text-decoration: none;
        margin-top: 20px;
        font-weight: bold;
        text-align: center;
        transition: background-color 0.3s ease;
    }

    .btn:hover {
        background-color: #8BC34A;
    }

    /* Estilo para el preloader */
    .preloader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loader-outter {
        border: 3px solid #81BAE6;
        border-radius: 50%;
        border-top: 3px solid transparent;
        width: 50px;
        height: 50px;
        animation: rotate 1s linear infinite;
    }

    @keyframes rotate {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

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
        
        .user-dashboard .card {
            border: none;
            border-radius: 10px;
            padding: 25px 20px;
            margin-bottom: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .user-dashboard .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .user-dashboard .card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 22px;
        }
        
        .user-dashboard .card p {
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .user-dashboard .card .card-icon {
            font-size: 36px;
            margin-bottom: 15px;
            color: var(--primary-color);
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
        
        .stats-number {
            font-size: 32px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
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
            background-color: var(--primary-color);
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
        }
        
        .activity-content p {
            font-size: 12px;
            color: #888;
            margin: 0;
        }
        
        .footer {
            background-color: #2c3e50;
            color: #ecf0f1;
        }
        
        .footer-top {
            padding: 70px 0 50px;
        }
        
        .footer h2 {
            color: white;
            font-size: 20px;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer .social li a {
            width: 36px;
            height: 36px;
            line-height: 36px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            color: white;
            display: block;
            text-align: center;
        }
        
        .footer .social li a:hover {
            background-color: var(--primary-color);
        }
        
        .preloader .indicator svg {
            animation: spin 1.5s linear infinite;
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
                            <img src="<?= $fotoPerfil ?>" alt="Foto de perfil">

                            <h3>Hola, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario') ?></h3>
                            <p>Miembro desde: Abril 2023</p>
                            <a href="user-settings.php" class="btn btn-sm btn-primary">Editar Perfil</a>
                        </div>
                        <ul class="sidebar-menu">
                            <li><a href="usuario.php" class="<?= $currentPage=='usuario.php'?'active':'' ?>"><i class="fas fa-user"></i> Perfil</a></li>
                            <li><a href="MisPedidos.php" class="<?= $currentPage=='MisPedidos.php'?'active':'' ?>"><i class="fas fa-shopping-bag"></i> Mis Pedidos</a></li>
                            <li><a href="eventosReservados.php" class="<?= $currentPage=='eventosReservados.php'?'active':'' ?>"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
                            <li><a href="notificaciones.php" class="<?= $currentPage=='notificaciones.php'?'active':'' ?>"><i class="fas fa-bell"></i> Notificaciones <span class="badge badge-primary">3</span></a></li>
                           
                            <li><a href="cliente-chat.php" class="<?= $currentPage=='cliente-chat.php'?'active':'' ?>"><i class="fas fa-cog"></i> Soporte</a></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-9 col-md-8 col-12">
                    <div class="user-main-content">
                        <!-- Contenido de reservas -->
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
                            $stmt->bind_param('i', $userID);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                            ?>
                                <div class="reservation-item order-item">
                                    <h2><?= htmlspecialchars($row['evento']) ?></h2>
                                    <div class="reservation-details order-details">
                                        <p><span>Fecha:</span> <?= date('d/m/Y', strtotime($row['fecha_evento'])) ?></p>
                                        <p><span>Hora:</span> <?= htmlspecialchars($row['hora_evento']) ?></p>
                                        <p><span>Cantidad de personas:</span> <?= (int)$row['Cantidad_Personas'] ?></p>
                                    </div>
                                    <div class="progress-bar">
                                        <?php
                                        $steps = ['Solicitada','Confirmada','En curso','Finalizada'];
                                        $current = array_search($row['Estado'], $steps);
                                        foreach ($steps as $i => $label) {
                                            $class = $i < $current ? 'completed' : ($i === $current ? 'active' : '');
                                            echo "<div class='progress-step $class'>{$label}</div>";
                                        }
                                        ?>
                                    </div>
                                    <p class="status-date">Última actualización: <?= date('d/m/Y', strtotime($row['Fecha_Reserva'])) ?></p>
                                </div>
                            <?php
                                endwhile;
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
                            $stmt->close();
                            $conn->close();
                            ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Area (idéntico al tuyo) -->
    <footer id="footer" class="footer">
        <!-- ... tu footer completo aquí ... -->
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
