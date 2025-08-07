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
// 2) Obtén el ID y el nombre del usuario de la sesión
$userId   = $_SESSION['user_id']   ?? null;
$userName = $_SESSION['user_name'] ?? 'Usuario';

// 3) Prepara y ejecuta la consulta
if ($userId) {
    $stmt = $conn->prepare("
        SELECT 
            p.ID_Pedido, 
            p.Numero_Proforma, 
            p.Fecha_Pedido, 
            p.Total_Pedido,
            p.Metodo_Pago, 
            p.Estado_Envio,
            (p.Total_Pedido + p.Monto_Canjeado) AS Total_Original,
            p.Monto_Canjeado,
            p.Puntos_Canjeados
        FROM Pedido p
        WHERE p.ID_Usuario = ?
        ORDER BY p.Fecha_Pedido DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $pedidos = $stmt->get_result();
} else {
    $pedidos = false;
}
?>

<!doctype html>
<html class="no-js" lang="es">


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
            color: #8BC34A;
        }

        /* Estilos para la lista de pedidos */
        .order-list {
            list-style: none;
            padding: 0;
        }

        /* Estilo de los items de pedidos */
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

        /* Título de cada pedido */
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

        /* Detalles del pedido */
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
            margin-right: 10px;
            font-weight: bold;
            text-align: center;
            transition: background-color 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #3e8e41;
            color: white;
            text-decoration: none;
        }

        .btn-review {
            background-color: #f39c12;
        }

        .btn-review:hover {
            background-color: #e67e22;
        }

        /* Estilo para el modal de reseñas */
        .review-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .review-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border: none;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-in-out;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover,
        .close:focus {
            color: #80b78d;
        }

        .review-form h3 {
            color: #8BC34A;
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .star-rating {
            text-align: center;
            margin: 20px 0;
        }

        .star {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
            margin: 0 2px;
        }

        .star:hover,
        .star.active {
            color: #ffc107;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9e9e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            resize: vertical;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #80b78d;
            box-shadow: 0 0 0 3px rgba(128, 183, 141, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #8BC34A;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #8BC34A;
        }

        .rating-text {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            color: #666;
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
                                <h3>Hola, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></h3>
                                <p>Miembro desde: Abril 2023</p>
                                <a href="user-settings.php" class="btn btn-sm btn-primary">Editar Perfil</a>
                            </div>
                            <ul class="sidebar-menu">
                                <li><a href="usuario.php" ><i class="fas fa-user"></i> Perfil</a></li>
                                 <li><a href="MisPedidos.php" class="active"><i class="fas fa-shopping-bag"></i> Mis Pedidos</a></li>
                                <li><a href="eventosReservados.php"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
                                <li><a href="notificaciones.php"><i class="fas fa-bell"></i> Notificaciones <span class="badge badge-primary">3</span></a></li>
                                <li><a href="cliente-chat.php"><i class="fas fa-cog"></i> Soporte</a></li>
                                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                            </ul>
                        </div>
                        </div>
                <div class="col-lg-9 col-md-8 col-12">
                    <!-- Main Content -->
                    <div class="user-main-content">
                        <!-- Contenido Principal -->
                        <div class="container">
                            <h1>Mis Pedidos</h1>
                                <?php if ($pedidos && $pedidos->num_rows > 0): ?>
                                    <ul class="order-list">
                                        <?php
                                        $steps = ['Pedido Recibido', 'En Preparación', 'En Tránsito', 'Entregado'];
                                        ?>

                                        <?php while($row = $pedidos->fetch_assoc()): ?>
                                            <li class="order-item">
                                                <h2>Pedido #<?php echo htmlspecialchars($row['Numero_Proforma']); ?></h2>

                                                <div class="progress-bar">
                                                    <?php
                                                    foreach ($steps as $idx => $label) {
                                                        $class = '';
                                                        if (array_search($row['Estado_Envio'], $steps) > $idx) {
                                                            $class = 'completed';
                                                        }
                                                        if ($row['Estado_Envio'] === $label) {
                                                            $class = 'active';
                                                        }
                                                        echo "<div class=\"progress-step {$class}\">{$label}</div>";
                                                    }
                                                    ?>
                                                </div>

                                                <div class="order-details">

                                            <?php
                                            $totalOriginal = $row['Total_Pedido'] ;
                                            ?>

                                            <p><span>Fecha Pedido:</span>
                                                <?php echo date("j \\d\\e F, Y", strtotime($row['Fecha_Pedido'])); ?>
                                            </p>

                                            <?php if ($row['Monto_Canjeado'] > 0): ?>
                                                <p><span>Total Original:</span>
                                                    <s>₡<?php echo number_format($totalOriginal, 2, ',', '.'); ?></s>
                                                </p>
                                                <p><span>Descuento por puntos:</span>
                                                    -₡<?php echo number_format($row['Monto_Canjeado'], 2, ',', '.'); ?>
                                                    <small>(<?php echo $row['Puntos_Canjeados']; ?> pts)</small>
                                                </p>
                                            <?php endif; ?>

                                            <p><span>Total Pagado:</span>
                                                <strong style="color:#4CAF50;">
                                                    ₡<?php echo number_format($row['Total_Pedido'] - $row['Monto_Canjeado'], 2, ',', '.'); ?>
                                                </strong>
                                            </p>

                                            </div>

                                                <a href="detallePedido.php?id=<?php echo $row['ID_Pedido']; ?>" class="btn">Ver Detalles</a>

                                                <button class="btn btn-review" onclick="openReviewModal('<?php echo $row['ID_Pedido']; ?>')">
                                                    <i class="fas fa-star"></i> Dar mi Opinión
                                                </button>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="order-item no-orders" style="text-align:center; padding:40px;">
                                        <h2>Hola, <?php echo htmlspecialchars($userName); ?></h2>
                                        <p>Usted aún no tiene pedidos con nosotros.</p>
                                    </div>
                                <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal de Reseña -->
    <div id="reviewModal" class="review-modal">
        <div class="review-modal-content">
            <span class="close" onclick="closeReviewModal()">&times;</span>
            <form class="review-form" onsubmit="submitReview(event)">
                <h3>¿Cómo estuvo tu pedido?</h3>
                
                <div class="star-rating">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <div class="rating-text">Haz clic en las estrellas para calificar</div>
                
                <div class="form-group">
                    <label for="reviewText">Cuéntanos tu experiencia:</label>
                    <textarea 
                        id="reviewText" 
                        class="form-control" 
                        rows="4" 
                        placeholder="Escribe aquí tu opinión sobre el pedido..."
                        required>
                    </textarea>
                </div>
                
                <input type="hidden" id="selectedRating" value="0">
                <input type="hidden" id="currentOrderId" value="">
                
                <button type="submit" class="btn-submit">Enviar Reseña</button>
            </form>
        </div>
    </div>

     <?php include 'layout/Footer.php'; ?>

		<!-- Scroll Up -->
		<a href="#" class="scroll-up"><i class="fa fa-chevron-up"></i></a>
		<!-- End Scroll Up -->

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

		<!-- Script para el modal de reseñas -->
		<script>
			let selectedRating = 0;

			// Función para abrir el modal de reseña
			function openReviewModal(orderId) {
				document.getElementById('reviewModal').style.display = 'block';
				document.getElementById('currentOrderId').value = orderId;
				resetForm();
			}

			// Función para cerrar el modal de reseña
			function closeReviewModal() {
				document.getElementById('reviewModal').style.display = 'none';
			}

			// Función para resetear el formulario
			function resetForm() {
				selectedRating = 0;
				document.getElementById('selectedRating').value = 0;
				document.getElementById('reviewText').value = '';
				updateStarDisplay();
				document.querySelector('.rating-text').textContent = 'Haz clic en las estrellas para calificar';
			}

			// Configurar eventos para las estrellas
			document.addEventListener('DOMContentLoaded', function() {
				const stars = document.querySelectorAll('.star');
				
				stars.forEach(star => {
					star.addEventListener('click', function() {
						selectedRating = parseInt(this.getAttribute('data-rating'));
						document.getElementById('selectedRating').value = selectedRating;
						updateStarDisplay();
						updateRatingText();
					});

					star.addEventListener('mouseover', function() {
						const rating = parseInt(this.getAttribute('data-rating'));
						highlightStars(rating);
					});
				});

				// Restaurar estrellas al salir del hover
				document.querySelector('.star-rating').addEventListener('mouseleave', function() {
					updateStarDisplay();
				});
			});

			// Función para actualizar la visualización de las estrellas
			function updateStarDisplay() {
				const stars = document.querySelectorAll('.star');
				stars.forEach((star, index) => {
					if (index < selectedRating) {
						star.classList.add('active');
					} else {
						star.classList.remove('active');
					}
				});
			}

			// Función para resaltar estrellas en hover
			function highlightStars(rating) {
				const stars = document.querySelectorAll('.star');
				stars.forEach((star, index) => {
					if (index < rating) {
						star.classList.add('active');
					} else {
						star.classList.remove('active');
					}
				});
			}

			// Función para actualizar el texto de calificación
			function updateRatingText() {
				const ratingTexts = [
					'',
					'Muy malo',
					'Malo',
					'Regular',
					'Bueno',
					'Excelente'
				];
				document.querySelector('.rating-text').textContent = ratingTexts[selectedRating];
			}

			// Función para enviar la reseña
			function submitReview(event) {
				event.preventDefault();
				
				const orderId = document.getElementById('currentOrderId').value;
				const rating = document.getElementById('selectedRating').value;
				const reviewText = document.getElementById('reviewText').value;
				
				if (rating == 0) {
					alert('Por favor selecciona una calificación.');
					return;
				}
				
				if (reviewText.trim() === '') {
					alert('Por favor escribe tu opinión.');
					return;
				}
				
				// Aquí puedes agregar la lógica para enviar la reseña al servidor
				// Por ejemplo, usar AJAX para enviar los datos
				
				// Simulamos el envío exitoso
				alert(`¡Gracias por tu reseña!\n\nPedido: ${orderId}\nCalificación: ${rating} estrellas\nOpinión: ${reviewText}`);
				
				// Cerrar el modal
				closeReviewModal();
				
				// Aquí podrías actualizar la interfaz para mostrar que ya se envió la reseña
				// Por ejemplo, cambiar el botón "Dar mi Opinión" por "Reseña enviada"
			}

			// Cerrar modal al hacer clic fuera de él
			window.addEventListener('click', function(event) {
				const modal = document.getElementById('reviewModal');
				if (event.target === modal) {
					closeReviewModal();
				}
			});
		</script>
  
</body>

</html>