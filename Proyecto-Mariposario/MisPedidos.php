<?php
/* ============================================================
   MisPedidos.php — Vista de pedidos del usuario
   Sincronizada con estados del admin:
   - Pendiente     -> Pedido Recibido
   - Procesado     -> En Preparación
   - Enviado       -> En Tránsito
   - Entregado     -> Entregado
   - Cancelado     -> (muestra badge y no avanza la barra)
   Incluye auto-notificaciones idempotentes.
   ============================================================ */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/* Página actual (útil para marcar activo en el menú/nav) */
$currentPage = basename($_SERVER['PHP_SELF']);

/* Conexión a la base de datos */
include 'DB.php';

/* Usuario en sesión */
$userId   = $_SESSION['user_id']   ?? null;
$userName = $_SESSION['user_name'] ?? 'Usuario';

/* ============================================================
   Helper: detectar la columna para el tipo en Notificacion
   (algunos esquemas usan 'Categoria', otros 'Tipo_Notificacion')
   ============================================================ */
function _notifTypeColumn(mysqli $conn): string {
    $res = $conn->query("SELECT DATABASE() AS db");
    $db  = $res ? ($res->fetch_assoc()['db'] ?? null) : null;
    if (!$db) return 'Tipo_Notificacion'; // fallback seguro

    $sql  = "SELECT COUNT(*) AS c
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA=? AND TABLE_NAME='Notificacion' AND COLUMN_NAME='Categoria'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $db);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (!empty($row['c']) && (int)$row['c'] > 0) ? 'Categoria' : 'Tipo_Notificacion';
}

/* ============================================================
   Helper: índice de paso para la barra de progreso a partir
   del estado "administrativo" más reciente.
   0: Pedido Recibido (Pendiente)
   1: En Preparación   (Procesado)
   2: En Tránsito      (Enviado)
   3: Entregado        (Entregado)
  -1: Cancelado u otro no mapeado
   ============================================================ */
function pedidoStatusIndex(string $estado): int {
    $e = mb_strtolower(trim($estado));
    $map = [
        // Estados del admin
        'pendiente'       => 0,
        'procesado'       => 1,
        'enviado'         => 2,
        'entregado'       => 3,
        'cancelado'       => -1,
        // Compatibilidad con valores antiguos en Pedido.Estado_Envio
        'pedido recibido' => 0,
        'en preparacion'  => 1,
        'en preparación'  => 1,
        'en transito'     => 2,
        'en tránsito'     => 2,
    ];
    return $map[$e] ?? -1;
}

/* ============================================================
   Auto-notificaciones idempotentes por estado real (último)
   Sin duplicar mensajes (NOT EXISTS) y soportando ambos
   vocabularios (admin y usuario).
   ============================================================ */
if (!empty($userId)) {
    $col = _notifTypeColumn($conn);

    // 1) Pendiente / Pedido Recibido
    $sql = "
        INSERT INTO Notificacion (ID_Usuario, $col, Mensaje)
        SELECT p.ID_Usuario, 'Pedido',
               CONCAT('Tu pedido #', p.Numero_Proforma, ' ha sido recibido y está en proceso')
        FROM Pedido p
        LEFT JOIN (
            SELECT ep1.ID_Pedido, ep1.Estado
            FROM Estado_Pedido ep1
            JOIN (
                SELECT ID_Pedido, MAX(Fecha) AS max_fecha
                FROM Estado_Pedido
                GROUP BY ID_Pedido
            ) ult ON ult.ID_Pedido = ep1.ID_Pedido AND ult.max_fecha = ep1.Fecha
        ) ep ON ep.ID_Pedido = p.ID_Pedido
        WHERE p.ID_Usuario = ?
          AND LOWER(COALESCE(ep.Estado, p.Estado_Envio)) IN ('pendiente','pedido recibido')
          AND NOT EXISTS (
            SELECT 1 FROM Notificacion n
            WHERE n.ID_Usuario = p.ID_Usuario
              AND n.$col = 'Pedido'
              AND n.Mensaje = CONCAT('Tu pedido #', p.Numero_Proforma, ' ha sido recibido y está en proceso')
        )
    ";
    if ($st = $conn->prepare($sql)) { $st->bind_param('i', $userId); $st->execute(); $st->close(); }

    // 2) Procesado / En Preparación
    $sql = "
        INSERT INTO Notificacion (ID_Usuario, $col, Mensaje)
        SELECT p.ID_Usuario, 'Pedido',
               CONCAT('Tu pedido #', p.Numero_Proforma, ' está siendo preparado con mucho cariño 🌱')
        FROM Pedido p
        LEFT JOIN (
            SELECT ep1.ID_Pedido, ep1.Estado
            FROM Estado_Pedido ep1
            JOIN (
                SELECT ID_Pedido, MAX(Fecha) AS max_fecha
                FROM Estado_Pedido
                GROUP BY ID_Pedido
            ) ult ON ult.ID_Pedido = ep1.ID_Pedido AND ult.max_fecha = ep1.Fecha
        ) ep ON ep.ID_Pedido = p.ID_Pedido
        WHERE p.ID_Usuario = ?
          AND LOWER(COALESCE(ep.Estado, p.Estado_Envio)) IN ('procesado','en preparacion','en preparación')
          AND NOT EXISTS (
            SELECT 1 FROM Notificacion n
            WHERE n.ID_Usuario = p.ID_Usuario
              AND n.$col = 'Pedido'
              AND n.Mensaje = CONCAT('Tu pedido #', p.Numero_Proforma, ' está siendo preparado con mucho cariño 🌱')
        )
    ";
    if ($st = $conn->prepare($sql)) { $st->bind_param('i', $userId); $st->execute(); $st->close(); }

    // 3) Enviado / En Tránsito
    $sql = "
        INSERT INTO Notificacion (ID_Usuario, $col, Mensaje)
        SELECT p.ID_Usuario, 'Pedido',
               CONCAT('¡Tu pedido #', p.Numero_Proforma, ' ya está en camino!')
        FROM Pedido p
        LEFT JOIN (
            SELECT ep1.ID_Pedido, ep1.Estado
            FROM Estado_Pedido ep1
            JOIN (
                SELECT ID_Pedido, MAX(Fecha) AS max_fecha
                FROM Estado_Pedido
                GROUP BY ID_Pedido
            ) ult ON ult.ID_Pedido = ep1.ID_Pedido AND ult.max_fecha = ep1.Fecha
        ) ep ON ep.ID_Pedido = p.ID_Pedido
        WHERE p.ID_Usuario = ?
          AND LOWER(COALESCE(ep.Estado, p.Estado_Envio)) IN ('enviado','en transito','en tránsito')
          AND NOT EXISTS (
            SELECT 1 FROM Notificacion n
            WHERE n.ID_Usuario = p.ID_Usuario
              AND n.$col = 'Pedido'
              AND n.Mensaje = CONCAT('¡Tu pedido #', p.Numero_Proforma, ' ya está en camino!')
        )
    ";
    if ($st = $conn->prepare($sql)) { $st->bind_param('i', $userId); $st->execute(); $st->close(); }

    // 4) Entregado
    $sql = "
        INSERT INTO Notificacion (ID_Usuario, $col, Mensaje)
        SELECT p.ID_Usuario, 'Pedido',
               CONCAT('Tu pedido #', p.Numero_Proforma, ' ha sido entregado. ¿Qué te pareció tu compra?')
        FROM Pedido p
        LEFT JOIN (
            SELECT ep1.ID_Pedido, ep1.Estado
            FROM Estado_Pedido ep1
            JOIN (
                SELECT ID_Pedido, MAX(Fecha) AS max_fecha
                FROM Estado_Pedido
                GROUP BY ID_Pedido
            ) ult ON ult.ID_Pedido = ep1.ID_Pedido AND ult.max_fecha = ep1.Fecha
        ) ep ON ep.ID_Pedido = p.ID_Pedido
        WHERE p.ID_Usuario = ?
          AND LOWER(COALESCE(ep.Estado, p.Estado_Envio)) = 'entregado'
          AND NOT EXISTS (
            SELECT 1 FROM Notificacion n
            WHERE n.ID_Usuario = p.ID_Usuario
              AND n.$col = 'Pedido'
              AND n.Mensaje = CONCAT('Tu pedido #', p.Numero_Proforma, ' ha sido entregado. ¿Qué te pareció tu compra?')
        )
    ";
    if ($st = $conn->prepare($sql)) { $st->bind_param('i', $userId); $st->execute(); $st->close(); }

    // 5) Cancelado
    $sql = "
        INSERT INTO Notificacion (ID_Usuario, $col, Mensaje)
        SELECT p.ID_Usuario, 'Pedido',
               CONCAT('Tu pedido #', p.Numero_Proforma, ' fue cancelado. Si no lo solicitaste, contáctanos por soporte.')
        FROM Pedido p
        LEFT JOIN (
            SELECT ep1.ID_Pedido, ep1.Estado
            FROM Estado_Pedido ep1
            JOIN (
                SELECT ID_Pedido, MAX(Fecha) AS max_fecha
                FROM Estado_Pedido
                GROUP BY ID_Pedido
            ) ult ON ult.ID_Pedido = ep1.ID_Pedido AND ult.max_fecha = ep1.Fecha
        ) ep ON ep.ID_Pedido = p.ID_Pedido
        WHERE p.ID_Usuario = ?
          AND LOWER(COALESCE(ep.Estado, p.Estado_Envio)) = 'cancelado'
          AND NOT EXISTS (
            SELECT 1 FROM Notificacion n
            WHERE n.ID_Usuario = p.ID_Usuario
              AND n.$col = 'Pedido'
              AND n.Mensaje = CONCAT('Tu pedido #', p.Numero_Proforma, ' fue cancelado. Si no lo solicitaste, contáctanos por soporte.')
        )
    ";
    if ($st = $conn->prepare($sql)) { $st->bind_param('i', $userId); $st->execute(); $st->close(); }
}

/* ============================================================
   Foto de perfil (fallback a default si no tiene)
   ============================================================ */
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

/* ============================================================
   Consulta de pedidos con el ÚLTIMO estado real (si hay en
   Estado_Pedido) y fallback a Pedido.Estado_Envio.
   ============================================================ */
if ($userId) {
    $stmt = $conn->prepare("
        SELECT 
            p.ID_Pedido, 
            p.Numero_Proforma, 
            p.Fecha_Pedido, 
            p.Total_Pedido,
            p.Metodo_Pago, 
            COALESCE(ep.Estado, p.Estado_Envio) AS Estado_Actual,
            (p.Total_Pedido + p.Monto_Canjeado) AS Total_Original,
            p.Monto_Canjeado,
            p.Puntos_Canjeados
        FROM Pedido p
        LEFT JOIN (
            SELECT ep1.ID_Pedido, ep1.Estado
            FROM Estado_Pedido ep1
            JOIN (
                SELECT ID_Pedido, MAX(Fecha) AS max_fecha
                FROM Estado_Pedido
                GROUP BY ID_Pedido
            ) ult ON ult.ID_Pedido = ep1.ID_Pedido AND ult.max_fecha = ep1.Fecha
        ) ep ON ep.ID_Pedido = p.ID_Pedido
        WHERE p.ID_Usuario = ?
        ORDER BY p.Fecha_Pedido DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $pedidos = $stmt->get_result();
} else {
    $pedidos = false;
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
<link rel="stylesheet" href="./css/Mispedidos.css">
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

                <!-- Main -->
                <div class="col-lg-9 col-md-8 col-12">
                    <div class="user-main-content">
                        <div class="containerPedidos">
                            <h1>Mis Pedidos</h1>

                            <?php if ($pedidos && $pedidos->num_rows > 0): ?>
                                <ul class="order-list">
                                    <?php
                                    // Etiquetas mostradas al usuario
                                    $labelsUI = ['Pedido Recibido', 'En Preparación', 'En Tránsito', 'Entregado'];
                                    while ($row = $pedidos->fetch_assoc()):
                                        $estadoActual = $row['Estado_Actual'] ?? '';
                                        $idx          = pedidoStatusIndex($estadoActual);
                                        $esCancelado  = (mb_strtolower(trim($estadoActual)) === 'cancelado');
                                    ?>
                                    <li class="order-item">
                                        <h2>Pedido #<?= htmlspecialchars($row['Numero_Proforma']) ?></h2>

                                        <?php if ($esCancelado): ?>
                                            <span class="badge-cancelado">Cancelado</span>
                                        <?php endif; ?>

                                        <div class="progress-bar">
                                            <?php for ($i = 0; $i < count($labelsUI); $i++):
                                                $class = '';
                                                if (!$esCancelado) {
                                                    if ($idx > $i)   $class = 'completed';
                                                    if ($idx === $i) $class = 'active';
                                                }
                                            ?>
                                                <div class="progress-step <?= $class ?>"><?= $labelsUI[$i] ?></div>
                                            <?php endfor; ?>
                                        </div>

<div class="order-details">
    <?php
    // Asegura valores numéricos
    $descuento    = isset($row['Monto_Canjeado']) ? (float)$row['Monto_Canjeado'] : 0.0;
    $totalPagado  = isset($row['Total_Pedido'])   ? (float)$row['Total_Pedido']   : 0.0;

    // Si Total_Pedido es NETO (lo que realmente pagó), el original se infiere sumando el descuento
    $totalOriginal = $totalPagado + $descuento;
    ?>

    <p class="mb-0"><span>Estado actual:</span> <?= htmlspecialchars($estadoActual ?: 'Pendiente') ?></p>
    <p><span>Fecha Pedido:</span> <?= date("j \\d\\e F, Y", strtotime($row['Fecha_Pedido'])) ?></p>

    <?php if ($descuento > 0): ?>
        <p><span>Total Original:</span>
            <s>₡<?= number_format($totalOriginal, 2, ',', '.') ?></s>
        </p>
        <p><span>Descuento por puntos:</span>
            -₡<?= number_format($descuento, 2, ',', '.') ?>
            <small>(<?= (int)$row['Puntos_Canjeados'] ?> pts)</small>
        </p>
    <?php endif; ?>

    <p><span>Total Pagado:</span>
        <strong style="color:#4CAF50;">
            ₡<?= number_format($totalPagado, 2, ',', '.') ?>
        </strong>
    </p>
</div>


                                        <button class="btn btn-review" onclick="openReviewModal('<?= (int)$row['ID_Pedido'] ?>')">
                                            <i class="fas fa-star"></i> Dar mi Opinión
                                        </button>
                                    </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="order-item no-orders text-center" style="padding:40px;">
                                    <h2 class="mt-0">Hola, <?= htmlspecialchars($userName) ?></h2>
                                    <p class="mb-0">Usted aún no tiene pedidos con nosotros.</p>
                                </div>
                            <?php endif; ?>
                        </div> <!-- /.containerPedidos -->
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
                    <textarea id="reviewText" class="form-control" rows="4" placeholder="Escribe aquí tu opinión sobre el pedido..." required></textarea>
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

    <!-- JS base -->
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

    <!-- Script específico: modal reseñas + estrellas -->
    <script>
        let selectedRating = 0;

        function openReviewModal(orderId) {
            document.getElementById('reviewModal').style.display = 'block';
            document.getElementById('currentOrderId').value = orderId;
            resetForm();
        }
        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }
        function resetForm() {
            selectedRating = 0;
            document.getElementById('selectedRating').value = 0;
            document.getElementById('reviewText').value = '';
            updateStarDisplay();
            document.querySelector('.rating-text').textContent = 'Haz clic en las estrellas para calificar';
        }

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
            const starWrap = document.querySelector('.star-rating');
            if (starWrap) {
                starWrap.addEventListener('mouseleave', function() {
                    updateStarDisplay();
                });
            }
        });

        function updateStarDisplay() {
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                if (index < selectedRating) star.classList.add('active');
                else star.classList.remove('active');
            });
        }
        function highlightStars(rating) {
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                if (index < rating) star.classList.add('active');
                else star.classList.remove('active');
            });
        }
        function updateRatingText() {
            const ratingTexts = ['', 'Muy malo', 'Malo', 'Regular', 'Bueno', 'Excelente'];
            const el = document.querySelector('.rating-text');
            if (el) el.textContent = ratingTexts[selectedRating];
        }
        function submitReview(event) {
            event.preventDefault();
            const orderId   = document.getElementById('currentOrderId').value;
            const rating    = document.getElementById('selectedRating').value;
            const reviewTxt = document.getElementById('reviewText').value;
            if (rating == 0) { alert('Por favor selecciona una calificación.'); return; }
            if (reviewTxt.trim() === '') { alert('Por favor escribe tu opinión.'); return; }
            // Aquí harías el POST real a tu endpoint de reseñas
            alert(`¡Gracias por tu reseña!\n\nPedido: ${orderId}\nCalificación: ${rating} estrellas\nOpinión: ${reviewTxt}`);
            closeReviewModal();
        }
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('reviewModal');
            if (event.target === modal) closeReviewModal();
        });
    </script>
</body>
</html>
