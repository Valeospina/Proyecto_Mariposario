<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'DB.php';

// Mantener el mismo ID de consulta en la sesión
if (!isset($_SESSION['consultaId'])) {
    $_SESSION['consultaId'] = rand(1000, 9999);
}
$userId = $_SESSION['user_id'] ?? null;
$consultaId = $_SESSION['consultaId'];
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
<link rel="stylesheet" href="css/tienda.css">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="./css/soporte.css">
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
            <!-- Chat -->
            <div class="col-lg-9 col-md-8 col-12">
                <div class="chat-wrapper">
                    <div class="chat-header">Soporte en Línea</div>
                    <div class="chat-body" id="chatBox">
                        <div class="message system">Cargando historial...</div>
                    </div>
                    <div class="chat-footer">
                        <div class="input-box">
                            <input type="text" id="msgInput" placeholder="Escribe tu mensaje...">
                            <button id="sendBtn"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'layout/Footer.php'; ?>

<script>
let consultaId = <?php echo $consultaId; ?>;
const user = "<?php echo htmlspecialchars($userName); ?>";
const chatBox = document.getElementById('chatBox');
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
let conn = new WebSocket('ws://localhost:8080/chat');

// Registrar chat
fetch('crear_chat.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `tema=Consulta&consultaId=${consultaId}`
});

// Cargar historial
window.onload = () => {
    fetch('./admin/get_chat.php?id=' + consultaId)
        .then(r => r.json())
        .then(data => {
            chatBox.innerHTML = '';
            if (data.length === 0) {
                chatBox.innerHTML = '<div class="message system">Escribe tu consulta para iniciar el chat.</div>';
            } else {
                data.forEach(m => {
                    const div = document.createElement('div');
                    div.classList.add('message', m.role === 'Admin' ? 'admin' : 'cliente');
                    div.textContent = m.text;
                    chatBox.appendChild(div);
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        });
};

// WebSocket: recibir
conn.onmessage = (e) => {
    const data = JSON.parse(e.data);
    if (data.consultaId === consultaId && data.user !== user) {
        agregarMensaje('admin', data.message);
    }
};

// Enviar mensaje
sendBtn.addEventListener('click', enviarMensaje);
msgInput.addEventListener('keypress', e => { if (e.key === 'Enter') enviarMensaje(); });

function enviarMensaje() {
    const msg = msgInput.value.trim();
    if (!msg) return;

    const mensajeFinal = `${user}: ${msg}`;
    agregarMensaje('cliente', mensajeFinal);
    conn.send(JSON.stringify({ consultaId, user, message: mensajeFinal }));

    // Guardar en BD
    fetch('update_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${consultaId}&role=Cliente&text=${encodeURIComponent(mensajeFinal)}`
    });

    msgInput.value = '';
}

function agregarMensaje(tipo, texto) {
    const div = document.createElement('div');
    div.classList.add('message', tipo);
    div.textContent = texto;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}
</script>
</body>
</html>
