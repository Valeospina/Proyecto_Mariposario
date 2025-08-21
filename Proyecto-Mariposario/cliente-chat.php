<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mantener el mismo ID de consulta en la sesión
if (!isset($_SESSION['consultaId'])) {
    $_SESSION['consultaId'] = rand(1000, 9999);
}
$consultaId = $_SESSION['consultaId'];
$userName = $_SESSION['user_name'] ?? 'Usuario';
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
:root {
    --main-green: #8BC34A;
    --darker-green: #6fa12d;
    --background-light: #f8f9fa;
    --border-color: #e9e9e9;
    --text-color: #333;
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--background-light);
    color: var(--text-color);
}

.user-sidebar {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.05);
    padding: 25px;
    margin-bottom: 30px;
    text-align: center;
}

.user-sidebar img {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    border: 4px solid var(--main-green);
    margin-bottom: 15px;
    object-fit: cover;
}

.user-sidebar h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
}

.user-sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.user-sidebar ul li {
    margin-bottom: 10px;
}

.user-sidebar ul li a {
    display: block;
    color: var(--text-color);
    padding: 10px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s;
}

.user-sidebar ul li a:hover {
    background: var(--main-green);
    color: #fff;
}

.chat-wrapper {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 0 12px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    height: 550px;
}

.chat-header {
    background: var(--main-green);
    color: #fff;
    padding: 15px;
    font-size: 18px;
    font-weight: 600;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

.chat-body {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background: var(--background-light);
}

.message {
    max-width: 70%;
    padding: 10px 14px;
    margin-bottom: 12px;
    border-radius: 18px;
    font-size: 14px;
    animation: fadeIn 0.3s ease;
    word-wrap: break-word;
}

.cliente {
    background: var(--main-green);
    color: #fff;
    margin-left: auto;
    border-bottom-right-radius: 0;
}

.admin {
    background: #198754;
    color: #fff;
    margin-right: auto;
    border-bottom-left-radius: 0;
}

.system {
    background: #e0e0e0;
    color: #333;
    margin: auto;
    font-size: 13px;
    text-align: center;
}

.chat-footer {
    padding: 15px;
    border-top: 1px solid var(--border-color);
    background: #fff;
}

.input-box {
    display: flex;
    gap: 10px;
}

.input-box input {
    flex: 1;
    padding: 12px;
    border-radius: 25px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.input-box button {
    background: var(--main-green);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    font-size: 18px;
    cursor: pointer;
    transition: 0.3s;
}

.input-box button:hover {
    background: var(--darker-green);
}

@keyframes fadeIn {
    from {opacity:0;transform:translateY(10px);}
    to {opacity:1;transform:translateY(0);}
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
                            <li><a href="usuario.php" class="<?= $currentPage=='user-profile.php'?'active':'' ?>"><i class="fas fa-user"></i> Perfil</a></li>
                            <li><a href="MisPedidos.php" class="<?= $currentPage=='MisPedidos.php'?'active':'' ?>"><i class="fas fa-shopping-bag"></i> Mis Pedidos</a></li>
                            <li><a href="eventosReservados.php" class="<?= $currentPage=='eventosReservados.php'?'active':'' ?>"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
                            <li><a href="notificaciones.php" class="<?= $currentPage=='notificaciones.php'?'active':'' ?>"><i class="fas fa-bell"></i> Notificaciones <span class="badge badge-primary">3</span></a></li>
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
